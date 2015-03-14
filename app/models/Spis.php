<?php

class Spis extends TreeModel
{

    protected $name = 'spis';
    protected $primary = 'id';
    
    
    public function getInfo($spis_id, $detail = false)
    {
        $result = $this->select(array(array('id=%i',$spis_id)));
        $row = $result->fetch();
        if (!$row)        
            throw new InvalidArgumentException("Spis id '$spis_id' neexistuje.");

        return $detail ? $this->spisDetail($row) : $row;
    }
    
    // Vrátí první spis z daným názvem, protože bohužel není zaručeno, že název spisu bude jedinečný
    public function findByName($spis_name)
    {
        $result = $this->select(array(array('nazev=%s', $spis_name)));
        $row = $result->fetch();
        return $row ? $row : null;
    }
    
    private function spisDetail($row)
    {
        $OrgJednotka = new Orgjednotka();
        
        $user = Nette\Environment::getUser();
        $user_id = $user->getIdentity()->id;
        
        if ( !empty($row->orgjednotka_id) ) {
            $row->orgjednotka_prideleno = $OrgJednotka->getInfo($row->orgjednotka_id);
        } else {
            $row->orgjednotka_prideleno = null;
        }
        if ( !empty($row->orgjednotka_id_predano) ) {
            $row->orgjednotka_predano = $OrgJednotka->getInfo($row->orgjednotka_id_predano);
        } else {
            $row->orgjednotka_predano = null;
        } 

        $query = array(        
            "SELECT w.* FROM {$this->tb_dokspis} AS ds INNER JOIN {$this->tb_workflow} AS w"
            . " ON ds.dokument_id = w.dokument_id WHERE ds.spis_id = %i",
            $row->id
        );
        
        $row->workflow = dibi::query($query)->fetchAll();

        return $row;        
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
        
        $params['leftJoin'] = array(
            'orgjednotka1' => array(
                'from' => array($this->tb_orgjednotka => 'org1'),
                'on' => array('org1.id=tb.orgjednotka_id'),
                'cols' => array('zkraceny_nazev'=>'orgjednotka_prideleno')                
            ),
            'orgjednotka2' => array(
                'from' => array($this->tb_orgjednotka => 'org2'),
                'on' => array('org2.id=tb.orgjednotka_id_predano'),
                'cols' => array('zkraceny_nazev'=>'orgjednotka_predano')                
            ),
            
        );
        
        return $this->nacti($parent_id, true, true, $params);

    }
    
