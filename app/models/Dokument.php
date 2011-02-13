<?php

class Dokument extends BaseModel
{

    protected $name = 'dokument';
    protected $primary = 'id';

    protected $tb_dokumenttyp = 'dokument_typ';
    protected $tb_workflow = 'workflow';
    protected $tb_dokspis = 'dokument_to_spis';
    protected $tb_spis = 'spis';
    protected $tb_dok_subjekt = 'dokument_to_subjekt';
    protected $tb_subjekt = 'subjekt';
    protected $tb_dok_file = 'dokument_to_file';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_dokumenttyp = $prefix . $this->tb_dokumenttyp;
        $this->tb_workflow = $prefix . $this->tb_workflow;
        $this->tb_dokspis = $prefix . $this->tb_dokspis;
        $this->tb_spis = $prefix . $this->tb_spis;
        $this->tb_dok_subjekt = $prefix . $this->tb_dok_subjekt;
        $this->tb_subjekt = $prefix . $this->tb_subjekt;
        $this->tb_dok_file = $prefix . $this->tb_dok_file;

    }


    /**
     * Seznam dokumentu s zivotnim cyklem
     * 
     * @param <type> $args 
     */
    public function seznam($args = array(), $detail = 0) {

        if ( isset($args['where']) ) {
            $where = $args['where'];
        } else {
            $where = null;
        }
        if ( isset($args['where_or']) ) {
            $where_or = $args['where_or'];
        } else {
            $where_or = null;
        }
        
        if ( isset($args['order']) ) {
            $order = $args['order'];
        } else {
            //$order = array('dokument_id'=>'DESC');
            $order = array('podaci_denik_rok'=>'DESC','podaci_denik_poradi'=>'DESC');
        } 
        if ( isset($args['limit']) ) {
            $limit = $args['limit'];
        } else {
            $limit = null;
        }
        if ( isset($args['offset']) ) {
            $offset = $args['offset'];
        } else {
            $offset = null;
        }
        
        $sql = array(
        
            'distinct'=>1,
            'from' => array($this->tb_workflow => 'wf'),
            'cols' => array('wf.dokument_id','wf.dokument_id'=>'id'),
            'where' => $where,
            'where_or' => $where_or,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->name => 'd'),
                    'on' => array('d.id=wf.dokument_id'),
                    'cols' => null
                ),
                'dokspisy' => array(
                    'from' => array($this->tb_dokspis => 'sp'),
                    'on' => array('sp.dokument_id=wf.dokument_id'),
                    'cols' => null
                ),
                'spisy' => array(
                    'from' => array($this->tb_spis => 'spis'),
                    'on' => array('spis.id=sp.spis_id'),
                    'cols' => null
                ),
                'typ_dokumentu' => array(
                    'from' => array($this->tb_dokumenttyp => 'dtyp'),
                    'on' => array('dtyp.id=d.typ_dokumentu_id'),
                    'cols' => null
                )                
                
            )
        
        );

        if ( isset($args['leftJoin']) ) {
            $sql['leftJoin'] = array_merge($sql['leftJoin'],$args['leftJoin']);
        }

        //echo "<pre>";
        //print_r($sql);
        //exit;
        //echo "</pre>";

        $select = $this->fetchAllComplet($sql);
        
        if ( $detail == 1 ) {
            // return array(DibiRow)
            $result = $select->fetchAll();
            if ( count($result)>0 ) {
                $tmp = array();
                $DokumentySpis = new DokumentSpis();
                $DokumentySubjekt = new DokumentSubjekt();
                $DokumentyPrilohy = new DokumentPrilohy();
                $Workflow = new Workflow();
                $Osoba = new UserModel();                
                
                foreach ($result as $index => $row) {

                    $dok = $this->getInfo($row->id,null,1);
                    
                    $dok->typ_dokumentu = Dokument::typDokumentu($dok->typ_dokumentu_id);
                    $dok->subjekty = $DokumentySubjekt->subjekty($dok->id);
                    $dok->prilohy = $DokumentyPrilohy->prilohy($dok->id);
                    $dok->spisy = $DokumentySpis->spisy($dok->id);

                    $dok->workflow = $Workflow->dokument($dok->id);
                    $dok->prideleno = null;
                    $dok->predano = null;
                    $prideleno = $predano = $stav = 0;
                    if ( count($dok->workflow)>0 ) {
                        foreach ($dok->workflow as $wf) {

                            // Pridelen
                            if ( $wf->stav_osoby == 1 && $prideleno==0 ) {
                                $dok->prideleno = $wf;
                                $prideleno=1;
                                }
                            // Predan
                            if ( $wf->stav_osoby == 0 && $predano==0 ) {
                                $dok->predano = $wf;
                                $predano=1;
                            }
                            // Stav
                            if ( $stav <= $wf->stav_dokumentu ) {
                                $stav = $wf->stav_dokumentu;
                            }
                        }
                    }
                    $dok->stav_dokumentu = $stav;

                    $dok->lhuta_stav = 0;
                    if ( !empty($dok->lhuta) ) {
                        $datum_vzniku = strtotime($dok->date_created);
                        $dok->lhuta_do = $datum_vzniku + ($dok->lhuta * 86400);
                        $rozdil = $dokument->lhuta_do - time();
                        if ( $rozdil < 0 ) {
                            $dok->lhuta_stav = 2;
                        } else if ( $rozdil <= 432000 ) {
                            $dok->lhuta_stav = 1;
                        }
                    } else {
                        $dok->lhuta_do = 'neurčeno';
                    }
                    if ( $stav > 3 ) {
                        $dok->lhuta_stav = 0;
                    }

                    $tmp[ $dok->id ] = $dok;


                
                }
                return $tmp;
            } else {
                return null;
            }
            
        } else {
            // return DibiResult
            return $select;
        }
        
        
        

    }

    /**
     * Seznam dokumentu bez zivotniho cyklu
     *
     * @param <type> $args
     * @return <type>
     */
    public function seznamKlasicky($args = null)
    {

        if ( isset($args['where']) ) {
            $where = $args['where'];
        } else {
            $where = array(array('stav<100'));
        }

        if ( isset($args['order']) ) {
            $order = $args['order'];
        } else {
            $order = array('podaci_denik_rok'=>'DESC','podaci_denik_poradi'=>'DESC');
        }

        if ( isset($args['offset']) ) {
            $offset = $args['offset'];
        } else {
            $offset = null;
        }

        if ( isset($args['limit']) ) {
            $limit = $args['limit'];
        } else {
            $limit = null;
        }


        $select = $this->fetchAll($order,$where,$offset,$limit);

        $rows = $select->fetchAll();

        return ($rows) ? $rows : NULL;

    }

    public function hledat($query, $typ = 'zakladni') {

        $args = array(
            'where_or' => array(
                array('d.nazev LIKE %s','%'.$query.'%'),
                array('d.popis LIKE %s','%'.$query.'%'),
                array('d.cislo_jednaci LIKE %s','%'.$query.'%'),
                array('d.jid LIKE %s','%'.$query.'%')
            )
        );
        return $args;

    }

    public function seradit(&$args, $typ = null) {

        switch ($typ) {
            case 'stav':
                $args['order'] = array('wf.stav_dokumentu');
                break;
            case 'stav_desc':
                $args['order'] = array('wf.stav_dokumentu'=>'DESC');
                break;
            case 'cj':
                $args['order'] = array('podaci_denik_rok','podaci_denik_poradi');
                break;
            case 'cj_desc':
                $args['order'] = array('podaci_denik_rok'=>'DESC','podaci_denik_poradi'=>'DESC');
                break;
            case 'vec':
                $args['order'] = array('d.nazev');
                break;
            case 'vec_desc':
                $args['order'] = array('d.nazev'=>'DESC');
                break;
            case 'dvzniku':
                $args['order'] = array('d.datum_vzniku','podaci_denik_rok','podaci_denik_poradi');
                break;
            case 'dvzniku_desc':
                $args['order'] = array('d.datum_vzniku'=>'DESC','podaci_denik_rok'=>'DESC','podaci_denik_poradi'=>'DESC');
                break;
            case 'prideleno':
                //$args['order'] = array('podaci_denik_rok'=>'DESC','podaci_denik_poradi'=>'DESC');
                break;
            default:
                break;
        }
        return $args;

    }

    public function filtr($nazev = null, $params = null) {

        if ( is_null($nazev) && is_null($params) ) {
            return null;
        } else if ( is_null($nazev) ) {
            return $this->paramsFiltr($params);
        } else {
            return $this->fixedFiltr($nazev, $params);
        }
    }

    private function paramsFiltr($params) {

        $args = array();

        //Debug::dump($params);

        if ( isset($params['nazev']) ) {
            if ( !empty($params['nazev']) ) {
                $args['where'][] = array('d.nazev LIKE %s','%'.$params['nazev'].'%');
            }
        }
        if ( isset($params['popis']) ) {
            if ( !empty($params['popis']) ) {
                $args['where'][] = array('d.popis LIKE %s','%'.$params['popis'].'%');
            }
        }
        if ( isset($params['cislo_jednaci']) ) {
            if ( !empty($params['cislo_jednaci']) ) {
                $args['where'][] = array('d.cislo_jednaci LIKE %s','%'.$params['cislo_jednaci'].'%');
            }
        }
        if ( isset($params['spisova_znacka']) ) {
            if ( !empty($params['spisova_znacka']) ) {
                $args['where'][] = array('spis.nazev LIKE %s','%'.$params['spisova_znacka'].'%');
            }
        }
        if ( isset($params['typ_dokumentu_id']) ) {
            if ( $params['typ_dokumentu_id'] != '0' ) {
                $args['where'][] = array('d.typ_dokumentu_id = %i',$params['typ_dokumentu_id']);
            }
        }
        if ( isset($params['cislo_jednaci_odesilatele']) ) {
            if ( !empty($params['cislo_jednaci_odesilatele']) ) {
                $args['where'][] = array('d.cislo_jednaci_odesilatele LIKE %s','%'.$params['cislo_jednaci_odesilatele'].'%');
            }
        }
        if ( isset($params['datum_vzniku']) ) {
            if ( !empty($params['datum_vzniku']) ) {

                $cas = '';
                if ( isset($params['datum_vzniku_cas']) ) {
                    if ( !empty($params['datum_vzniku_cas']) ) {
                        $cas = ' '. $params['datum_vzniku_cas'];
                    }
                }

                $args['where'][] = array('d.datum_vzniku = %d',$params['datum_vzniku'].$cas);
            }
        }
        if ( isset($params['pocet_listu']) ) {
            if ( !empty($params['pocet_listu']) ) {
                $args['where'][] = array('d.pocet_listu = %i',$params['pocet_listu']);
            }
        }
        if ( isset($params['pocet_priloh']) ) {
            if ( !empty($params['pocet_priloh']) ) {
                $args['where'][] = array('d.pocet_priloh = %i',$params['pocet_priloh']);
            }
        }
        if ( isset($params['stav_dokumentu']) ) {
            if ( !empty($params['stav_dokumentu']) ) {
                $args['where'][] = array('wf.stav_dokumentu = %i',$params['stav_dokumentu']);
            }
        }
        if ( isset($params['lhuta']) ) {
            if ( !empty($params['lhuta']) ) {
                $args['where'][] = array('d.lhuta = %i',$params['lhuta']);
            }
        }
        if ( isset($params['poznamka']) ) {
            if ( !empty($params['poznamka']) ) {
                $args['where'][] = array('d.poznamka LIKE %s','%'.$params['poznamka'].'%');
            }
        }
        if ( isset($params['zpusob_vyrizeni_id']) ) {
            if ( !empty($params['zpusob_vyrizeni_id']) || $params['zpusob_vyrizeni_id'] != '0' ) {
                $args['where'][] = array('d.zpusob_vyrizeni_id = %i',$params['zpusob_vyrizeni_id']);
            }
        }
        if ( isset($params['datum_vyrizeni']) ) {
            if ( !empty($params['datum_vyrizeni']) ) {

                $cas = '';
                if ( isset($params['datum_vyrizeni_cas']) ) {
                    if ( !empty($params['datum_vyrizeni_cas']) ) {
                        $cas = ' '. $params['datum_vyrizeni_cas'];
                    }
                }

                $args['where'][] = array('d.datum_vyrizeni = %d',$params['datum_vyrizeni'].$cas);
            }
        }
        if ( isset($params['datum_odeslani']) ) {
            if ( !empty($params['datum_odeslani']) ) {

                $cas = '';
                if ( isset($params['datum_odeslani_cas']) ) {
                    if ( !empty($params['datum_odeslani_cas']) ) {
                        $cas = ' '. $params['datum_odeslani_cas'];
                    }
                }

                //$args['where'][] = array('d.datum_odeslani = %d',$params['datum_odeslani'].$cas);
            }
        }
        if ( isset($params['spisovy_znak_id']) ) {
            if ( !empty($params['spisovy_znak_id']) ) {
                //$args['where'][] = array();
            }
        }
        if ( isset($params['ulozeni_dokumentu']) ) {
            if ( !empty($params['ulozeni_dokumentu']) ) {
                $args['where'][] = array('d.ulozeni_dokumentu LIKE %s','%'.$params['ulozeni_dokumentu'].'%');
            }
        }
        if ( isset($params['poznamka_vyrizeni']) ) {
            if ( !empty($params['poznamka_vyrizeni']) ) {
                $args['where'][] = array('d.poznamka_vyrizeni LIKE %s','%'.$params['poznamka_vyrizeni'].'%');
            }
        }
        if ( isset($params['skartacni_znak']) ) {
            if ( !empty($params['skartacni_znak']) ) {
                $args['where'][] = array('d.skartacni_znak = %s',$params['skartacni_znak']);
            }
        }
        if ( isset($params['spousteci_udalost']) ) {
            if ( !empty($params['spousteci_udalost']) ) {
                $args['where'][] = array('d.spousteci_udalost = %s',$params['spousteci_udalost']);
            }
        }
        if ( isset($params['vyrizeni_pocet_listu']) ) {
            if ( !empty($params['vyrizeni_pocet_listu']) ) {
                $args['where'][] = array('d.vyrizeni_pocet_listu = %i',$params['vyrizeni_pocet_listu']);
            }
        }
        if ( isset($params['vyrizeni_pocet_priloh']) ) {
            if ( !empty($params['vyrizeni_pocet_priloh']) ) {
                $args['where'][] = array('d.vyrizeni_pocet_priloh = %i',$params['vyrizeni_pocet_priloh']);
            }
        }
        if ( isset($params['subjekt_type']) ) {
            if ( !empty($params['subjekt_type']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.type = %s',$params['subjekt_type']);
            }
        }
        if ( isset($params['subjekt_nazev']) ) {
            if ( !empty($params['subjekt_nazev']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array(
                    's.nazev_subjektu LIKE %s OR','%'.$params['subjekt_nazev'].'%',
                    "CONCAT(s.jmeno,' ',s.prijmeni) LIKE %s OR",'%'.$params['subjekt_nazev'].'%',
                    "CONCAT(s.prijmeni,' ',s.jmeno) LIKE %s",'%'.$params['subjekt_nazev'].'%'
                );
            }
        }
        if ( isset($params['subjekt_ic']) ) {
            if ( !empty($params['subjekt_ic']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.ic LIKE %s','%'.$params['ic'].'%');
            }
        }
        if ( isset($params['adresa_ulice']) ) {
            if ( !empty($params['adresa_ulice']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.adresa_ulice LIKE %s','%'.$params['adresa_ulice'].'%');
            }
        }
        if ( isset($params['adresa_cp']) ) {
            if ( !empty($params['adresa_cp']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.adresa_cp LIKE %s','%'.$params['adresa_cp'].'%');
            }
        }
        if ( isset($params['adresa_co']) ) {
            if ( !empty($params['adresa_co']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.adresa_co LIKE %s','%'.$params['adresa_co'].'%');
            }
        }
        if ( isset($params['adresa_mesto']) ) {
            if ( !empty($params['adresa_mesto']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.adresa_mesto LIKE %s','%'.$params['adresa_mesto'].'%');
            }
        }
        if ( isset($params['adresa_psc']) ) {
            if ( !empty($params['adresa_psc']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.adresa_psc LIKE %s','%'.$params['adresa_psc'].'%');
            }
        }
        if ( isset($params['adresa_stat']) ) {
            if ( !empty($params['adresa_stat']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.adresa_stat = %s',$params['adresa_stat']);
            }
        }
        if ( isset($params['subjekt_email']) ) {
            if ( !empty($params['subjekt_email']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.email LIKE %s','%'.$params['subjekt_email'].'%');
            }
        }
        if ( isset($params['subjekt_telefon']) ) {
            if ( !empty($params['subjekt_telefon']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.telefon LIKE %s','%'.$params['subjekt_telefon'].'%');
            }
        }
        if ( isset($params['subjekt_isds']) ) {
            if ( !empty($params['subjekt_isds']) ) {

                $args['leftJoin']['dok_subjekt'] = array(
                    'from'=> array($this->tb_dok_subjekt => 'ds'),
                    'on' => array('ds.dokument_id=d.id'),
                    'cols' => null
                );
                $args['leftJoin']['subjekt'] = array(
                    'from'=> array($this->tb_subjekt => 's'),
                    'on' => array('s.id=ds.subjekt_id'),
                    'cols' => null
                );
                $args['where'][] = array('s.id_isds LIKE %s','%'.$params['subjekt_isds'].'%');
            }
        }
        if ( isset($params['prideleno']) ) {
            if ( count($params['prideleno'])>0 && is_array($params['prideleno']) ) {
                $args['where'][] = array('wf.prideleno IN (%in) AND wf.stav_osoby=1 AND wf.aktivni=1',$params['prideleno']);
            }
        }
        if ( isset($params['predano']) ) {
            if ( count($params['predano'])>0 && is_array($params['predano']) ) {
                $args['where'][] = array('wf.prideleno IN (%in) AND wf.stav_osoby=0 AND wf.aktivni=1',$params['predano']);
            }
        }
        if ( isset($params['prideleno_org']) ) {
            if ( count($params['prideleno_org'])>0 && is_array($params['prideleno_org']) ) {
                $args['where'][] = array('wf.orgjednotka_id IN (%in) AND wf.stav_osoby=1 AND wf.aktivni=1',$params['prideleno_org']);
            }
        }
        if ( isset($params['predano_org']) ) {
            if ( count($params['predano_org'])>0 && is_array($params['predano_org']) ) {
                $args['where'][] = array('wf.orgjednotka_id IN (%in) AND wf.stav_osoby=0 AND wf.aktivni=1',$params['predano_org']);
            }
        }

        //Debug::dump($args); exit;

        return $args;

    }

    private function fixedFiltr($nazev, $params = null) {

        $user = Environment::getUser()->getIdentity();
        $isVedouci = Environment::getUser()->isAllowed(NULL, 'is_vedouci');
        $org_jednotka = null;

        switch ($nazev) {
            case 'org':
                if ( $isVedouci ) {
                    if ( @count( $user->user_roles )>0 ) {
                        foreach ( $user->user_roles as $role ) {
                            if (preg_match('/^vedouci/', $role->code) ) {
                                $org_jednotka = $role->orgjednotka_id;
                            }
                        }
                    }
                }
                $args = array(
                    'where' => array( array('wf.orgjednotka_id=%i',$org_jednotka),array('wf.stav_osoby=0 OR wf.stav_osoby=1 OR wf.stav_osoby=2'), array('wf.aktivni=1') )
                );
                break;
            case 'moje':
                if ( $isVedouci ) {
                    if ( @count( $user->user_roles )>0 ) {
                        foreach ( $user->user_roles as $role ) {
                            if (preg_match('/^vedouci/', $role->code) ) {
                                $org_jednotka = $role->orgjednotka_id;
                            }
                        }
                    }
                }
                if ( $org_jednotka ) {
                    $args = array(
                        'where' => array(
                            array('(wf.prideleno=%i',$user->id, ') OR (wf.prideleno IS NULL AND wf.orgjednotka_id=%i)',$org_jednotka),
                            array('wf.stav_osoby=0 OR wf.stav_osoby=1 OR wf.stav_osoby=2'),
                            array('wf.aktivni=1') )
                    );
                } else {
                    $args = array(
                        'where' => array(
                            array('wf.prideleno=%i',$user->id,),
                            array('wf.stav_osoby=0 OR wf.stav_osoby=1 OR wf.stav_osoby=2'),
                            array('wf.aktivni=1') )
                    );
                }

                break;
            case 'predane':
                $args = array(
                    'where' => array( array('wf.prideleno=%i',$user->id),array('wf.stav_osoby=0'), array('wf.aktivni=1') )
                );
                break;
            case 'pracoval':
                $args = array(
                    'where' => array( array('wf.prideleno=%i',$user->id),array('wf.stav_osoby < 100') )
                );
                break;
            case 'moje_nove':
                $args = array(
                    'where' => array( array('wf.prideleno=%i',$user->id),array('wf.stav_osoby = 1'), array('wf.stav_dokumentu = 1'), array('wf.aktivni=1') )
                );
                break;
            case 'vsichni_nove':
                $args = array(
                    'where' => array( array('wf.stav_dokumentu = 1'), array('wf.aktivni=1') )
                );
                break;
            case 'moje_vyrizuje':
                $args = array(
                    'where' => array( array('wf.prideleno=%i',$user->id),array('wf.stav_osoby = 1'), array('wf.stav_dokumentu = 3'), array('wf.aktivni=1') )
                );
                break;
            case 'vsichni_vyrizuji':
                $args = array(
                    'where' => array( array('wf.stav_dokumentu = 3'), array('wf.aktivni=1') )
                );
                break;
            case 'vse':
                $args = array(
                    'where' => array( array('1') )
                );
                break;
            default:
                $args = array(
                    'where' => array( array('0') )
                );
                break;
        }

        return $args;

    }

    public function getInfo($dokument_id,$dokument_version = null, $detail = 0) {
     
        $sql = array(
        
            'distinct'=>null,
            'from' => array($this->name => 'dok'),
            'cols' => array('*','id'=>'dokument_id','version'=>'dokument_version'),
            'leftJoin' => array(
                'dokspisy' => array(
                    'from' => array($this->tb_dokspis => 'sp'),
                    'on' => array('sp.dokument_id=dok.id'),
                    'cols' => array('poradi'=>'poradi_spisu','stav'=>'stav_spisu')
                ),
                'typ_dokumentu' => array(
                    'from' => array($this->tb_dokumenttyp => 'dtyp'),
                    'on' => array('dtyp.id=dok.typ_dokumentu_id'),
                    'cols' => array('nazev'=>'typ_nazev','popis'=>'typ_popis','smer'=>'typ_smer','typ'=>'typ_typ')
                ),
                'workflow' => array(
                    'from' => array($this->tb_workflow => 'wf'),
                    'on' => array('wf.dokument_id=dok.id'),
                    'cols' => array('id'=>'workflow_id','stav_dokumentu','prideleno','prideleno_info','orgjednotka_id','orgjednotka_info',
                                    'stav_osoby','date'=>'date_prideleni','date_predani','poznamka'=>'poznamka_predani','aktivni'=>'wf_aktivni')
                ),
                'spisy' => array(
                    'from' => array($this->tb_spis => 'spis'),
                    'on' => array('spis.id=sp.spis_id'),
                    'cols' => array('id'=>'spis_id','nazev'=>'nazev_spisu','popis'=>'popis_spisu')
                )
                
            ),
            'order_by' => array('dok.id'=>'DESC','dok.version'=>'DESC')
        
        );
        
        if ( !is_null($dokument_version) ) {
            $sql['where'] = array( array('dok.id=%i',$dokument_id),array('dok.version=%i',$dokument_version) );
        } else {
            $sql['where'] = array( array('dok.id=%i',$dokument_id) );
        }
        
        $select = $this->fetchAllComplet($sql);
        $result = $select->fetchAll();
        if ( count($result)>0 ) {

            $tmp = array();
            $dokument_id = $dokument_version = 0;
            foreach ($result as $index => $row) {
                $id = $row->id;
                $v = $row->version;

                $spis = new stdClass();
                $spis->id = $row->spis_id; unset($row->spis_id);
                $spis->nazev = $row->nazev_spisu; unset($row->nazev_spisu);
                $spis->popis = $row->popis_spisu; unset($row->popis_spisu);
                $spis->stav = $row->stav_spisu; unset($row->stav_spisu);
                $spis->poradi = $row->poradi_spisu; unset($row->poradi_spisu);
                $tmp[$id][$v]['spisy'][ $spis->id ] = $spis;

                $typ = new stdClass();
                $typ->id = $row->typ_dokumentu_id; unset($row->typ_dokumentu_id);
                $typ->nazev = $row->typ_nazev; unset($row->typ_nazev);
                $typ->popis = $row->typ_popis; unset($row->typ_popis);
                $typ->smer = $row->typ_smer; unset($row->typ_smer);
                $typ->typ = $row->typ_typ; unset($row->typ_typ);
                $tmp[$id][$v]['typ_dokumentu'] = $typ;

                $tmp[$id][$v]['typ_dokumentu'] = $typ;
                $workflow = new stdClass();
                $workflow->id = $row->workflow_id; unset($row->workflow_id);
                $workflow->stav_dokumentu = $row->stav_dokumentu; unset($row->stav_dokumentu);
                $workflow->prideleno = $row->prideleno; unset($row->prideleno);
                $workflow->prideleno_info = unserialize($row->prideleno_info); unset($row->prideleno_info);
                $workflow->prideleno_jmeno = Osoba::displayName($workflow->prideleno_info);
                $workflow->stav_osoby = $row->stav_osoby; unset($row->stav_osoby);
                $workflow->orgjednotka_id = $row->orgjednotka_id; unset($row->orgjednotka_id);
                $workflow->orgjednotka_info = unserialize($row->orgjednotka_info); unset($row->orgjednotka_info);
                $workflow->date_predani = $row->date_predani; unset($row->date_predani);
                $workflow->date = $row->date_prideleni; unset($row->date_prideleni);
                $workflow->poznamka = $row->poznamka_predani; unset($row->poznamka_predani);
                $workflow->aktivni = $row->wf_aktivni; unset($row->wf_aktivni);
                $tmp[$id][$v]['workflow'][ $workflow->id ] = $workflow;

                $tmp[$id][$v]['raw'] = $row;
                
                

                if ( $row->id >= $dokument_id ) $dokument_id = $row->id;
                if ( $row->version >= $dokument_version ) $dokument_version = $row->version;

            }

            $dokument = $tmp[ $dokument_id ][$dokument_version]['raw'];
            $dokument->typ_dokumentu = $tmp[ $dokument_id ][$dokument_version]['typ_dokumentu'];
            $dokument->spisy = $tmp[ $dokument_id ][$dokument_version]['spisy'];
            $dokument->workflow = $tmp[ $dokument_id ][$dokument_version]['workflow'];

            $DokSubjekty = new DokumentSubjekt();
            $dokument->subjekty = $DokSubjekty->subjekty($dokument_id);
            $Dokrilohy = new DokumentPrilohy();
            $dokument->prilohy = $Dokrilohy->prilohy($dokument_id,null,$detail);

            $DokOdeslani = new DokumentOdeslani();
            $dokument->odeslani = $DokOdeslani->odeslaneZpravy($dokument_id);

            $dokument->prideleno = null;
            $dokument->predano = null;
            $prideleno = $predano = $stav = 0;
            if ( count($dokument->workflow)>0 ) {
                foreach ($dokument->workflow as $wf) {
                    // Pridelen
                    if ( ($wf->stav_osoby == 1 && $wf->aktivni == 1 ) && $prideleno==0 ) {
                        $dokument->prideleno = $wf;
                        $prideleno=1;
                    }
                    // Predan
                    if ( ($wf->stav_osoby == 0 && $wf->aktivni == 1 ) && $predano==0 ) {
                        $dokument->predano = $wf;
                        $predano=1;
                    }
                    // Stav
                    if ( $stav <= $wf->stav_dokumentu ) {
                        $stav = $wf->stav_dokumentu;
                    }
                }
            }
            $dokument->stav_dokumentu = $stav;


            // lhuta
            $dokument->lhuta_stav = 0;
            if ( !empty($dokument->lhuta) ) {
                $datum_vzniku = strtotime($dokument->datum_vzniku);
                $dokument->lhuta_do = $datum_vzniku + ($dokument->lhuta * 86400);
                $rozdil = $dokument->lhuta_do - time();

                if ( $rozdil < 0 ) {
                    $dokument->lhuta_stav = 2;
                } else if ( $rozdil <= 432000 ) {
                    $dokument->lhuta_stav = 1;
                }
            } else {
                $dokument->lhuta_do = 'neurčeno';
                $dokument->lhuta_stav = 0;
            }
            if ( $stav > 3 ) {
                $dokument->lhuta_stav = 0;
            }

            // datum skartace
            if ( !empty($dokument->datum_spousteci_udalosti) ) {
                $datum_spusteni = strtotime($dokument->datum_spousteci_udalosti);
                if ( empty($dokument->skartacni_lhuta) ) {
                    // neurceno
                    $dokument->datum_skartace = 'neurčeno';
                } else if ( $dokument->skartacni_lhuta > 1900 ) {
                    // jde o rok 1.1.rok
                    $dokument->datum_skartace = mktime(0,0,0,1,1,(int)$dokument->skartacni_lhuta);
                } else {
                    // jde o roky
                    //$datum_skartace =
                    $dokument->datum_skartace = DateDiff::add($dokument->datum_spousteci_udalosti, $dokument->skartacni_lhuta);
                }
            } else {
                $dokument->datum_skartace = 'neurčeno';
            }

            // spisovy znak
            if ( !empty($dokument->spisovy_znak_id) ) {
                $SpisZnak = new SpisovyZnak();
                $sznak = $SpisZnak->getInfo($dokument->spisovy_znak_id);
                if ( $sznak ) {
                    $dokument->spisovy_znak = $sznak->nazev;
                    $dokument->spisovy_znak_popis = $sznak->popis;
                    $dokument->spisovy_znak_skart_znak = $sznak->skartacni_znak;
                    $dokument->spisovy_znak_skart_lhuta = $sznak->skartacni_lhuta;
                    $dokument->spisovy_znak_udalost = $sznak->spousteci_udalost;
                    $dokument->spisovy_znak_udalost_nazev = $sznak->spousteci_udalost_nazev;
                    $dokument->spisovy_znak_udalost_stav = $sznak->spousteci_udalost_stav;
                    $dokument->spisovy_znak_udalost_dtext = $sznak->spousteci_udalost_dtext;
                } else {
                    $dokument->spisovy_znak = '';
                    $dokument->spisovy_znak_popis = '';
                    $dokument->spisovy_znak_skart_znak = '';
                    $dokument->spisovy_znak_skart_lhuta = '';
                    $dokument->spisovy_znak_udalost = '';
                    $dokument->spisovy_znak_udalost_nazev = '';
                    $dokument->spisovy_znak_udalost_stav = '';
                    $dokument->spisovy_znak_udalost_dtext = '';
                }
            } else {
                $dokument->spisovy_znak = '';
                $dokument->spisovy_znak_popis = '';
                $dokument->spisovy_znak_skart_znak = '';
                $dokument->spisovy_znak_skart_lhuta = '';
                $dokument->spisovy_znak_udalost = '';
                $dokument->spisovy_znak_udalost_nazev = '';
                $dokument->spisovy_znak_udalost_stav = '';
                $dokument->spisovy_znak_udalost_dtext = '';
            }

            //vyrizeni
            if ( !empty($dokument->zpusob_vyrizeni_id) ) {
                $zpvyrizeni = Dokument::zpusobVyrizeni($dokument->zpusob_vyrizeni_id);
                $dokument->zpusob_vyrizeni = $zpvyrizeni->nazev;
            } else {
                $dokument->zpusob_vyrizeni = '';
            }

            return $dokument;


        } else {
            return null;
        }
        
        
    }

    public function getBasicInfo($dokument_id,$dokument_version = null) {

        if ( !is_null($dokument_version) ) {
            $where = array( array('id=%i',$dokument_id),array('version=%i',$dokument_version) );
        } else {
            $where = array( array('id=%i',$dokument_id) );
        }
        $order_by = array('id'=>'DESC','version'=>'DESC');
        $limit = 1;

        $select = $this->fetchAll($order_by, $where, null, $limit);
        $result = $select->fetch();

        return $result;

    }

    public function getMax() {

        $result = $this->fetchAll(array('id'=>'DESC'),null,null,1);
        $row = $result->fetch();
        return ($row) ? ($row->id+1) : 1;

    }

    public function getMaxPoradi($cjednaci) {

        $result = $this->fetchAll(array('poradi'=>'DESC'),array(array('cislojednaci_id=%i',$cjednaci)),null,1);
        $row = $result->fetch();
        return ($row) ? ($row->poradi+1) : 1;

    }

    public function ulozit($data, $dokument_id = null, $dokument_version = null) {


        if ( is_null($data) ) {
            return false;
        } else if ( is_null($dokument_id) ) {
            // novy dokument

            $data['id'] = $this->getMax();
            $data['version'] = 1;
            $data['date_created'] = new DateTime();
            $data['user_created'] = Environment::getUser()->getIdentity()->id;
            $data['stav'] = isset($data['stav'])?$data['stav']:1;
            $data['md5_hash'] = $this->generujHash($data);
            $this->insert_basic($data);
            $new_row = $this->getInfo($data['id']);

            if ( $new_row ) {
                return $new_row;
            } else {
                return false;
            }
        } else {
            // uprava existujiciho dokumentu

            $old_dokument = $this->getBasicInfo($dokument_id,$dokument_version);

            if ( $old_dokument ) {

                //Debug::dump($data);

                // sestaveni upravenych dat
                $update_data = array();
                foreach ( $old_dokument as $key => $value ) {
                    $update_data[ $key ] = $value;
                    if ( array_key_exists($key, $data) ) {
                        $update_data[ $key ] = $data[ $key ];
                    }
                }
                $md5_hash = $this->generujHash($update_data);

                //Debug::dump($update_data);
                //Debug::dump($md5_hash);
                //exit;

                if ( !is_null($dokument_version) ) {
                    // update na stavajici verzi

                    $update_data['date_modified'] = new DateTime();
                    $update_data['user_modified'] = Environment::getUser()->getIdentity()->id;
                    $update_data['md5_hash'] = $md5_hash;
                    unset($update_data['id'],$update_data['version']);
                    $updateres = $this->update($update_data, array(
                                                    array('id=%i',$dokument_id),
                                                    array('version=%i',$dokument_version)
                                                    )
                                               );
                    if ( $updateres ) {
                        $new_row = $this->getInfo($dokument_id,$dokument_version);
                        return $new_row;
                    } else {
                        return false;
                    }
                } else {

                    if ( $md5_hash == $old_dokument->md5_hash  ) {

                        // shodny hash - zadna zmena - pouze update
                        $update_data['date_modified'] = new DateTime();
                        $update_data['user_modified'] = Environment::getUser()->getIdentity()->id;
                        unset($update_data['id'],$update_data['version']);
                        $updateres = $this->update($update_data, array(
                                                    array('id=%i',$old_dokument->id),
                                                    array('version=%i',$old_dokument->version)
                                                    )
                                               );
                        if ( $updateres ) {
                            $new_row = $this->getInfo($old_dokument->id,$old_dokument->version);
                            return $new_row;
                        } else {
                            return false;
                        }
                    } else {

                        // zjistena zmena - nova verze
                        $update = array('stav%sql'=>'stav+100');
                        $this->update($update, array('id=%i',$dokument_id));

                        $update_data['version'] = $old_dokument->version + 1;
                        $update_data['date_created'] = new DateTime();
                        $update_data['user_created'] = Environment::getUser()->getIdentity()->id;
                        $update_data['md5_hash'] = $md5_hash;

                        $this->insert_basic($update_data);
                        $new_row = $this->getBasicInfo($update_data['id'],$update_data['version']);

                        if ( $new_row ) {
                            return $new_row;
                        } else {
                            return false;
                        }


                    }
                }
            } else {
                return false; // id dokumentu neexistuje
            }
        }
    }

    public function zmenitStav($data) {

        if ( is_array($data) ) {
            
            $dokument_id = $data['id'];
            $dokument_version = $data['version'];
            unset($data['id'],$data['version']);
            $data['date_modified'] = new DateTime();

            //$transaction = (! dibi::inTransaction());
            //if ($transaction)
            //dibi::begin('stavdok');

            // aktualni verze
            $this->update($data, array(array('stav<100'), array('id=%i',$dokument_id)) );

            // ostatni verze
            $data['stav'] = $data['stav'] + 100;
            $this->update($data, array(array('stav>=100'), array('id=%i',$dokument_id)) );

            //if ($transaction)
            //dibi::commit('stavdok');

            return true;
            
        } else {
            return false;
        }
    }

    protected function generujHash($data) {

        $data = Dokument::obj2array($data);

        unset( $data['id'],$data['version'],$data['md5_hash'],
               $data['date_created'],$data['user_created'],$data['date_modified'],$data['user_modified']
             );

        $data_implode = implode('#', $data);

        // věc#popis#1##2010-05-23#30##0#9#OUV-9/2010#denik#9#2010
        // věc#popis#1##2010-05-23#věc#popis#1##2010-05-23#
        //echo $data_implode;
        return md5($data_implode);

    }

    public function kontrola($data, $typ = "komplet") {

        $mess = array();
        if ( empty($data->nazev) ) $mess[] = "Věc dokumentu nemůže být prázdné!";
        if ( empty($data->cislo_jednaci) ) $mess[] = "Číslo jednací dokumentu nemůže být prázdné!";
        if ( empty($data->datum_vzniku) || $data->datum_vzniku == "0000-00-00 00:00:00" ) $mess[] = "Datum přijetí/vytvoření nemůže být prázdné!";

        if ( $typ == "komplet" ) {

            //if ( empty($data->datum_vyrizeni) || $data->datum_vyrizeni == "0000-00-00 00:00:00" ) $mess[] = "Datum vyřízení nemůže být prázdné!";
            if ( empty($data->zpusob_vyrizeni_id) || $data->zpusob_vyrizeni_id == 0 ) $mess[] = "Není zvolen způsob vyřízení dokumentu!";
            if ( empty($data->spisovy_znak) ) $mess[] = "Není zvolen spisový znak!";
            if ( empty($data->skartacni_znak) ) $mess[] = "Není vyplněn skartační znak!";
            if ( empty($data->skartacni_lhuta) ) $mess[] = "Není vyplněna skartační lhůta!";
            if ( empty($data->spousteci_udalost) ) $mess[] = "Není zvolena spouštěcí událost!";

            if ( count($data->subjekty)==0 ) {
                $mess[] = "Dokument musí obsahovat aspoň jeden subjekt!";
            }
            if ( count($data->prilohy)==0 ) {
                $mess[] = "Dokument musí obsahovat aspoň jednu přílohu!";
            }
        }

        if ( count($mess)>0 ) {
            return $mess;
        } else {
            return null;
        }

    }

    public static function typDokumentu( $kod = null, $select = 0 ) {

        $prefix = Environment::getConfig('database')->prefix;
        $tb_dokument_typ = $prefix .'dokument_typ';

        $result = dibi::query('SELECT * FROM %n', $tb_dokument_typ )->fetchAssoc('id');

        if ( is_null($kod) ) {
            if ( $select == 1 ) {
                $tmp = array();
                foreach ($result as $dt) {
                    $tmp[ $dt->id ] = $dt->nazev;
                }
                return $tmp;
            } else {
                return $result;
            }
        } else {
            return ( array_key_exists($kod, $result) )?$result[ $kod ]:null;
        }
        
    }

    public static function zpusobVyrizeni( $kod = null, $select = 0 ) {

        $prefix = Environment::getConfig('database')->prefix;
        $tb_zpusob_vyrizeni = $prefix .'zpusob_vyrizeni';

        $result = dibi::query('SELECT * FROM %n', $tb_zpusob_vyrizeni )->fetchAssoc('id');

        if ( is_null($kod) ) {
            if ( $select == 1 ) {
                $tmp = array();
                foreach ($result as $dt) {
                    $tmp[ $dt->id ] = $dt->nazev;
                }
                return $tmp;
            } else {
                return $result;
            }
        } else {
            return ( array_key_exists($kod, $result) )?$result[ $kod ]:null;
        }

    }

    public static function stav($dokument = null) {

        $stavy = array('1'=>'aktivný',
                       '2'=>'neaktivný',
                       '3'=>'zrušený'
            );

        if ( is_null( $dokument ) ) {
            return $stavy;
        } else if ( !is_numeric($dokument) ) {
            return null;
        }

        $index = ($dokument>=100)?$dokument-100:$dokument;
        if ( array_key_exists($index, $stavy) ) {
         return $stavy[ $index ];
        } else {
            return null;
        }



    }



}
