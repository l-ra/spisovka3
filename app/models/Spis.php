<?php

class Spis extends TreeModel
{

    protected $name = 'spis';
    protected $primary = 'id';
    
    
    public function getInfo($spis_id)
    {

        if ( empty($spis_id) ) {
            return null;
        } else if ( !is_numeric($spis_id) ) {
            // string - nazev
            $result = $this->fetchRow(array('nazev=%s',$spis_id));
        } else {
            // int - id
            $result = $this->fetchRow(array('id=%i',$spis_id));
        }
        
        $row = $result->fetch();
        return ($row) ? $row : NULL;

    }

    public function seznamSpisovychPlanu($pouze_aktivni = 0)
    {

        if ( $pouze_aktivni ) {
            $where = array("typ='SP'","stav=1");
        } else {
            $where = array(array("typ='SP'"));
        }

        $order = array('stav'=>'DESC','date_created'=>'DESC');
        $query = $this->fetchAll($order,$where);
        $rows = $query->fetchAll();
        if ( count($rows)>0 ) {
            $spis_plan = array();
            foreach( $rows as $row ) {
                $spis_plan[ $row->id ] = $row->nazev;
            }
            return $spis_plan;
        } else {
            return null;
        }
    }

    public function getSpisovyPlan()
    {

        $where = array("typ='SP'","stav=1");
        $order = array('stav'=>'DESC','date_created'=>'DESC');
        $query = $this->fetchAll($order,$where,null,1);
        $rows = $query->fetch();
        if ( $rows ) {
            return $rows->id;
        } else {
            return null;
        }
    }

    public function seznam($args = null, $select = 0, $parent_id = null)
    {

        $params = null;
        if ( !is_null($args) ) {
            $params['where'] = $args['where'];
        }
        if ( $select == 5 ) {
            $params['paginator'] = 1;
        }

        $params['order'] = array('tb.nazev');
        return $this->nacti($parent_id, true, true, $params);

    }

    public function spisovka($args) {

        if ( isset($args['where']) ) {
            $args['where'][] = array("NOT (tb.typ = 'S' AND tb.stav > 2)");
        } else {
            $args['where'] = array(array("NOT (tb.typ = 'S' AND tb.stav > 2)"));
        }

        return $args;
    }

    public function spisovna($args) {

        if ( isset($args['where']) ) {
            $args['where'][] = array("NOT (tb.typ = 'S' AND tb.stav < 3)");
        } else {
            $args['where'] = array(array("NOT (tb.typ = 'S' AND tb.stav < 3)"));
        }

        return $args;
    }

    public function spisovna_prijem($args) {

        if ( isset($args['where']) ) {
            $args['where'][] = array("NOT (tb.typ = 'S' AND tb.stav <> 2)");
        } else {
            $args['where'] = array(array("NOT (tb.typ = 'S' AND tb.stav <> 2)"));
        }

        return $args;
    }