    public function seznamDokumentu($spis_id)
    {
        if ( empty($spis_id) ) return null;
        if ( !is_array($spis_id) ) $spis_id = array($spis_id);
        
        $sql = array(
            'distinct'=>1,
            'from' => array($this->tb_dokspis => 'dokspis'),
            'cols' => array('spis_id','dokument_id','poradi'),
            'where' => array(array('dokspis.spis_id IN %in',$spis_id),'d.stav > 0'),
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_dokument => 'd'),
                    'on' => array('d.id=dokspis.dokument_id'),
                    'cols' => array('nazev','popis','cislo_jednaci','jid','poradi')
                ),
                'workflow' => array(
                    'from' => array($this->tb_workflow => 'wf'),
                    'on' => array('wf.dokument_id=d.id AND wf.aktivni=1 AND wf.stav_osoby=1'),
                    'cols' => array('stav_dokumentu','prideleno_id','orgjednotka_id')
                ),  
                'orgwf' => array(
                    'from' => array($this->tb_orgjednotka => 'orgwf'),
                    'on' => array('orgwf.id=wf.orgjednotka_id'),
                    'cols' => array('zkraceny_nazev'=>'orgjednotka_prideleno')
                ),   
                
            )
        );   
        
        $select = $this->selectComplex($sql);
        $result = $select->fetchAll();
        if ( count($result)>0 ) {
            $dokumenty = array();
            foreach ( $result as $dok ) {
                $dokumenty[ $dok->spis_id ][ $dok->dokument_id ] = $dok;
            }
            return $dokumenty;
        } else {
            return null;
        }
        
        
    }

    private function omezeni_org($args)
    {
        $user = Nette\Environment::getUser();
        $oj_id = Orgjednotka::dejOrgUzivatele();

        if ( $user->isAllowed('Dokument', 'cist_vse') )
            ; // vsechny spisy bez ohledu na organizacni jednotku
        else  if ($oj_id === null)
            $args['where'][] = array("0");
        else {           
            if ($user->isAllowed(NULL, 'is_vedouci'))
                $org_jednotky = Orgjednotka::childOrg($oj_id);
            else
                $org_jednotky = array($oj_id);

            if ( count($org_jednotky) > 1 )
                $args['where'][] =  array( 'tb.orgjednotka_id IN %in OR tb.orgjednotka_id_predano IN %in OR tb.orgjednotka_id IS NULL', $org_jednotky, $org_jednotky);
                
            else 
                $args['where'][] = array( 'tb.orgjednotka_id = %i OR tb.orgjednotka_id_predano = %i OR tb.orgjednotka_id IS NULL', $org_jednotky, $org_jednotky);
        }
        
        return $args;
    }
    
    private function spisovka_spisovna($args, $podminka)
    {
        $args['where'][] = $podminka;

        return $this->omezeni_org($args);
    }
    
    public function spisovka($args) {

        return $this->spisovka_spisovna($args, "NOT (tb.typ = 'S' AND tb.stav > 2)");
    }

    public function spisovna($args) {

        return $this->spisovka_spisovna($args, "NOT (tb.typ = 'S' AND tb.stav < 3)");
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
        $data['user_created'] = Nette\Environment::getUser()->getIdentity()->id;
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;
        
        $data['orgjednotka_id'] = OrgJednotka::dejOrgUzivatele();

        if ( !isset($data['parent_id']) ) $data['parent_id'] = 1;
        if ( empty($data['parent_id']) ) $data['parent_id'] = 1;
        if ( empty($data['spisovy_znak']) ) $data['spisovy_znak'] = '';
        if ( empty($data['datum_uzavreni']) ) $data['datum_uzavreni'] = null;
        if ( empty($data['datum_otevreni']) ) $data['datum_otevreni'] = null;
        
        if ( empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = 10;
        if ( !empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = (int) $data['skartacni_lhuta'];

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

        $Log = new LogModel();
        $Log->logSpis($spis_id, 41);
        
        return $spis_id;

    }

    public function upravit($data,$spis_id) {

        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;

        if ( empty($data['spousteci_udalost_id']) ) $data['spousteci_udalost_id'] = null;
        if ( empty($data['spisovy_znak_id']) ) $data['spisovy_znak_id'] = null;
        if ( !isset($data['parent_id']) ) $data['parent_id'] = 1;
        if ( empty($data['parent_id']) ) $data['parent_id'] = 1;
        if ( !isset($data['parent_id_old']) ) $data['parent_id_old'] = 1;
        if ( empty($data['parent_id_old']) ) $data['parent_id_old'] = 1;

        if ( empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = 10;
        if ( !empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = (int) $data['skartacni_lhuta'];
        if ( empty($data['datum_otevreni']) ) $data['datum_otevreni'] = null;
        if ( empty($data['datum_uzavreni']) ) $data['datum_uzavreni'] = null;

        $Log = new LogModel();

        try {
            $this->upravitH($data, $spis_id);
            $Log->logSpis($spis_id, 42);
        }
        catch (Exception $e) {
            $Log->logSpis($spis_id, 48,'Hodnoty spisu se nepodarilo upravit.');
            throw $e;
        }
    }
    
    public function zmenitStav($spis_id, $stav) {

        if ( !is_numeric($spis_id) || !is_numeric($stav) ) return null;

        if ( $stav != 1 ) {
            // Kontrola
            if ( $kontrola = $this->kontrolaDokumentu($spis_id) ) {
                foreach ($kontrola as $kmess) {
                    Nette\Environment::getApplication()->getPresenter()->flashMessage($kmess,'warning');
                }                
                return -1;
            }
        }
        
        $data = array();
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;
        $data['stav'] = $stav;

        $Log = new LogModel();
        try {
            $this->update($data, array('id=%i',$spis_id));
            
            if ( $stav == 1 ) {
                $Log->logSpis($spis_id, 46);
            } else {
                $Log->logSpis($spis_id, 47);
            }
            
            return true;
        } catch (exception $e) {
            $Log->logSpis($spis_id, 48, 'Nepodařilo se změnit stav spisu.');
            return false;
        }

    }

    public function zmenitOrg($spis_id, $orgjednotka_id) 
    {
        
        if ( empty($spis_id) ) return false;
        if ( empty($orgjednotka_id) ) return false;
        
        try {
            $this->update(
                    array('orgjednotka_id'=>$orgjednotka_id,'orgjednotka_id_predano'=>null),
                    array( array('id=%i',$spis_id) )
                    );
            return true;
        } catch (Exception $e) {
            return false;
        }
        
    }

    public function predatOrg($spis_id, $orgjednotka_id) 
    {
        
        if ( empty($spis_id) ) return false;
        if ( empty($orgjednotka_id) ) return false;
        
        try {
            $this->update(
                    array('orgjednotka_id_predano'=>$orgjednotka_id),
                    array( array('id=%i',$spis_id) )
                    );
            return true;
        } catch (Exception $e) {
            return false;
        }
        
    }
    
    public function zrusitPredani($spis_id) 
    {
        
        if ( empty($spis_id) ) return false;
        
        try {
            $this->update(
                    array('orgjednotka_id_predano'=>null),
                    array( array('id=%i',$spis_id) )
                    );
            return true;
        } catch (Exception $e) {
            return false;
        }
        
    }    
    
    
    public static function spisovyZnak( $spis, $simple = 0 )
    {

        $user_config = Nette\Environment::getVariable('user_config');
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

    public function kontrolaSpisovyZnak( $spisovy_znak, $spis_parent_id )
    {

        // zjistime rodice
        $spis_parent = $this->getInfo($spis_parent_id);

        // sestavime plneurceny spisovy znak
        $spisovy_znak_plneurceny = $spis_parent->spisovy_znak_plneurceny .'.'. $spisovy_znak;

        // vyhledame, zda existuje
        $spis_exist = $this->select(array(array("spisovy_znak_plneurceny=%s",$spisovy_znak_plneurceny)), null, null, 1);
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
                $spis_exist = $this->select(array(array("spisovy_znak_plneurceny=%s",$spisovy_znak_plneurceny)), null, null, 1);
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
                Nette\Environment::getApplication()->getPresenter()->flashMessage('Spis '.$spis_info->nazev.' - '.$kmess,'warning');
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
                Nette\Environment::getApplication()->getPresenter()->flashMessage('Spis '.$spis_info->nazev.' - '.$kmess,'warning');
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
                        Nette\Environment::getApplication()->getPresenter()->flashMessage($stav,'warning');
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
                Nette\Environment::getApplication()->getPresenter()->flashMessage('Spis '.$spis_info->nazev.' - '.$kmess,'warning');
            }
            return 'Spis '.$spis_info->nazev.' nelze připojit do spisovny! Jeden nebo více dokumentů spisu nejsou vyřízeny.';
        }
        
        // Pripojit vsechny dokumenty do spisovny spolu se spisem
        $DokumentSpis = new DokumentSpis();
        $dokumenty = $DokumentSpis->dokumenty($spis_id, 1);
        if ( count($dokumenty)>0 ) {
            $Workflow = new Workflow();
            foreach ( $dokumenty as $dok ) {
                $stav = $Workflow->prevzitDoSpisovny($dok->id);
                if ( $stav === true ) {
                } else {
                    if ( is_string($stav) ) {
                        Nette\Environment::getApplication()->getPresenter()->flashMessage($stav,'warning');
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
        
        $LogModel = new LogModel();
        $LogModel->deleteAllSpis();

        parent::deleteAll();
    }

    // $spis - informace, ktere vratilo getInfo
    public static function zjistiOpravneniUzivatele($spis) {

        $user = Nette\Environment::getUser();
        $user_id = $user->getIdentity()->id;  
        $oj_uzivatele = OrgJednotka::dejOrgUzivatele();
        $Lze_cist = $Lze_menit = $Lze_prevzit = false;
        
        if ($oj_uzivatele === null)
            $org_jednotky = array();
        else if ($user->isAllowed(NULL, 'is_vedouci'))
            $org_jednotky = Orgjednotka::childOrg($oj_uzivatele);
        else
            $org_jednotky = array($oj_uzivatele);
        
        // prideleno
        if ( in_array($spis->orgjednotka_id, $org_jednotky) ) {
            $Lze_menit = true;
            $Lze_cist = true;
        }
        // predano
        if ( $oj_uzivatele !== null && $spis->orgjednotka_id_predano == $oj_uzivatele) {
            $Lze_prevzit = true;
            $Lze_cist = true;
        }
                
        // Oprava ticket #194
        // Mohou nastat situace, kdy spis nema zadneho vlastnika, napr. po migraci ze spisovky 2
        // V tom pripade musi byt videt seznam dokumentu ve spisu
        if (!$spis->orgjednotka_id && !$spis->orgjednotka_id_predano)
            $Lze_cist = 1;

        if ($user->isAllowed('Dokument', 'cist_vse'))
            $Lze_cist = 1;
        
        if ( count($spis->workflow) > 0 ) {
            $org_cache = array();
            foreach ( $spis->workflow as $wf ) {
                
                if ( isset( $org_cache[$wf->orgjednotka_id] ) ) {
                    $orgjednotka_expr = $org_cache[$wf->orgjednotka_id];
                } else {
                    $orgjednotka_expr = Orgjednotka::isInOrg($wf->orgjednotka_id);
                    $org_cache[$wf->orgjednotka_id] = $orgjednotka_expr;
                }
                
                if ( !$Lze_cist )
                    if ( ($wf->prideleno_id == $user_id || $orgjednotka_expr)
                         && $wf->stav_osoby < 100 ) {
                        // uzivatel vlastnil nejaky dokument ve spisu v minulosti
                        $Lze_cist = 1;
                    }   
                
                if ( !$Lze_menit )
                    if ( ($wf->prideleno_id == $user_id || $orgjednotka_expr)
                        && ($wf->stav_osoby == 1 && $wf->aktivni == 1 ) ) {
                        // uzivatel vlastni nejaky dokument ve spisu ted
                        $Lze_menit = 1;
                    }   

                if ( !$Lze_prevzit )
                    if ( ($wf->prideleno_id == $user_id || $orgjednotka_expr)
                        && ($wf->stav_osoby == 0 && $wf->aktivni == 1 ) ) {
                        $Lze_prevzit = 1;
                    }   
                
                if ( $Lze_prevzit && $Lze_menit && $Lze_cist ) {
                    break;
                }
            }
        }

        // 2 = predan do spisovny
        if ( $spis->stav == 2)
            $Lze_menit = false;

        if ( $user->isInRole('superadmin') ) {
            $Lze_cist = 1;
            $Lze_menit = 1;
        }

        return array('lze_cist' => $Lze_cist,
                    'lze_menit' => $Lze_menit, 
                    'lze_prevzit' => $Lze_prevzit);
    
    }
    
}
