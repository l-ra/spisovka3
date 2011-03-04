<?php

class Workflow extends BaseModel
{

    protected $name = 'workflow';
    protected $primary = 'id';


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
            foreach ($rows as $index => &$wf) {
                if ( !empty($wf->prideleno) ) {
                    $osoba = $UserModel->getUser($wf->prideleno, 1);
                    if ( $osoba ) {
                        $rows[$index]->prideleno_jmeno = Osoba::displayName($osoba->identity);
                    }
                }
                if ( !empty($wf->prideleno_info) ) {
                    if ( $prideleno_info = unserialize($wf->prideleno_info) ) {
                        $wf->prideleno_info = $prideleno_info;
                    }
                }
                if ( !empty($wf->orgjednotka_info) ) {
                    if ( $orgjednotka_info = unserialize($wf->orgjednotka_info) ) {
                        $wf->orgjednotka_info = $orgjednotka_info;
                    }
                }


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
            $user_info = $UserModel->getUser($user->id, 1);
            $org_info = $UserModel->getOrg($user->id);
            if ( is_array($org_info) ) {
                $org_info = current($org_info);
            }

            $data = array();
            $data['dokument_id'] = $dokument_id;
            $data['dokument_version'] = 1;
            $data['stav_dokumentu'] = 1;
            $data['aktivni'] = 1;
            $data['prideleno'] = $user->id;
            $data['prideleno_info'] = serialize($user_info->identity);
            $data['orgjednotka_id'] = @$org_info->orgjednotka_id;
            $data['orgjednotka_info'] = @serialize($org_info);
            $data['stav_osoby'] = 1;
            $data['date'] = new DateTime();
            $data['user_id'] = $user->id;
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
            $user_info = $UserModel->getUser($user->id, 1);

            $data = array();
            $data['dokument_id'] = $dokument_info->id;
            $data['dokument_version'] = $dokument_info->version;
            $data['stav_dokumentu'] = 2;
            $data['aktivni'] = 1;

            $data['stav_osoby'] = 0;

            if ( $user_id ) {
                $prideleno_info = $UserModel->getUser($user_id, 1);
                $data['prideleno'] = $prideleno_info->id;
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

                if ( $org_info ) {
                    $log = 'Dokument předán organizační jednotce '. $org_info->zkraceny_nazev .'.';
                } else {
                    $log = 'Dokument předán organizační jednotce.';
                }
                

            } else {
                $data['orgjednotka_id'] = null;
                $data['orgjednotka_info'] = '';
            }

            $data['date'] = new DateTime();
            $data['date_predani'] = new DateTime();
            $data['user_id'] = $user->id;
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
                    $user_info = $UserModel->getUser($user->id, 1);

                    $data = array();
                    $data['stav_osoby'] = 1;
                    $data['date'] = new DateTime();
                    $data['user_id'] = $user->id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['aktivni'] = 1;

                    $where = array('id=%i',$predan->id);
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
                    $user_info = $UserModel->getUser($user->id, 1);

                    $data = array();
                    $data['dokument_id'] = $dokument_info->id;
                    $data['dokument_version'] = $dokument_info->version;
                    $data['stav_dokumentu'] = 3;
                    $data['aktivni'] = 1;

                    $data['stav_osoby'] = 1;

                    if ( $user_id ) {
                        $prideleno_info = $UserModel->getUser($user_id, 1);
                        $data['prideleno'] = $prideleno_info->id;
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
                    $data['user_id'] = $user->id;
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

    public function vyrizeno($dokument_id, $user_id, $orgjednotka_id = null, $accepted = null)
    {
        if ( is_numeric($dokument_id) ) {

            $predan_array = $this->dokument($dokument_id, 1);
            $predan = is_array($predan_array)?$predan_array[0]:null;

            if ( $predan ) {
                if ( $predan->prideleno == $user_id ) {

                    //$transaction = (! dibi::inTransaction());
                    //if ($transaction)
                    //dibi::begin();

                    $Dokument = new Dokument();
                    $dokument_info = $Dokument->getInfo($dokument_id);

                    // Test na uplnost dat
                    if ( $kontrola = $Dokument->kontrola($dokument_info) ) {
                        foreach ($kontrola as $kmess) {
                            Environment::getApplication()->getPresenter()->flashMessage($kmess,'warning');
                        }
                        return false;
                    }

                    // spouteci udalost - manualni nebo automativky
                    if ( $dokument_info->spisovy_znak_udalost_stav == 2 && is_null($accepted) ) {
                        $stav = 5;
                        $datum_spusteni = date("Y-m-d");
                    } else if ( !is_null($accepted) ) {
                        $stav = $accepted['stav'];
                        if ( !empty($accepted['datum']) ) {
                            $datum_spusteni = $accepted['datum'];
                        } else {
                            $datum_spusteni = null;
                        }
                    } else {
                        return "udalost";
                    }

                    // Deaktivujeme starsi zaznamy
                    $this->deaktivovat($dokument_id);

                    $UserModel = new UserModel();
                    $user = Environment::getUser()->getIdentity();
                    $user_info = $UserModel->getUser($user->id, 1);

                    $data = array();
                    $data['dokument_id'] = $dokument_info->id;
                    $data['dokument_version'] = $dokument_info->version;
                    $data['stav_dokumentu'] = $stav;

                    $data['stav_osoby'] = 1;
                    $data['aktivni'] = 1;

                    if ( $user_id ) {
                        $prideleno_info = $UserModel->getUser($user_id, 1);
                        $data['prideleno'] = $prideleno_info->id;
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
                    $data['user_id'] = $user->id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['poznamka'] = $predan->poznamka;

                    $result_insert = $this->insert($data);

                    //if ($transaction)
                    //dibi::commit();

                    if ( $result_insert ) {

                        $Log = new LogModel();
                        $Log->logDokument($dokument_id, LogModel::DOK_VYRIZEN, 'Dokument označen za vyřízený.');

                        if ( $stav == 5 ) {
                            $data = array('datum_spousteci_udalosti'=>$datum_spusteni);
                            $Dokument->ulozit($data, $dokument_id);
                            $Log->logDokument($dokument_id, LogModel::DOK_SPUSTEN, 'Byla spuštěna událost. Začíná běžet skartační lhůta.');
                        }

                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return 'neprideleno';
                }
            } else {
                return 'neprideleno';
            }

        } else {
            return false;
        }
    }

    public function keskartaci($dokument_id, $user_id, $orgjednotka_id = null)
    {
        if ( is_numeric($dokument_id) ) {

                $user = Environment::getUser();
                if ( $user->isInRole('skartacni_dohled') || $user->isInRole('superadmin') ) {

                    //$transaction = (! dibi::inTransaction());
                    //if ($transaction)
                    //dibi::begin();

                    $Dokument = new Dokument();
                    $dokument_info = $Dokument->getInfo($dokument_id);

                    // Deaktivujeme starsi zaznamy
                    $this->deaktivovat($dokument_id);

                    $UserModel = new UserModel();
                    $user_info = $UserModel->getUser($user->getIdentity()->id, 1);

                    $data = array();
                    $data['dokument_id'] = $dokument_info->id;
                    $data['dokument_version'] = $dokument_info->version;
                    $data['stav_dokumentu'] = 6;
                    $data['stav_osoby'] = 1;
                    $data['aktivni'] = 1;
                    $data['prideleno'] = $dokument_info->prideleno->prideleno;
                    $data['prideleno_info'] = serialize($dokument_info->prideleno->prideleno_info);
                    $data['orgjednotka_id'] = $dokument_info->prideleno->orgjednotka_id;
                    $data['orgjednotka_info'] = serialize($dokument_info->prideleno->orgjednotka_info);

                    $data['date'] = new DateTime();
                    $data['user_id'] = $user->getIdentity()->id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['poznamka'] = $dokument_info->prideleno->poznamka;

                    $result_insert = $this->insert($data);

                    //if ($transaction)
                    //dibi::commit();

                    if ( $result_insert ) {

                        $Log = new LogModel();
                        $Log->logDokument($dokument_id, LogModel::DOK_KESKARTACI, 'Dokument přidán do skartačního řízení.');

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
    }

    public function archivovat($dokument_id, $user_id, $orgjednotka_id = null)
    {
        if ( is_numeric($dokument_id) ) {

                $user = Environment::getUser();
                if ( $user->isInRole('skartacni_komise') || $user->isInRole('superadmin') ) {

                    //$transaction = (! dibi::inTransaction());
                    //if ($transaction)
                    //dibi::begin();

                    $Dokument = new Dokument();
                    $dokument_info = $Dokument->getInfo($dokument_id);

                    // Deaktivujeme starsi zaznamy
                    $this->deaktivovat($dokument_id);

                    $UserModel = new UserModel();
                    $user_info = $UserModel->getUser($user->getIdentity()->id, 1);

                    $data = array();
                    $data['dokument_id'] = $dokument_info->id;
                    $data['dokument_version'] = $dokument_info->version;
                    $data['stav_dokumentu'] = 7;
                    $data['stav_osoby'] = 1;
                    $data['aktivni'] = 1;
                    $data['prideleno'] = $dokument_info->prideleno->prideleno;
                    $data['prideleno_info'] = serialize($dokument_info->prideleno->prideleno_info);
                    $data['orgjednotka_id'] = $dokument_info->prideleno->orgjednotka_id;
                    $data['orgjednotka_info'] = serialize($dokument_info->prideleno->orgjednotka_info);

                    $data['date'] = new DateTime();
                    $data['user_id'] = $user->getIdentity()->id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['poznamka'] = $dokument_info->prideleno->poznamka;

                    $result_insert = $this->insert($data);

                    //if ($transaction)
                    //dibi::commit();

                    if ( $result_insert ) {

                        $Log = new LogModel();
                        $Log->logDokument($dokument_id, LogModel::DOK_ARCHIVOVAN, 'Dokument uložen do archivu.');

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
    }

    public function skartovat($dokument_id, $user_id, $orgjednotka_id = null)
    {
        if ( is_numeric($dokument_id) ) {

                $user = Environment::getUser();
                if ( $user->isInRole('skartacni_komise') || $user->isInRole('superadmin') ) {

                    //$transaction = (! dibi::inTransaction());
                    //if ($transaction)
                    //dibi::begin();

                    $Dokument = new Dokument();
                    $dokument_info = $Dokument->getInfo($dokument_id);

                    // Deaktivujeme starsi zaznamy
                    $this->deaktivovat($dokument_id);

                    $UserModel = new UserModel();
                    $user_info = $UserModel->getUser($user->getIdentity()->id, 1);

                    $data = array();
                    $data['dokument_id'] = $dokument_info->id;
                    $data['dokument_version'] = $dokument_info->version;
                    $data['stav_dokumentu'] = 8;
                    $data['stav_osoby'] = 1;
                    $data['aktivni'] = 1;
                    $data['prideleno'] = $dokument_info->prideleno->prideleno;
                    $data['prideleno_info'] = serialize($dokument_info->prideleno->prideleno_info);
                    $data['orgjednotka_id'] = $dokument_info->prideleno->orgjednotka_id;
                    $data['orgjednotka_info'] = serialize($dokument_info->prideleno->orgjednotka_info);

                    $data['date'] = new DateTime();
                    $data['user_id'] = $user->getIdentity()->id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['poznamka'] = $dokument_info->prideleno->poznamka;

                    $result_insert = $this->insert($data);

                    //if ($transaction)
                    //dibi::commit();

                    if ( $result_insert ) {

                        $Log = new LogModel();
                        $Log->logDokument($dokument_id, LogModel::DOK_SKARTOVAN, 'Dokument byl skartován.');

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
            $user_id = Environment::getUser()->getIdentity()->id;
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
            $user_id = Environment::getUser()->getIdentity()->id;
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

