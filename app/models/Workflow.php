<?php

class Workflow extends BaseModel
{

    protected $name = 'workflow';
    protected $primary = 'workflow_id';


    public function dokument($dokument_id, $stav=null)
    {

        $param = array();

        if ( !is_null($stav) ) {
            $param['where'] = array( array('dokument_id=%i', $dokument_id), array('stav_osoby=%i', $stav) );
            $param['limit'] = 1;
        } else {
            $param['where'] = array( array('dokument_id=%i', $dokument_id) );
        }

        $param['order'] = array('date'=>'DESC');

        $rows = $this->fetchAllComplet($param);
        $rows = $rows->fetchAll();

        if ( count($rows)>0 ) {

            $UserModel = new UserModel();
            foreach ($rows as $index => $wf) {
               
                $osoba = $UserModel->getUser($wf->prideleno, 1);
                $rows[$index]->prideleno_jmeno = Osoba::displayName($osoba->identity);

            }


            return $rows;
        } else {
            return null;
        }


        

    }

    /**
     * Vytvori novy proces dokumentu
     *
     * @param int $dokument_id
     * @return bool
     */
    public function vytvorit($dokument_id, $poznamka = '')
    {
        if ( is_numeric($dokument_id) ) {

            $user = Environment::getUser()->getIdentity();

            $UserModel = new UserModel();
            $user_info = $UserModel->getUser($user->user_id, 1);
            $org_info = $UserModel->getOrg($user->user_id);
            if ( is_array($org_info) ) {
                $org_info = current($org_info);
            }

            $data = array();
            $data['dokument_id'] = $dokument_id;
            $data['dokument_version'] = 1;
            $data['stav_dokumentu'] = 1;
            $data['aktivni'] = 1;
            $data['prideleno'] = $user->user_id;
            $data['prideleno_info'] = serialize($user_info->identity);
            $data['orgjednotka_id'] = @$org_info->orgjednotka_id;
            $data['orgjednotka_info'] = @serialize($org_info);
            $data['stav_osoby'] = 1;
            $data['date'] = new DateTime();
            $data['user_id'] = $user->user_id;
            $data['user_info'] = serialize($user_info->identity);
            $data['poznamka'] = $poznamka;

            if ( $this->insert($data) ) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    public function priradit($dokument_id, $user_id, $orgjednotka_id, $poznamka = '')
    {
        if ( is_numeric($dokument_id) ) {

            //$transaction = (! dibi::inTransaction());
            //if ($transaction)
            //dibi::begin();

            // Vyradime ty zamestanance, kterym byl dokument v minulosti predan
            $update = array('stav_osoby%sql'=>'stav_osoby+100');
            $this->update($update, array(array('dokument_id=%i',$dokument_id),array('stav_osoby=0')));

            // Deaktivujeme starsi zaznamy
            //$this->deaktivovat($dokument_id);


            $Dokument = new Dokument();
            $dokument_info = $Dokument->getInfo($dokument_id);

            $UserModel = new UserModel();
            $user = Environment::getUser()->getIdentity();
            $user_info = $UserModel->getUser($user->user_id, 1);

            $data = array();
            $data['dokument_id'] = $dokument_info->dokument_id;
            $data['dokument_version'] = $dokument_info->dokument_version;
            $data['stav_dokumentu'] = 2;
            $data['aktivni'] = 1;

            $data['stav_osoby'] = 0;

            if ( $user_id ) {
                $prideleno_info = $UserModel->getUser($user_id, 1);
                $data['prideleno'] = $prideleno_info->user_id;
                $data['prideleno_info'] = serialize($prideleno_info->identity);

                $log = 'Dokument předán zaměstnanci '. Osoba::displayName($prideleno_info->identity) .'.';

            } else {
                $data['prideleno'] = null;
                $data['prideleno_info'] = '';
            }

            if ( $orgjednotka_id ) {
                $OrgJednotka = new Orgjednotka();
                $org_info = $OrgJednotka->getInfo($orgjednotka_id);
                $data['orgjednotka_id'] = $orgjednotka_id;
                $data['orgjednotka_info'] = serialize($org_info);

                $log = 'Dokument předán organizační jednotce '. $org_info->zkraceny_nazev .'.';

            } else {
                $data['orgjednotka_id'] = null;
                $data['orgjednotka_info'] = '';
            }

            $data['date'] = new DateTime();
            $data['date_predani'] = new DateTime();
            $data['user_id'] = $user->user_id;
            $data['user_info'] = serialize($user_info->identity);
            $data['poznamka'] = $poznamka;

            $result_insert = $this->insert($data);

            //if ($transaction)
            //dibi::commit();

            if ( $result_insert ) {

                $Log = new LogModel();
                $Log->logDokument($dokument_id, LogModel::DOK_PREDAN, $log);
                

                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

    public function zrusit_prevzeti($dokument_id)
    {
        if ( is_numeric($dokument_id) ) {

            // Vyradime ty zamestanance, kterym byl dokument v minulosti predan
            $update = array('stav_osoby%sql'=>'stav_osoby+100');
            $this->update($update, array(array('dokument_id=%i',$dokument_id),array('stav_osoby=0')));


            // TODO upravit aktiitu dokumentu - reaktivovat posledni dokument

            return true;

        } else {
            return false;
        }

    }


    public function prevzit($dokument_id, $user_id, $orgjednotka_id = null)
    {
        if ( is_numeric($dokument_id) ) {

            $predan_array = $this->dokument($dokument_id, 0);
            $predan = is_array($predan_array)?$predan_array[0]:null;

            if ( $predan ) {

                // test predaneho
                // pokud neni predana osoba, tak test na vedouciho org.jednotky
                $access = 0; $log_plus = ".";
                if ( empty($predan->prideleno) ) {
                    if ( Orgjednotka::isInOrg($predan->orgjednotka_id, 'vedouci', $user_id) ) {
                        $access = 1;
                        $log_plus = " určený organizační jednotce ". $predan->orgjednotka_info->zkraceny_nazev. ".";
                    }
                } else {
                    if ( $predan->prideleno == $user_id ) {
                        $access = 1;
                    }
                }

                if ( $access == 1 ) {

                    //$transaction = (! dibi::inTransaction());
                    //if ($transaction)
                    //dibi::begin();

                    // Prirazene zamestanance predame uz nejsou prirazeni
                    $update = array('stav_osoby'=>2);
                    $this->update($update, array(array('dokument_id=%i',$dokument_id),
                                                 array('stav_osoby=1')
                                                )
                                 );
                    
                    // Deaktivujeme starsi zaznamy
                    $this->deaktivovat($dokument_id);

                    $UserModel = new UserModel();
                    $user = Environment::getUser()->getIdentity();
                    $user_info = $UserModel->getUser($user->user_id, 1);

                    $data = array();
                    $data['stav_osoby'] = 1;
                    $data['date'] = new DateTime();
                    $data['user_id'] = $user->user_id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['aktivni'] = 1;

                    $where = array('workflow_id=%i',$predan->workflow_id);
                    $result_update = $this->update($data,$where);

                    //if ($transaction)
                    //dibi::commit();

                    if ( $result_update ) {

                        $Log = new LogModel();
                        $Log->logDokument($dokument_id, LogModel::DOK_PRIJAT, 'Zaměstnanec '. Osoba::displayName($user_info->identity) .' přijal dokument'.$log_plus);


                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    public function vyrizuje($dokument_id, $user_id, $orgjednotka_id = null)
    {
        if ( is_numeric($dokument_id) ) {

            $predan_array = $this->dokument($dokument_id, 1);
            $predan = is_array($predan_array)?$predan_array[0]:null;

            if ( $predan ) {

                $access = 0;
                if ( empty($predan->prideleno) ) {
                    if ( Orgjednotka::isInOrg($predan->orgjednotka_id, 'vedouci', $user_id) ) {
                        $access = 1;
                    }
                } else {
                    if ( $predan->prideleno == $user_id ) {
                        $access = 1;
                    }
                }

                if ( $access == 1 ) {

                    //$transaction = (! dibi::inTransaction());
                    //if ($transaction)
                    //dibi::begin();

                    // Deaktivujeme starsi zaznamy
                    $this->deaktivovat($dokument_id);

                    $Dokument = new Dokument();
                    $dokument_info = $Dokument->getInfo($dokument_id);

                    $UserModel = new UserModel();
                    $user = Environment::getUser()->getIdentity();
                    $user_info = $UserModel->getUser($user->user_id, 1);

                    $data = array();
                    $data['dokument_id'] = $dokument_info->dokument_id;
                    $data['dokument_version'] = $dokument_info->dokument_version;
                    $data['stav_dokumentu'] = 3;
                    $data['aktivni'] = 1;

                    $data['stav_osoby'] = 1;

                    if ( $user_id ) {
                        $prideleno_info = $UserModel->getUser($user_id, 1);
                        $data['prideleno'] = $prideleno_info->user_id;
                        $data['prideleno_info'] = serialize($prideleno_info->identity);
                    } else {
                        $data['prideleno'] = null;
                        $data['prideleno_info'] = '';
                    }

                    if ( $orgjednotka_id ) {
                        $OrgJednotka = new Orgjednotka();
                        $org_info = $OrgJednotka->getInfo($orgjednotka_id);
                        $data['orgjednotka_id'] = $orgjednotka_id;
                        $data['orgjednotka_info'] = serialize($org_info);
                    } else {
                        $data['orgjednotka_id'] = null;
                        $data['orgjednotka_info'] = '';
                    }

                    $data['date'] = new DateTime();
                    $data['user_id'] = $user->user_id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['poznamka'] = $predan->poznamka;

                    $result_insert = $this->insert($data);

                    //if ($transaction)
                    //dibi::commit();

                    if ( $result_insert ) {

                        $Log = new LogModel();
                        $Log->logDokument($dokument_id, LogModel::DOK_KVYRIZENI, 'Zaměstnanec '. Osoba::displayName($user_info->identity) .' převzal dokument k vyřízení.');

                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    public function vyrizeno($dokument_id, $user_id, $orgjednotka_id = null)
    {
        if ( is_numeric($dokument_id) ) {

            $predan_array = $this->dokument($dokument_id, 1);
            $predan = is_array($predan_array)?$predan_array[0]:null;

            if ( $predan ) {
                if ( $predan->prideleno == $user_id ) {

                    //$transaction = (! dibi::inTransaction());
                    //if ($transaction)
                    //dibi::begin();

                    // Deaktivujeme starsi zaznamy
                    $this->deaktivovat($dokument_id);

                    $Dokument = new Dokument();
                    $dokument_info = $Dokument->getInfo($dokument_id);

                    $UserModel = new UserModel();
                    $user = Environment::getUser()->getIdentity();
                    $user_info = $UserModel->getUser($user->user_id, 1);

                    $data = array();
                    $data['dokument_id'] = $dokument_info->dokument_id;
                    $data['dokument_version'] = $dokument_info->dokument_version;
                    $data['stav_dokumentu'] = 4;

                    $data['stav_osoby'] = 1;
                    $data['aktivni'] = 1;

                    if ( $user_id ) {
                        $prideleno_info = $UserModel->getUser($user_id, 1);
                        $data['prideleno'] = $prideleno_info->user_id;
                        $data['prideleno_info'] = serialize($prideleno_info->identity);
                    } else {
                        $data['prideleno'] = null;
                        $data['prideleno_info'] = '';
                    }

                    if ( $orgjednotka_id ) {
                        $OrgJednotka = new Orgjednotka();
                        $org_info = $OrgJednotka->getInfo($orgjednotka_id);
                        $data['orgjednotka_id'] = $orgjednotka_id;
                        $data['orgjednotka_info'] = serialize($org_info);
                    } else {
                        $data['orgjednotka_id'] = null;
                        $data['orgjednotka_info'] = '';
                    }

                    $data['date'] = new DateTime();
                    $data['user_id'] = $user->user_id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['poznamka'] = $predan->poznamka;

                    $result_insert = $this->insert($data);

                    //if ($transaction)
                    //dibi::commit();

                    if ( $result_insert ) {

                        $Log = new LogModel();
                        $Log->logDokument($dokument_id, LogModel::DOK_VYRIZEN, 'Dokument označen za vyřízený.');

                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                return false;
            }

        } else {
            return false;
        }
    }


    /**
     * Je uzivatel vlastnikem dokumentu
     * @param int $dokument_id
     * @param int $user_id
     * @return bool 
     */
    public function prirazeny($dokument_id, $user_id = null)
    {
        $param = array();

        if ( is_null($user_id) ) {
            $user_id = Environment::getUser()->getIdentity()->user_id;
        }

        $param['where'] = array( 
                array('dokument_id=%i', $dokument_id),
                array('stav_osoby=%i', 1),
                array('aktivni=%i', 1)
            );
        $param['limit'] = 1;

        $rows = $this->fetchAllComplet($param);
        $row = $rows->fetch();

        if ( $row ) {
            if ( empty($row->prideleno) ) {
                if ( Orgjednotka::isInOrg($row->orgjednotka_id, 'vedouci', $user_id) ) {
                    return true;
                }
            } else {
                if ( $row->prideleno == $user_id ) {
                    return true;
                }
            }

        }

        return false;
    }

    /**
     * Je uzivatel potencialni vlastnik dokumentu
     * @param int $dokument_id
     * @param int $user_id
     * @return bool
     */
    public function predany($dokument_id, $user_id = null)
    {
        $param = array();

        if ( is_null($user_id) ) {
            $user_id = Environment::getUser()->getIdentity()->user_id;
        }


        $param['where'] = array(
                array('dokument_id=%i', $dokument_id),
                array('stav_osoby=%i', 0),
                array('aktivni=%i', 1)
            );
        //array('prideleno=%i', $user_id),
        $param['limit'] = 1;

        $rows = $this->fetchAllComplet($param);
        $row = $rows->fetch();

        if ( $row ) {
            if ( empty($row->prideleno) ) {
                if ( Orgjednotka::isInOrg($row->orgjednotka_id, 'vedouci', $user_id) ) {
                    return true;
                }
            } else {
                if ( $row->prideleno == $user_id ) {
                    return true;
                }
            }

        }
        
        return false;
    }

    protected function deaktivovat($dokument_id, $dokument_version = null) {

        if ( is_numeric($dokument_id) ) {
            $update = array('aktivni'=>0);
            $this->update($update, array(array('dokument_id=%i',$dokument_id),array('aktivni=1')));
            return true;
        } else {
            return false;
        }

    }

}