    public function vytvorit($data) {

        $data['datum_otevreni'] = new DateTime();
        $data['date_created'] = new DateTime();
        $data['user_created'] = Environment::getUser()->getIdentity()->id;
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Environment::getUser()->getIdentity()->id;

        if ( $data['typ'] == 'SP' ) {
            $data['spisovy_znak_plneurceny'] = $data['spisovy_znak'];
        }

        if ( !isset($data['parent_id']) ) $data['parent_id'] = null;
        if ( empty($data['parent_id']) ) $data['parent_id'] = null;
        if ( empty($data['spisovy_znak']) ) $data['spisovy_znak'] = '';
        if ( empty($data['datum_uzavreni']) ) $data['datum_uzavreni'] = null;
        if ( empty($data['datum_otevreni']) ) $data['datum_otevreni'] = null;
        
        if ( empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = 10;
        if ( !empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = (int) $data['skartacni_lhuta'];

        /*$SpisParent = $this->getInfo($data['parent_id']);
        if ( $SpisParent ) {
            $spis_znak_parent = self::spisovyZnak($SpisParent, 2);
            $data['spisovy_znak_plneurceny'] = $spis_znak_parent . $data['spisovy_znak'];
        } else {
            $data['spisovy_znak_plneurceny'] = $data['spisovy_znak'];
        }*/

        if ( empty($data['spisovy_znak_id']) ) {
            $data['spisovy_znak_id'] = null;
        } else {
            $data['spisovy_znak_id'] = (int) $data['spisovy_znak_id'];
        }
        if ( empty($data['spousteci_udalost_id']) ) {
            $data['spousteci_udalost_id'] = null;
        } else {
            $data['spousteci_udalost_id'] = (int) $data['spousteci_udalost_id'];
        }

        $data['stav'] = isset($data['stav'])?$data['stav']:1;
        //Debug::dump($data); exit;
        
        $spis_id = $this->vlozitH($data);
        return $spis_id;

    }

    public function upravit($data,$spis_id) {

        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Environment::getUser()->getIdentity()->id;


        // Vyplnění plneurceneho spisoveho znaku
        /*$spis = $this->getInfo($spis_id);
        if ( !empty($spis->parent_id) ) {
            $spis_parent = $this->getInfo($spis->parent_id);
            $spis_znak_parent = self::spisovyZnak($spis_parent, 2);
            $data['spisovy_znak_plneurceny'] = $spis_znak_parent . $data['spisovy_znak'];
        } else {
            $data['spisovy_znak_plneurceny'] = $data['spisovy_znak'];
        }*/

        if ( empty($data['spousteci_udalost_id']) ) $data['spousteci_udalost_id'] = null;
        if ( empty($data['spisovy_znak_id']) ) $data['spisovy_znak_id'] = null;
        if ( !isset($data['parent_id']) ) $data['parent_id'] = null;
        if ( empty($data['parent_id']) ) $data['parent_id'] = null;
        if ( !isset($data['parent_id_old']) ) $data['parent_id_old'] = null;
        if ( empty($data['parent_id_old']) ) $data['parent_id_old'] = null;

        if ( empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = 10;
        if ( !empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = (int) $data['skartacni_lhuta'];
        if ( empty($data['datum_otevreni']) ) $data['datum_otevreni'] = null;
        if ( empty($data['datum_uzavreni']) ) $data['datum_uzavreni'] = null;

        //Debug::dump($data); exit;

        $ret = $this->upravitH($data, $spis_id);
        
        return $ret;

    }

    public function zmenitStav($spis_id, $stav) {

        if ( !is_numeric($spis_id) || !is_numeric($stav) ) return null;

        if ( $stav != 1 ) {
            // Kontrola
            if ( $kontrola = $this->kontrolaDokumentu($spis_id) ) {
                foreach ($kontrola as $kmess) {
                    Environment::getApplication()->getPresenter()->flashMessage($kmess,'warning');
                }                
                return -1;
            }
        }
        
        $data = array();
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Environment::getUser()->getIdentity()->id;
        $data['stav'] = $stav;

        try {
            $this->update($data, array('id=%i',$spis_id));
            return true;
        } catch (exception $e) {
            return false;
        }

    }


    public static function spisovyZnak( $spis, $simple = 0 )
    {

        $user_config = Environment::getVariable('user_config');
        if ( !isset($user_config->spisovy_znak) ) {
            $maska = ".";
            $cifernik = 3;
        } else {
            $maska = $user_config->spisovy_znak->oddelovac;
            if ( $user_config->spisovy_znak->pocatecni_nuly == 1 ) {
                $cifernik = $user_config->spisovy_znak->pocet_znaku;
            } else {
                $cifernik = 0;
            }
        }

        if ( is_string($spis) ) {
            $simple = 0;
            $spis_tmp = $spis;
            $spis = new stdClass();
            $spis->spisovy_znak_plneurceny = $spis_tmp;
        }

        if ( $simple == 1 ) {
            // spisovy znak
            return sprintf('%0'.$cifernik.'d', $spis->spisovy_znak);
        } else if ( $simple == 2 ) {
            if ( empty($spis->spisovy_znak_plneurceny) ) {
                return "";
            } else {
                return $spis->spisovy_znak_plneurceny .".";
            }

        } else {
            // plneurceny spisovy znak
            if ( empty($spis->spisovy_znak_plneurceny) ) {
                return "";
            } else {
                $part_in = explode(".",$spis->spisovy_znak_plneurceny);
                $part_out = array();
                foreach ( $part_in as $part_index => $part_value ) {
                    $part_out[ $part_index ] = sprintf('%0'.$cifernik.'d', $part_value);
                }
                return implode($maska,$part_out);
            }
        }

    }

    public function maxSpisovyZnak( $spis_id = null )
    {

        if ( is_null($spis_id) ) {
            $spisovy_znak = $this->fetchAll( array('spisovy_znak'=>'DESC'), array("typ='SP'"), null, 1);
            $spisovy_znak_max = $spisovy_znak->fetch();

            if ( $spisovy_znak_max ) {
                return ($spisovy_znak_max->spisovy_znak + 1);
            } else {
                return 1;
            }
        } else {
            $Spis = $this->getInfo($spis_id);
            if ( $Spis ) {
                $spisovy_znak = $this->fetchAll( array('spisovy_znak'=>'DESC'), array(array("parent_id=%i",$Spis->id)), null, 1);
                $spisovy_znak_max = $spisovy_znak->fetch();
                if ( $spisovy_znak_max ) {
                    return ($spisovy_znak_max->spisovy_znak + 1);
                } else {
                    return 1;
                }
            } else {
                return 1;
            }
        }


    }

    public function kontrolaSpisovyZnak( $spisovy_znak, $spis_parent_id )
    {

        // zjistime rodice
        $spis_parent = $this->getInfo($spis_parent_id);

        // sestavime plneurceny spisovy znak
        $spisovy_znak_plneurceny = $spis_parent->spisovy_znak_plneurceny .'.'. $spisovy_znak;

        // vyhledame, zda existuje
        $spis_exist = $this->fetchAll(null, array(array("spisovy_znak_plneurceny=%s",$spisovy_znak_plneurceny)), null, 1);
        $spis_exist_fetch = $spis_exist->fetch();

        if ( !$spis_exist_fetch ) {
            return true;
        } else {
            return false;
        }

    }

    public function kontrolaSpisovyZnakPublic( $spisovy_znak, $spis_id )
    {

        // zjistime rodice
        $spis = $this->getInfo($spis_id);
        if ( $spis ) {
            $spis_parent = $this->getInfo($spis->parent_id);
            if ( $spis_parent ) {
                // sestavime plneurceny spisovy znak
                $spisovy_znak_plneurceny = $spis_parent->spisovy_znak_plneurceny .'.'. $spisovy_znak;

                // vyhledame, zda existuje
                $spis_exist = $this->fetchAll(null, array(array("spisovy_znak_plneurceny=%s",$spisovy_znak_plneurceny)), null, 1);
                $spis_exist_fetch = $spis_exist->fetch();

                if ( !$spis_exist_fetch ) {
                    return true;
                } else {
                    return false;
                }

            } else {
                return true;
            }

        } else {
            return false;
        }

    }

    public function predatDoSpisovny($spis_id)
    {

        // kontrola uzivatele
        $spis_info = $this->getInfo($spis_id);

        //echo "<pre>"; print_r($dokument_info); echo "</pre>"; exit;

        // Test na uplnost dat
        if ( $kontrola = $this->kontrola($spis_info) ) {
            // nejsou kompletni data - neprenasim
            foreach ($kontrola as $kmess) {
                Environment::getApplication()->getPresenter()->flashMessage('Spis '.$spis_info->nazev.' - '.$kmess,'warning');
            }
            return 'Spis '.$spis_info->nazev.' nelze přenést do spisovny! Nejsou vyřízeny všechny potřebné údaje.';
        }

        // Kontrola stavu - uzavren = krome 1
        if ( $spis_info->stav == 1 ) {
            return 'Spis '.$spis_info->nazev .' nelze přenést do spisovny! Spis není uzavřen.';
        }

        // Kontrola kompletnosti vlozenych dokumentu (musi byt vyrizene)
        if ( $kontrola = $this->kontrolaDokumentu($spis_info) ) {
            // nejsou kompletni - neprenasim
            foreach ($kontrola as $kmess) {
                Environment::getApplication()->getPresenter()->flashMessage('Spis '.$spis_info->nazev.' - '.$kmess,'warning');
            }
            return 'Spis '.$spis_info->nazev.' nelze přenést do spisovny! Jeden nebo více dokumentů spisu nejsou vyřízeny.';
        }

        // Prenest vsechny dokumenty do spisovny spolu se spisem
        $DokumentSpis = new DokumentSpis();
        $dokumenty = $DokumentSpis->dokumenty($spis_id, 1);
        if ( count($dokumenty)>0 ) {
            $Workflow = new Workflow();
            foreach ( $dokumenty as $dok ) {
                $stav = $Workflow->predatDoSpisovny($dok->id);
                if ( $stav === true ) {
                } else {
                    if ( is_string($stav) ) {
                        Environment::getApplication()->getPresenter()->flashMessage($stav,'warning');
                    }
                }                
            }
        }
        
        // Predat do spisovny
        $result = $this->zmenitStav($spis_id, 2);
        if ( $result ) {
            //$Log = new LogModel();
            //$Log->logDokument($dokument_id, LogModel::DOK_SPISOVNA_PREDAN, 'Dokument předán do spisovny.');
            return true;
        } else {
            return false;
        }

    }

    public function pripojitDoSpisovny($spis_id)
    {

        // kontrola uzivatele
        $spis_info = $this->getInfo($spis_id);

        //echo "<pre>"; print_r($dokument_info); echo "</pre>"; exit;

        // Test na uplnost dat
        if ( $kontrola = $this->kontrola($spis_info) ) {
            // nejsou kompletni data - neprenasim
            return 'Spis '.$spis_info->nazev.' nelze připojit do spisovny! Nejsou vyřízeny všechny potřebné údaje.';
        }

        // Kontrola stavu - uzavren = krome 1
        if ( $spis_info->stav != 2 ) {
            return 'Spis '.$spis_info->nazev .' nelze připojit do spisovny! Spis nebyl předán do spisovny.';
        }

        // Kontrola kompletnosti vlozenych dokumentu (musi byt vyrizene)
        if ( $kontrola = $this->kontrolaDokumentu($spis_info) ) {
            // nejsou kompletni - neprenasim
            foreach ($kontrola as $kmess) {
                Environment::getApplication()->getPresenter()->flashMessage('Spis '.$spis_info->nazev.' - '.$kmess,'warning');
            }
            return 'Spis '.$spis_info->nazev.' nelze připojit do spisovny! Jeden nebo více dokumentů spisu nejsou vyřízeny.';
        }
        
        // Pripojit vsechny dokumenty do spisovny spolu se spisem
        $DokumentSpis = new DokumentSpis();
        $dokumenty = $DokumentSpis->dokumenty($spis_id, 1);
        if ( count($dokumenty)>0 ) {
            $Workflow = new Workflow();
            foreach ( $dokumenty as $dok ) {
                $stav = $Workflow->pripojitDoSpisovny($dok->id);
                if ( $stav === true ) {
                } else {
                    if ( is_string($stav) ) {
                        Environment::getApplication()->getPresenter()->flashMessage($stav,'warning');
                    }
                }                
            }
        }        
        
        // Pripojit do spisovny
        $result = $this->zmenitStav($spis_id, 3);
        if ( $result ) {
            //$Log = new LogModel();
            //$Log->logDokument($dokument_id, LogModel::DOK_SPISOVNA_PREDAN, 'Dokument předán do spisovny.');
            return true;
        } else {
            return false;
        }

    }

    public function kontrola($data) {
        
        $mess = array();
        if ( empty($data->nazev) ) $mess[] = "Název spisu nemůže být prázdný!";
        if ( empty($data->spisovy_znak_id) ) $mess[] = "Spisový znak nemůže být prázdný!";
        if ( empty($data->skartacni_znak) ) $mess[] = "Skartační znak nemůže být prázdný!";
        if ( $data->skartacni_lhuta === "" ) $mess[] = "Skartační lhůta musí obsahovat hodnotu!";
        //if ( empty($data->datum_otevreni) ) $mess[] = "Spis nemá uveden datum otevření spisu!";
        //if ( empty($data->datum_uzavreni) ) $mess[] = "Spis nemá uveden datum uzavření spisu!";
        
        if ( count($mess)>0 ) {
            return $mess;
        } else {
            return null;
        }        
        
    }
    
    public function kontrolaDokumentu($data) {
        
        $mess = array();
        
        if ( is_numeric($data) ) {
            // $data je id
            $data = $this->getInfo($data);
        }
        
        $DokumentSpis = new DokumentSpis();
        $dokumenty = $DokumentSpis->dokumenty($data->id, 1);
        
        // stav nad 5 (4?)
        if ( count($dokumenty)>0 ) {
            foreach ( $dokumenty as $dok ) {
                if ( $dok->stav_dokumentu < 5 ) {
                    $mess[] = "Spis \"".$data->nazev."\" - Dokument \"".$dok->cislo_jednaci."\" není vyřízen.";
                }
            }
        }
        
        if ( count($mess)>0 ) {
            return $mess;
        } else {
            return null;
        }        
        
    }    

    public static function typSpisu($typ = null, $sklonovat = 0) {

        $typ_array1 = array('S'=>'spis',
                     'VS'=>'složka'
                     );
        $typ_array2 = array('S'=>'spisu',
                     'VS'=>'složky'
                     );

        $typ_array = ($sklonovat==1)?$typ_array2:$typ_array1;

        if ( is_null($typ) ) {
            return $typ_array;
        } else {
            return array_key_exists($typ, $typ_array)?$typ_array[$typ]:null;
        }


    }

    public static function stav($stav = null) {

        $stav_array = array('1'=>'otevřen',
                            '0'=>'uzavřen'/*spisovka*/,
                            '2'=>'uzavřen a předán do spisovny'/*spisovna*/,
                            '3'=>'uzavřen ve spisovně',
                            '4'=>'zápůjčka',
                            '5'=>'archivován',
                            '6'=>'skartován'
                     );

        if ( is_null($stav) ) {
            return $stav_array;
        } else {
            return array_key_exists($stav, $stav_array)?$stav_array[$stav]:null;
        }


    }

    public function  deleteAll() {

        $DokumentSpis = new DokumentSpis();
        $DokumentSpis->deleteAll();

        parent::deleteAll();
    }

}
