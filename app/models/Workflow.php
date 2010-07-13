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


/*
object(Identity) (4) {
   "name" private => string(21) "Ing. Tomáš Vančura"
   "roles" private => array(1) {
      0 => string(11) "programator"
   }
   "data" private => array(10) {
      "user_id" => string(2) "25"
      "active" => string(1) "1"
      "date_created" => string(19) "2010-04-16 11:55:23"
      "last_modified" => NULL
      "last_login" => string(19) "2010-05-14 11:05:27"
      "username" => string(5) "tomik"
      "last_ip" => string(9) "127.0.0.1"
      "identity" => object(DibiRow) (11) {
         "osoba_id" => string(1) "1"
         "prijmeni" => string(8) "Vančura"
         "jmeno" => string(7) "Tomáš"
         "titul_pred" => string(4) "Ing."
         "titul_za" => string(0) ""
         "email" => string(16) "tomas@vancura.eu"
         "telefon" => string(11) "776 722 189"
         "pozice" => string(12) "programátor"
         "stav" => string(1) "1"
         "date_created" => string(19) "2010-04-02 10:24:14"
         "date_modified" => string(19) "2010-04-07 15:31:26"
      }
      "display_name" => string(21) "Ing. Tomáš Vančura"
      "user_roles" => array(1) {
         0 => object(DibiRow) (11) {
            ...
         }
      }
   }
*/


            $data = array();
            $data['dokument_id'] = $dokument_id;
            $data['dokument_version'] = 1;
            $data['stav_dokumentu'] = 1;
            $data['aktivni'] = 1;
            $data['prideleno'] = $user->user_id;
            $data['prideleno_info'] = serialize($user_info->identity);
            $data['orgjednotka_id'] = null;
            $data['orgjednotka_info'] = '';
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
            $data['date_predani'] = new DateTime();
            $data['user_id'] = $user->user_id;
            $data['user_info'] = serialize($user_info->identity);
            $data['poznamka'] = $poznamka;

            $result_insert = $this->insert($data);

            //if ($transaction)
            //dibi::commit();

            if ( $result_insert ) {
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


    public function prevzit($dokument_id, $user_id)
    {
        if ( is_numeric($dokument_id) ) {

            $predan_array = $this->dokument($dokument_id, 0);
            $predan = is_array($predan_array)?$predan_array[0]:null;

            if ( $predan ) {
                if ( $predan->prideleno == $user_id ) {

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

    public function vyrizuje($dokument_id, $user_id)
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

                    /*if ( $orgjednotka_id ) {
                        $OrgJednotka = new Orgjednotka();
                        $org_info = $OrgJednotka->getInfo($orgjednotka_id);
                        $data['orgjednotka_id'] = $orgjednotka_id;
                        $data['orgjednotka_info'] = serialize($org_info);
                    } else {
                        $data['orgjednotka_id'] = null;
                        $data['orgjednotka_info'] = '';
                    }*/

                    $data['date'] = new DateTime();
                    $data['user_id'] = $user->user_id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['poznamka'] = $predan->poznamka;

                    $result_insert = $this->insert($data);

                    //if ($transaction)
                    //dibi::commit();

                    if ( $result_insert ) {
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

    public function vyrizeno($dokument_id, $user_id)
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

                    /*if ( $orgjednotka_id ) {
                        $OrgJednotka = new Orgjednotka();
                        $org_info = $OrgJednotka->getInfo($orgjednotka_id);
                        $data['orgjednotka_id'] = $orgjednotka_id;
                        $data['orgjednotka_info'] = serialize($org_info);
                    } else {
                        $data['orgjednotka_id'] = null;
                        $data['orgjednotka_info'] = '';
                    }*/

                    $data['date'] = new DateTime();
                    $data['user_id'] = $user->user_id;
                    $data['user_info'] = serialize($user_info->identity);
                    $data['poznamka'] = $predan->poznamka;

                    $result_insert = $this->insert($data);

                    //if ($transaction)
                    //dibi::commit();

                    if ( $result_insert ) {
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
                array('aktivni=%i', 1),
                array('prideleno=%i', $user_id),
            );
        $param['limit'] = 1;

        $rows = $this->fetchAllComplet($param);
        $row = $rows->fetch();

        return ($row)?true:false;
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
                array('aktivni=%i', 1),
                array('prideleno=%i', $user_id),
            );
        $param['limit'] = 1;

        $rows = $this->fetchAllComplet($param);
        $row = $rows->fetch();

        return ($row)?true:false;
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

