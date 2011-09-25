<?php //netteloader=Dokument

class Dokument extends BaseModel
{

    protected $name = 'dokument';
    protected $primary = 'id';

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
            $order = array('d.podaci_denik_rok'=>'DESC','d.podaci_denik_poradi'=>'DESC','d.poradi'=>'DESC');
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
                    'on' => array('dtyp.id=d.dokument_typ_id'),
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
                foreach ($result as $index => $row) {
                    $dok = $this->getInfo($row->id, 1);
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
                $args['order'] = array('wf.aktivni','wf.stav_dokumentu');
                break;
            case 'stav_desc':
                $args['order'] = array('wf.aktivni','wf.stav_dokumentu'=>'DESC');
                break;
            case 'cj':
                $args['order'] = array('d.podaci_denik_rok','d.podaci_denik_poradi','d.poradi');
                break;
            case 'cj_desc':
                $args['order'] = array('d.podaci_denik_rok'=>'DESC','d.podaci_denik_poradi'=>'DESC','d.poradi'=>'DESC');
                break;
            case 'jid':
                $args['order'] = array('d.id','d.podaci_denik_rok','d.podaci_denik_poradi','d.poradi');
                break;
            case 'jid_desc':
                $args['order'] = array('d.id'=>'DESC','d.podaci_denik_rok'=>'DESC','d.podaci_denik_poradi'=>'DESC','d.poradi'=>'DESC');
                break;            
            case 'vec':
                $args['order'] = array('d.nazev');
                break;
            case 'vec_desc':
                $args['order'] = array('d.nazev'=>'DESC');
                break;
            case 'dvzniku':
                $args['order'] = array('d.datum_vzniku','d.podaci_denik_rok','d.podaci_denik_poradi');
                break;
            case 'dvzniku_desc':
                $args['order'] = array('d.datum_vzniku'=>'DESC','d.podaci_denik_rok'=>'DESC','d.podaci_denik_poradi'=>'DESC');
                break;
            case 'skartacni_znak':
                $args['order'] = array('d.skartacni_znak','d.podaci_denik_rok','d.podaci_denik_poradi');
                break;
            case 'skartacni_znak_desc':
                $args['order'] = array('d.skartacni_znak'=>'DESC','d.podaci_denik_rok'=>'DESC','d.podaci_denik_poradi'=>'DESC');
                break;
            case 'spisovy_znak':
                $args['leftJoin']['sznak'] = array(
                    'from'=> array($this->tb_spisovy_znak => 'sznak'),
                    'on' => array('sznak.id=d.spisovy_znak_id'),
                    'cols' => null
                );                
                $args['order'] = array('sznak.nazev','d.podaci_denik_rok','d.podaci_denik_poradi');
                break;
            case 'spisovy_znak_desc':
                $args['leftJoin']['sznak'] = array(
                    'from'=> array($this->tb_spisovy_znak => 'sznak'),
                    'on' => array('sznak.id=d.spisovy_znak_id'),
                    'cols' => null
                );                
                $args['order'] = array('sznak.nazev'=>'DESC','d.podaci_denik_rok'=>'DESC','d.podaci_denik_poradi'=>'DESC');
                break;
            case 'prideleno':
                $args['leftJoin']['wf_user'] = array(
                    'from'=> array($this->tb_osoba_to_user => 'wf_user'),
                    'on' => array('wf_user.user_id=wf.prideleno_id'),
                    'cols' => null
                );
                $args['leftJoin']['wf_osoba'] = array(
                    'from'=> array($this->tb_osoba => 'oso'),
                    'on' => array('oso.id=wf_user.osoba_id'),
                    'cols' => null
                );
                $args['order'] = array('oso.prijmeni','oso.jmeno');                
                break;
            case 'prideleno_desc':
                $args['leftJoin']['wf_user'] = array(
                    'from'=> array($this->tb_osoba_to_user => 'wf_user'),
                    'on' => array('wf_user.user_id=wf.prideleno_id'),
                    'cols' => null
                );
                $args['leftJoin']['wf_osoba'] = array(
                    'from'=> array($this->tb_osoba => 'oso'),
                    'on' => array('oso.id=wf_user.osoba_id'),
                    'cols' => null
                );
                $args['order'] = array('oso.prijmeni'=>'DESC','oso.jmeno'=>'DESC');                
                break;
            
            default:
                break;
        }
        return $args;

    }

    public function filtr($nazev = null, $params = null, $bez_vyrizenych = false) {

        if ( is_null($nazev) && is_null($params) ) {
            return null;
        } else if ( $nazev == 'spisovna' ) {
            return $this->spisovnaFiltr($params);
        } else if ( is_null($nazev) ) {
            return $this->paramsFiltr($params);
        } else {
            return $this->fixedFiltr($nazev, $params, $bez_vyrizenych);
        }
    }

    private function paramsFiltr($params) {

        $args = array();

        //Debug::dump($params); exit;

        if ( isset($params['nazev']) ) {
            if ( !empty($params['nazev']) ) {
                $args['where'][] = array('d.nazev LIKE %s','%'.$params['nazev'].'%');
            }
        }

        // popis
        if ( isset($params['popis']) ) {
            if ( !empty($params['popis']) ) {
                $args['where'][] = array('d.popis LIKE %s','%'.$params['popis'].'%');
            }
        }

        // cislo jednaci
        if ( isset($params['cislo_jednaci']) ) {
            if ( !empty($params['cislo_jednaci']) ) {
                $args['where'][] = array('d.cislo_jednaci LIKE %s','%'.$params['cislo_jednaci'].'%');
            }
        }

        // spisova znacka - nazev spisu
        if ( isset($params['spisova_znacka']) ) {
            if ( !empty($params['spisova_znacka']) ) {
                $args['where'][] = array('spis.nazev LIKE %s','%'.$params['spisova_znacka'].'%');
            }
        }

        // typ dokumentu
        if ( isset($params['dokument_typ_id']) ) {
            if ( $params['dokument_typ_id'] != '0' ) {
                $args['where'][] = array('d.dokument_typ_id = %i',$params['dokument_typ_id']);
            }
        }

        // zpusob doruceni
        if ( isset($params['zpusob_doruceni_id']) ) {
            if ( $params['zpusob_doruceni_id'] != '0' ) {
                $args['where'][] = array('d.zpusob_doruceni_id = %i',$params['zpusob_doruceni_id']);
            }
        }
        
        if ( isset($params['typ_doruceni']) ) {
            if ( $params['typ_doruceni'] != '0' ) {

                $args['leftJoin']['zpusob_doruceni'] = array(
                    'from'=> array($this->tb_epodatelna => 'epod'),
                    'on' => array('epod.dokument_id=d.id'),
                    'cols' => null
                );
                switch ($params['typ_doruceni']) {
                    case 1: // epod
                        $args['where'][] = array('epod.id IS NOT NULL');
                        break;
                    case 2: // email
                        $args['where'][] = array('epod.email_signature IS NOT NULL');
                        break;
                    case 3: // isds
                        $args['where'][] = array('epod.isds_signature IS NOT NULL');
                        break;
                    case 4: // mimo epod
                        $args['where'][] = array('epod.id IS NULL');
                        break;
                    default:
                        break;
                }
            }
        }

        // CJ odesilatele
        if ( isset($params['cislo_jednaci_odesilatele']) ) {
            if ( !empty($params['cislo_jednaci_odesilatele']) ) {
                $args['where'][] = array('d.cislo_jednaci_odesilatele LIKE %s','%'.$params['cislo_jednaci_odesilatele'].'%');
            }
        }
        
        // cislo doporuceneho dopisu
        if ( isset($params['cislo_doporuceneho_dopisu']) ) {
            if ( !empty($params['cislo_doporuceneho_dopisu']) ) {
                $args['where'][] = array('d.cislo_doporuceneho_dopisu LIKE %s','%'.$params['cislo_doporuceneho_dopisu'].'%');
            }
        }   
        // pouze doporucene
        if ( isset($params['cislo_doporuceneho_dopisu_pouze']) ) {
            if ( $params['cislo_doporuceneho_dopisu_pouze'] ) {
                $args['where'][] = array("d.cislo_doporuceneho_dopisu <> ''");
            }
        }             

        // Datum vzniku
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

        // Datum vzniku - rozmezi
        if ( isset($params['datum_vzniku_od']) || isset($params['datum_vzniku_do']) ) {
            if ( !empty($params['datum_vzniku_od']) && !empty($params['datum_vzniku_do']) ) {
                if ( !empty($params['datum_vzniku_cas_od']) ) {
                    $params['datum_vzniku_od'] = $params['datum_vzniku_od'] .' '. $params['datum_vzniku_cas_od'];
                }
                if ( !empty($params['datum_vzniku_cas_do']) ) {
                    $params['datum_vzniku_do'] = $params['datum_vzniku_do'] .' '. $params['datum_vzniku_cas_do'];
                } else {
                    $unix_do = strtotime($params['datum_vzniku_do']);
                    if ( $unix_do ) {
                        $params['datum_vzniku_do'] = date("Y-m-d",$unix_do+86400);
                    }
                }

                $args['where'][] = array('d.datum_vzniku BETWEEN %d AND %d',$params['datum_vzniku_od'],$params['datum_vzniku_do']);
            } else if ( !empty($params['datum_vzniku_od']) ) {
                if ( !empty($params['datum_vzniku_cas_od']) ) {
                    $params['datum_vzniku_od'] = $params['datum_vzniku_od'] .' '. $params['datum_vzniku_cas_od'];
                }
                $args['where'][] = array('d.datum_vzniku >= %d',$params['datum_vzniku_od']);
            } else if ( !empty($params['datum_vzniku_do']) ) {
                if ( !empty($params['datum_vzniku_cas_do']) ) {
                    $params['datum_vzniku_do'] = $params['datum_vzniku_do'] .' '. $params['datum_vzniku_cas_do'];
                } else {
                    $unix_do = strtotime($params['datum_vzniku_do']);
                    if ( $unix_do ) {
                        $params['datum_vzniku_do'] = date("Y-m-d",$unix_do+86400);
                    }
                }
                $args['where'][] = array('d.datum_vzniku < %d',$params['datum_vzniku_do']);
            }
        }

        // pocet listu
        if ( isset($params['pocet_listu']) ) {
            if ( !empty($params['pocet_listu']) ) {
                $args['where'][] = array('d.pocet_listu = %i',$params['pocet_listu']);
            }
        }

        //
        if ( isset($params['pocet_priloh']) ) {
            if ( !empty($params['pocet_priloh']) ) {
                $args['where'][] = array('d.pocet_priloh = %i',$params['pocet_priloh']);
            }
        }

        // stav dokumentu
        if ( isset($params['stav_dokumentu']) ) {
            if ( !empty($params['stav_dokumentu']) ) {

                if ( $params['stav_dokumentu'] == 4 ) {
                    // vyrizene - 4,5,6
                    $args['where'][] = array('wf.stav_dokumentu IN (4,5,6) AND wf.aktivni=1');
                } else if ( $params['stav_dokumentu'] == 77 ) {
                    $args['where'][] = array('wf.stav_dokumentu IN (6,7,8) AND wf.aktivni=1');
                } else {
                    $args['where'][] = array('wf.stav_dokumentu = %i AND wf.aktivni=1',$params['stav_dokumentu']);
                }
                
            }
        }

        // lhuta
        if ( isset($params['lhuta']) ) {
            if ( !empty($params['lhuta']) ) {
                $args['where'][] = array('d.lhuta = %i',$params['lhuta']);
            }
        }

        // poznamka k dokumentu
        if ( isset($params['poznamka']) ) {
            if ( !empty($params['poznamka']) ) {
                $args['where'][] = array('d.poznamka LIKE %s','%'.$params['poznamka'].'%');
            }
        }

        // zpusob vyrizeni
        if ( isset($params['zpusob_vyrizeni_id']) ) {
            if ( !empty($params['zpusob_vyrizeni_id']) || $params['zpusob_vyrizeni_id'] != '0' ) {
                $args['where'][] = array('d.zpusob_vyrizeni_id = %i',$params['zpusob_vyrizeni_id']);
            }
        }

        // datum vyrizeni
        if ( isset($params['datum_vyrizeni']) ) {
            if ( !empty($params['datum_vyrizeni']) ) {
                $cas = '';
                if ( !empty($params['datum_vyrizeni_cas']) ) {
                    $cas = ' '. $params['datum_vyrizeni_cas'];
                }
                $args['where'][] = array('d.datum_vyrizeni = %d',$params['datum_vyrizeni'].$cas);
            }
        }
        
        // datum vyrizeni - rozmezi
        if ( isset($params['datum_vyrizeni_od']) || isset($params['datum_vyrizeni_do']) ) {
            if ( !empty($params['datum_vyrizeni_od']) && !empty($params['datum_vyrizeni_do']) ) {
                if ( !empty($params['datum_vyrizeni_cas_od']) ) {
                    $params['datum_vyrizeni_od'] = $params['datum_vyrizeni_od'] .' '. $params['datum_vyrizeni_cas_od'];
                }
                if ( !empty($params['datum_vyrizeni_cas_do']) ) {
                    $params['datum_vyrizeni_do'] = $params['datum_vyrizeni_do'] .' '. $params['datum_vyrizeni_cas_do'];
                } else {
                    $unix_do = strtotime($params['datum_vyrizeni_do']);
                    if ( $unix_do ) {
                        $params['datum_vyrizeni_do'] = date("Y-m-d",$unix_do+86400);
                    }
                }

                $args['where'][] = array('d.datum_vyrizeni BETWEEN %d AND %d',$params['datum_vyrizeni_od'],$params['datum_vyrizeni_do']);
            } else if ( !empty($params['datum_vyrizeni_od']) ) {
                if ( !empty($params['datum_vyrizeni_cas_od']) ) {
                    $params['datum_vyrizeni_od'] = $params['datum_vyrizeni_od'] .' '. $params['datum_vyrizeni_cas_od'];
                }
                $args['where'][] = array('d.datum_vyrizeni >= %d',$params['datum_vyrizeni_od']);
            } else if ( !empty($params['datum_vyrizeni_do']) ) {
                if ( !empty($params['datum_vyrizeni_cas_do']) ) {
                    $params['datum_vyrizeni_do'] = $params['datum_vyrizeni_do'] .' '. $params['datum_vyrizeni_cas_do'];
                } else {
                    $unix_do = strtotime($params['datum_vyrizeni_do']);
                    if ( $unix_do ) {
                        $params['datum_vyrizeni_do'] = date("Y-m-d",$unix_do+86400);
                    }
                }
                $args['where'][] = array('d.datum_vyrizeni < %d',$params['datum_vyrizeni_do']);
            }
        }

        // zpusob odeslani
        if ( isset($params['zpusob_odeslani']) ) {
            if ( !empty($params['zpusob_odeslani']) || $params['zpusob_odeslani'] != '0' ) {

                $args['leftJoin']['zpusob_odeslani'] = array(
                    'from'=> array($this->tb_dok_odeslani => 'dok_odeslani'),
                    'on' => array('dok_odeslani.dokument_id=d.id'),
                    'cols' => null
                );

                $args['where'][] = array('dok_odeslani.zpusob_odeslani_id = %i',$params['zpusob_odeslani']);
            }
        }

        // datum odeslani
        if ( isset($params['datum_odeslani']) ) {
            if ( !empty($params['datum_odeslani']) ) {

                $args['leftJoin']['zpusob_odeslani'] = array(
                    'from'=> array($this->tb_dok_odeslani => 'dok_odeslani'),
                    'on' => array('dok_odeslani.dokument_id=d.id'),
                    'cols' => null
                );

                $cas = '';
                if ( !empty($params['datum_odeslani_cas']) ) {
                    $cas = ' '. $params['datum_odeslani_cas'];
                }
                $args['where'][] = array('dok_odeslani.datum_odeslani = %d',$params['datum_odeslani'].$cas);
            }
        }

        // datum odeslani - rozmezi
        if ( isset($params['datum_odeslani_od']) || isset($params['datum_odeslani_do']) ) {

            $args['leftJoin']['zpusob_odeslani'] = array(
                'from'=> array($this->tb_dok_odeslani => 'dok_odeslani'),
                'on' => array('dok_odeslani.dokument_id=d.id'),
                'cols' => null
            );

            if ( !empty($params['datum_odeslani_od']) && !empty($params['datum_odeslani_do']) ) {
                if ( !empty($params['datum_odeslani_cas_od']) ) {
                    $params['datum_odeslani_od'] = $params['datum_odeslani_od'] .' '. $params['datum_odeslani_cas_od'];
                }
                if ( !empty($params['datum_odeslani_cas_do']) ) {
                    $params['datum_odeslani_do'] = $params['datum_odeslani_do'] .' '. $params['datum_odeslani_cas_do'];
                } else {
                    $unix_do = strtotime($params['datum_odeslani_do']);
                    if ( $unix_do ) {
                        $params['datum_odeslani_do'] = date("Y-m-d",$unix_do+86400);
                    }
                }

                $args['where'][] = array('dok_odeslani.datum_odeslani BETWEEN %d AND %d',$params['datum_odeslani_od'],$params['datum_odeslani_do']);
            } else if ( !empty($params['datum_odeslani_od']) ) {
                if ( !empty($params['datum_odeslani_cas_od']) ) {
                    $params['datum_odeslani_od'] = $params['datum_odeslani_od'] .' '. $params['datum_odeslani_cas_od'];
                }
                $args['where'][] = array('dok_odeslani.datum_odeslani >= %d',$params['datum_odeslani_od']);
            } else if ( !empty($params['datum_odeslani_do']) ) {
                if ( !empty($params['datum_odeslani_cas_do']) ) {
                    $params['datum_odeslani_do'] = $params['datum_odeslani_do'] .' '. $params['datum_odeslani_cas_do'];
                } else {
                    $unix_do = strtotime($params['datum_odeslani_do']);
                    if ( $unix_do ) {
                        $params['datum_odeslani_do'] = date("Y-m-d",$unix_do+86400);
                    }
                }
                $args['where'][] = array('dok_odeslani.datum_odeslani < %d',$params['datum_odeslani_do']);
            }
        }


        if ( isset($params['spisovy_znak']) ) {
            if ( !empty($params['spisovy_znak']) ) {
                $args['where'][] = array('d.spisovy_znak LIKE %s','%'.$params['spisovy_znak'] .'%');
            }
        }
        if ( isset($params['spisovy_znak_id']) ) {
            if ( !empty($params['spisovy_znak_id']) ) {
                $args['where'][] = array('d.spisovy_znak_id = %i',$params['spisovy_znak_id']);
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
                $args['where'][] = array('d.spousteci_udalost_id = %s',$params['spousteci_udalost']);
            }
        }
        if ( isset($params['spousteci_udalost_id']) ) {
            if ( !empty($params['spousteci_udalost_id']) ) {
                $args['where'][] = array('d.spousteci_udalost_id = %i',$params['spousteci_udalost_id']);
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
                    's.ic LIKE %s OR','%'.$params['subjekt_nazev'].'%',
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


        if ( isset($params['prideleno_osobne']) && $params['prideleno_osobne'] ) {
            $user_id = Environment::getUser()->getIdentity()->id;
            if ( !isset($params['prideleno']) ) {
                $params['prideleno'] = array();
            }
            $params['prideleno'][] = $user_id;
        }
        if ( isset($params['predano_osobne']) && $params['predano_osobne'] ) {
            $user_id = Environment::getUser()->getIdentity()->id;
            if ( !isset($params['predano']) ) {
                $params['predano'] = array();
            }
            $params['predano'][] = $user_id;
        }
        if ( isset($params['prideleno']) ) {
            if ( count($params['prideleno'])>0 && is_array($params['prideleno']) ) {
                $args['where'][] = array('wf.prideleno_id IN (%in) AND wf.stav_osoby=1 AND wf.aktivni=1',$params['prideleno']);
            }
        }
        if ( isset($params['predano']) ) {
            if ( count($params['predano'])>0 && is_array($params['predano']) ) {
                $args['where'][] = array('wf.prideleno_id IN (%in) AND wf.stav_osoby=0 AND wf.aktivni=1',$params['predano']);
            }
        }

        if ( isset($params['prideleno_na_organizacni_jednotku']) && $params['prideleno_na_organizacni_jednotku'] ) {
            $user = Environment::getUser()->getIdentity();
            if ( !isset($params['prideleno_org']) ) {
                $params['prideleno_org'] = array();
            }
            $org = array();
            foreach( $user->user_roles as $roles ) {
                if ( !empty($roles->orgjednotka_id) ) {
                    $params['prideleno_org'][] = $roles->orgjednotka_id;
                }
            }
        }
        if ( isset($params['predano_na_organizacni_jednotku']) && $params['predano_na_organizacni_jednotku'] ) {
            $user = Environment::getUser()->getIdentity();
            if ( !isset($params['predano_org']) ) {
                $params['predano_org'] = array();
            }
            $org = array();
            foreach( $user->user_roles as $roles ) {
                if ( !empty($roles->orgjednotka_id) ) {
                    $params['predano_org'][] = $roles->orgjednotka_id;
                }
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

    private function fixedFiltr($nazev, $params = null, $bez_vyrizenych = null) {

        $user = Environment::getUser()->getIdentity();
        $isVedouci = Environment::getUser()->isAllowed(NULL, 'is_vedouci');
        $isAdmin = Environment::getUser()->isInRole('admin');
        $vyrusit_bezvyrizeni = false;
        $org_jednotka = array();
        $org_jednotka_vedouci = array();

        if ( @count( $user->user_roles )>0 ) {
            foreach ( $user->user_roles as $role ) {
                if ( !empty($role->orgjednotka_id) ) {
                    if (preg_match('/^vedouci/', $role->code) ) {
                        $org_jednotka_vedouci[] = $role->orgjednotka_id;
                    }
                    $org_jednotka[] = $role->orgjednotka_id;
                }
            }
        }

        $where_org = null;
        if ( count($org_jednotka) == 1 ) {
            $where_org = array( 'wf.orgjednotka_id=%i',$org_jednotka[0] );
        } else if ( count($org_jednotka) > 1 ) {
            $where_org = array( 'wf.orgjednotka_id IN (%in)',$org_jednotka );
        } else if ( $isAdmin ) {
            $where_org = array( '1' );
        }

        switch ($nazev) {
            case 'org':
                if ( $isVedouci ) {

                    $org_jednotka_vedouci = Orgjednotka::childOrg($org_jednotka_vedouci);

                    if ( count($org_jednotka_vedouci) == 1 ) {
                        $args = array(
                            'where' => array( array('wf.orgjednotka_id=%i',$org_jednotka_vedouci[0]) )
                        );
                    } else if ( count($org_jednotka_vedouci) > 1 ) {
                        $args = array(
                            'where' => array( array('wf.orgjednotka_id IN (%in)',$org_jednotka_vedouci) )
                        );
                    } else if ( $isAdmin ) {
                        $args = array(
                            'where' => array( array('1') )
                        );
                    } else {
                        $args = array(
                            'where' => array( array('0') )
                        );
                    }
                } else {
                    $args = array(
                        'where' => array( array('0') )
                    );
                }
                break;
            case 'moje':
                if ( $isVedouci && count($org_jednotka_vedouci) ) {
                    if ( count($org_jednotka_vedouci)>1 ) {
                        $args = array(
                            'where' => array(
                                array('(wf.prideleno_id=%i',$user->id, ') OR (wf.prideleno_id IS NULL AND wf.orgjednotka_id IN (%in))',$org_jednotka_vedouci),
                                array('wf.stav_osoby=0 OR wf.stav_osoby=1 OR wf.stav_osoby=2'),
                                array('wf.aktivni=1') )
                        );
                    } else {
                        $args = array(
                            'where' => array(
                                array('(wf.prideleno_id=%i',$user->id, ') OR (wf.prideleno_id IS NULL AND wf.orgjednotka_id=%i)',$org_jednotka_vedouci[0]),
                                array('wf.stav_osoby=0 OR wf.stav_osoby=1 OR wf.stav_osoby=2'),
                                array('wf.aktivni=1') )
                        );
                    }
                } else {
                    $args = array(
                        'where' => array(
                            array('wf.prideleno_id=%i',$user->id,),
                            array('wf.stav_osoby=0 OR wf.stav_osoby=1 OR wf.stav_osoby=2'),
                            array('wf.aktivni=1') )
                    );
                }

                break;
            case 'predane':
                $args = array(
                    'where' => array( array('wf.prideleno_id=%i',$user->id),array('wf.stav_osoby=0'), array('wf.aktivni=1') )
                );
                break;
            case 'pracoval':
                $vyrusit_bezvyrizeni = true;
                $args = array(
                    'where' => array( array('wf.prideleno_id=%i',$user->id),array('wf.stav_osoby < 100') )
                );
                break;
            case 'moje_nove':
                $args = array(
                    'where' => array( array('wf.prideleno_id=%i',$user->id),array('wf.stav_osoby = 1'), array('wf.stav_dokumentu = 1'), array('wf.aktivni=1') )
                );
                break;
            case 'vsichni_nove':
                $args = array(
                    'where' => array( array('wf.stav_dokumentu = 1'), array('wf.aktivni=1'), $where_org )
                );
                break;
            case 'moje_vyrizuje':
                $args = array(
                    'where' => array( array('wf.prideleno_id=%i',$user->id),array('wf.stav_osoby = 1'), array('wf.stav_dokumentu = 3'), array('wf.aktivni=1') )
                );
                break;
            case 'vsichni_vyrizuji':
                $args = array(
                    'where' => array( array('wf.stav_dokumentu = 3'), array('wf.aktivni=1'), $where_org )
                );
                break;
            case 'moje_vyrizene':
                $vyrusit_bezvyrizeni = true;
                $args = array(
                    'where' => array( array('wf.prideleno_id=%i',$user->id),array('wf.stav_osoby = 1'),
                                      array('(wf.stav_dokumentu = 4 AND wf.aktivni=1) OR (wf.stav_dokumentu = 5 AND wf.aktivni=1)') )
                );
                break;
            case 'vsichni_vyrizene':
                $vyrusit_bezvyrizeni = true;
                $args = array(
                    'where' => array( array('(wf.stav_dokumentu = 4 AND wf.aktivni=1) OR (wf.stav_dokumentu = 5 AND wf.aktivni=1)'), $where_org )
                );
                break;
            case 'vse':
                if ( !is_null($where_org) ) {
                    $args = array(  
                        'where' => array( $where_org )
                    );
                } else {
                    $args = array();
                }
                break;
            default:
                $args = array(
                    'where' => array( array('0') )
                );
                break;
        }

        if ( $bez_vyrizenych && !$vyrusit_bezvyrizeni ) {

            // TODO vyresit jeste variantu na kterych jsem pracoval -> aktivni=0
            $args['where'][] = array('wf.aktivni=1');
            $args['where'][] = array('!((wf.stav_dokumentu = 4) OR (wf.stav_dokumentu = 5))');

        }


        return $args;

    }

    private function spisovnaFiltr($params = null)
    {

        if ( strpos($params,'stav_') !== false ) {
            $stav = substr($params, 5);
            return $this->paramsFiltr(array('stav_dokumentu'=>$stav));
        } else if ( $params == 'vlastni' ) {
            return $this->paramsFiltr(array('stav_dokumentu'=>77,'prideleno_osobne'=>1));
        } else if ( strpos($params,'skartacni_znak_') !== false ) {
            $skartacni_znak = substr($params, 15);
            return $this->paramsFiltr(array('skartacni_znak'=>$skartacni_znak));
        } else if ( strpos($params,'zpusob_vyrizeni_') !== false ) {
            $zpusob_vyrizeni = substr($params, 16);
            return $this->paramsFiltr(array('zpusob_vyrizeni_id'=>$zpusob_vyrizeni));
        } else {
            return $this->paramsFiltr(array('stav_dokumentu'=>77));
        }

    }

    public function spisovka($args) {

        if ( isset($args['where']) ) {
            $args['where'][] = array('d.stav = 1');
        } else {
            $args['where'] = array(array('d.stav = 1'));
        }

        // Omezeni pouze na dokumenty z vlastni organizacni jednotky
        $user = Environment::getUser()->getIdentity();
        $isVedouci = Environment::getUser()->isAllowed(NULL, 'is_vedouci');
        $isAdmin = Environment::getUser()->isInRole('admin');
        
        if ( $isVedouci ) {
            // prozatim bez omezeni
            // - mozne problemy pri filtrovani organizacnich jednotek
            // - tento stav ovsem propousti vse pri hledani
            ;
        } else if ( !$isAdmin ) {
            
            $org_jednotka = array();
            $org_jednotka_vedouci = array();

            if ( @count( $user->user_roles )>0 ) {
                foreach ( $user->user_roles as $role ) {
                    if ( !empty($role->orgjednotka_id) ) {
                        if (preg_match('/^vedouci/', $role->code) ) {
                            $org_jednotka_vedouci[] = $role->orgjednotka_id;
                        }
                        $org_jednotka[] = $role->orgjednotka_id;
                    }
                }
            } 
            $where_org = null;
            if ( count($org_jednotka_vedouci) > 0 ) {
                $where_org = array( 'wf.orgjednotka_id IN (%in)',$org_jednotka_vedouci );
            } else if ( count($org_jednotka) == 1 ) {
                $where_org = array( 'wf.orgjednotka_id=%i',$org_jednotka[0] );
            } else if ( count($org_jednotka) > 1 ) {
                $where_org = array( 'wf.orgjednotka_id IN (%in)',$org_jednotka );
            }
            $args['where'][] = array( $where_org );
            
        }
        
        return $args;
    }

    public function spisovna($args) {

        if ( isset($args['where']) ) {
            $args['where'][] = array('d.stav > 1');
            $args['where'][] = array('NOT (wf.stav_dokumentu = 6 AND wf.aktivni = 1)');
        } else {
            $args['where'] = array(
                    array('d.stav > 1'),
                    array('NOT (wf.stav_dokumentu = 6 AND wf.aktivni = 1)')
                );
        }
        
        // Omezeni pouze na dokumenty z vlastni organizacni jednotky
        $user = Environment::getUser()->getIdentity();
        $isVedouci = Environment::getUser()->isAllowed(NULL, 'is_vedouci');
        $isAdmin = Environment::getUser()->isInRole('admin');
        
        if ( !$isAdmin ) {
            
            $org_jednotka = array();
            $org_jednotka_vedouci = array();

            if ( @count( $user->user_roles )>0 ) {
                foreach ( $user->user_roles as $role ) {
                    if ( !empty($role->orgjednotka_id) ) {
                        if (preg_match('/^vedouci/', $role->code) ) {
                            $org_jednotka_vedouci[] = $role->orgjednotka_id;
                        }
                        $org_jednotka[] = $role->orgjednotka_id;
                    }
                }
            } 
            $where_org = null;
            if ( count($org_jednotka_vedouci) > 0 ) {
                $where_org = array( 'wf.orgjednotka_id IN (%in)',$org_jednotka_vedouci );
            } else if ( count($org_jednotka) == 1 ) {
                $where_org = array( 'wf.orgjednotka_id=%i',$org_jednotka[0] );
            } else if ( count($org_jednotka) > 1 ) {
                $where_org = array( 'wf.orgjednotka_id IN (%in)',$org_jednotka );
            }
            $args['where'][] = array( $where_org );
            
        }
        return $args;
    }

    public function spisovna_prijem($args) {

        if ( isset($args['where']) ) {
            $args['where'][] = array('wf.stav_dokumentu = 6 AND wf.aktivni = 1');
        } else {
            $args['where'] = array(array('wf.stav_dokumentu = 6 AND wf.aktivni = 1'));
        }

        return $args;
    }

    public function spisovna_keskartaci($args) {

        if ( isset($args['where']) ) {
            $args['where'][] = array('d.stav > 1');
            $args['where'][] = array('wf.stav_dokumentu < 8 AND wf.aktivni=1');
            $args['where'][] = array('NOW() > CASE WHEN d.skartacni_lhuta > 1900 THEN MAKEDATE(d.skartacni_lhuta,1) ELSE DATE_ADD(d.datum_spousteci_udalosti, INTERVAL d.skartacni_lhuta YEAR) END');
        } else {
            $args['where'] = array(
                                array('d.stav > 1'),
                                array('wf.stav_dokumentu < 8 AND wf.aktivni=1'),
                                array('NOW() > CASE WHEN d.skartacni_lhuta > 1900 THEN MAKEDATE(d.skartacni_lhuta,1) ELSE DATE_ADD(d.datum_spousteci_udalosti, INTERVAL d.skartacni_lhuta YEAR) END')
                             );
        }

        return $args;
    }

    public function spisovna_skartace($args) {

        if ( isset($args['where']) ) {
            $args['where'][] = array('wf.stav_dokumentu = 8 AND wf.aktivni = 1');
        } else {
            $args['where'] = array(array('wf.stav_dokumentu = 8 AND wf.aktivni = 1'));
        }

        return $args;
    }

    public function getInfo($dokument_id, $detail = 0, $dataplus = null) {

        $UserModel = new UserModel();

        $sql = array(
        
            'distinct'=>null,
            'from' => array($this->name => 'dok'),
            'cols' => array('*','id'=>'dokument_id','%sqlCASE WHEN dok.skartacni_lhuta > 1900 THEN MAKEDATE(dok.skartacni_lhuta,1) ELSE DATE_ADD(dok.datum_spousteci_udalosti, INTERVAL dok.skartacni_lhuta YEAR) END'=>'skartacni_rok'),
            'leftJoin' => array(
                'dokspisy' => array(
                    'from' => array($this->tb_dokspis => 'sp'),
                    'on' => array('sp.dokument_id=dok.id'),
                    'cols' => array('poradi'=>'poradi_spisu','stav'=>'stav_spisu')
                ),
                'typ_dokumentu' => array(
                    'from' => array($this->tb_dokumenttyp => 'dtyp'),
                    'on' => array('dtyp.id=dok.dokument_typ_id'),
                    'cols' => array('nazev'=>'typ_nazev','popis'=>'typ_popis','smer'=>'typ_smer')
                ),
                'workflow' => array(
                    'from' => array($this->tb_workflow => 'wf'),
                    'on' => array('wf.dokument_id=dok.id'),
                    'cols' => array('id'=>'workflow_id','stav_dokumentu','prideleno_id','orgjednotka_id',
                                    'stav_osoby','date'=>'date_prideleni','date_predani','poznamka'=>'poznamka_predani','aktivni'=>'wf_aktivni')
                ),
                'workflow_prideleno' => array(
                    'from' => array($this->tb_osoba_to_user => 'wf_o2u'),
                    'on' => array('wf_o2u.user_id=wf.prideleno_id'),
                    'cols' => array('osoba_id'=>'prideleno_osoba_id')
                ),
                'workflow_prideleno_osoba' => array(
                    'from' => array($this->tb_osoba => 'wf_prideleno_osoba'),
                    'on' => array('wf_prideleno_osoba.id=wf_o2u.osoba_id'),
                    'cols' => array('id'=>'osoba_id',
                                    'prijmeni'=>'osoba_prijmeni','jmeno'=>'osoba_jmeno','titul_pred'=>'osoba_titul_pred','titul_za'=>'osoba_titul_za'
                                   )
                ),
                'workflow_orgjednotka' => array(
                    'from' => array($this->tb_orgjednotka => 'wf_org'),
                    'on' => array('wf_org.id=wf.orgjednotka_id'),
                    'cols' => array('id'=>'org_id',
                                    'plny_nazev'=>'org_plny_nazev','zkraceny_nazev'=>'org_zkraceny_nazev','ciselna_rada'=>'org_ciselna_rada')
                ),
                'workflow_user' => array(
                    'from' => array($this->tb_osoba_to_user => 'wf_o2user'),
                    'on' => array('wf_o2user.user_id=wf.user_id'),
                    'cols' => array('osoba_id'=>'user_osoba_id','user_id'=>'wf_user_id')
                ),
                'workflow_user_osoba' => array(
                    'from' => array($this->tb_osoba => 'wf_user_osoba'),
                    'on' => array('wf_user_osoba.id=wf_o2user.osoba_id'),
                    'cols' => array('id'=>'user_osoba_id',
                                    'prijmeni'=>'user_prijmeni','jmeno'=>'user_jmeno','titul_pred'=>'user_titul_pred','titul_za'=>'user_titul_za'
                                   )
                ),
                'spisy' => array(
                    'from' => array($this->tb_spis => 'spis'),
                    'on' => array('spis.id=sp.spis_id'),
                    'cols' => array('id'=>'spis_id','nazev'=>'nazev_spisu','popis'=>'popis_spisu')
                ),
                'epod' => array(
                    'from' => array($this->tb_epodatelna => 'epod'),
                    'on' => array('epod.dokument_id=dok.id'),
                    'cols' => array('identifikator','email_signature'=>'epod_is_email','isds_signature'=>'epod_is_isds')
                ),
                'spisovy_znak' => array(
                    'from' => array($this->tb_spisovy_znak => 'spisznak'),
                    'on' => array('spisznak.id=dok.spisovy_znak_id'),
                    'cols' => array('id'=>'spisznak_id','nazev'=>'spisznak_nazev','popis'=>'spisznak_popis','skartacni_znak'=>'spisznak_skartacni_znak','skartacni_lhuta'=>'spisznak_skartacni_lhuta','spousteci_udalost_id'=>'spisznak_spousteci_udalost_id')
                ),
                'zpusob_doruceni' => array(
                    'from' => array($this->tb_zpusob_doruceni => 'zdoruceni'),
                    'on' => array('zdoruceni.id=dok.zpusob_doruceni_id'),
                    'cols' => array('nazev'=>'zpusob_doruceni')
                ),  
                'zpusob_vyrizeni' => array(
                    'from' => array($this->tb_zpusob_vyrizeni => 'zvyrizeni'),
                    'on' => array('zvyrizeni.id=dok.zpusob_vyrizeni_id'),
                    'cols' => array('nazev'=>'zpusob_vyrizeni')
                ),                    
                
            ),
            'order_by' => array('dok.id'=>'DESC')
        
        );
        
        $sql['where'] = array( array('dok.id=%i',$dokument_id) );
        
        $select = $this->fetchAllComplet($sql);
        $result = $select->fetchAll();
        if ( count($result)>0 ) {

            $tmp = array();
            $dokument_id = $dokument_version = 0;
            foreach ($result as $index => $row) {
                $id = $row->id;

                $spis = new stdClass();
                $spis->id = $row->spis_id; unset($row->spis_id);
                $spis->nazev = $row->nazev_spisu; unset($row->nazev_spisu);
                $spis->popis = $row->popis_spisu; unset($row->popis_spisu);
                $spis->stav = $row->stav_spisu; unset($row->stav_spisu);
                $spis->poradi = $row->poradi_spisu; unset($row->poradi_spisu);
                $tmp[$id]['spisy'][ $spis->id ] = $spis;

                $typ = new stdClass();
                $typ->id = $row->dokument_typ_id; unset($row->dokument_typ_id);
                $typ->nazev = $row->typ_nazev; unset($row->typ_nazev);
                $typ->popis = $row->typ_popis; unset($row->typ_popis);
                $typ->smer = $row->typ_smer; unset($row->typ_smer);
                $tmp[$id]['typ_dokumentu'] = $typ;

                $workflow = new stdClass();
                $workflow->id = $row->workflow_id; unset($row->workflow_id);
                $workflow->stav_dokumentu = $row->stav_dokumentu; unset($row->stav_dokumentu);
                $workflow->prideleno_id = $row->prideleno_id; unset($row->prideleno_id);
                if ( !empty($workflow->prideleno_id) ) {
                    $workflow->prideleno_jmeno = Osoba::displayName($row);
                } else {
                    $workflow->prideleno_jmeno = "";
                }
                $workflow->stav_osoby = $row->stav_osoby; unset($row->stav_osoby);
                
                $workflow->user_id = $row->wf_user_id; unset($row->wf_user_id);
                if ( !empty($workflow->user_id) ) {
                    $workflow->user_jmeno = Osoba::displayName($row,'user');
                } else {
                    $workflow->user_jmeno = "";
                }

                $workflow->orgjednotka_id = $row->orgjednotka_id; unset($row->orgjednotka_id);
                if ( !empty($workflow->orgjednotka_id) ) {
                    $tmp_org = new stdClass();
                    $tmp_org->id = $row->org_id;
                    $tmp_org->plny_nazev = $row->org_plny_nazev;
                    $tmp_org->zkraceny_nazev = $row->org_zkraceny_nazev;
                    $tmp_org->ciselna_rada = $row->org_ciselna_rada;
                    $workflow->orgjednotka_info = $tmp_org;
                    unset($tmp_org);
                } else {
                    $workflow->orgjednotka_info = null;
                }
                
                //$workflow->orgjednotka_info = unserialize($row->orgjednotka_info); unset($row->orgjednotka_info);
                $workflow->date_predani = $row->date_predani; unset($row->date_predani);
                $workflow->date = $row->date_prideleni; unset($row->date_prideleni);
                $workflow->poznamka = $row->poznamka_predani; unset($row->poznamka_predani);
                $workflow->aktivni = $row->wf_aktivni; unset($row->wf_aktivni);
                $tmp[$id]['workflow'][ $workflow->id ] = $workflow;

                $tmp[$id]['raw'] = $row;

                if ( $row->id >= $dokument_id ) $dokument_id = $row->id;

            }

            $dokument = $tmp[ $dokument_id ]['raw'];
            $dokument->typ_dokumentu = $tmp[ $dokument_id ]['typ_dokumentu'];
            $dokument->spisy = $tmp[ $dokument_id ]['spisy'];
            $dokument->workflow = $tmp[ $dokument_id ]['workflow'];

            if ( isset($dataplus['subjekty'][$dokument_id]) ) {
                $dokument->subjekty = $dataplus['subjekty'][$dokument_id];
            } else {
                $DokSubjekty = new DokumentSubjekt();
                $dokument->subjekty = $DokSubjekty->subjekty($dokument_id);
            }

            if ( isset($dataplus['prilohy'][$dokument_id]) ) {
                $dokument->prilohy = $dataplus['prilohy'][$dokument_id];
            } else {
                $Dokrilohy = new DokumentPrilohy();
                $dokument->prilohy = $Dokrilohy->prilohy($dokument_id,null,$detail);
            }

            if ( isset($dataplus['odeslani'][$dokument_id]) ) {
                if ( isset( $dataplus['odeslani'][$dokument_id] ) ) {
                    $dokument->odeslani = $dataplus['odeslani'][$dokument_id];
                } else {
                    $dokument->odeslani = null;
                }
            } else {
                $DokOdeslani = new DokumentOdeslani();
                $dokument->odeslani = $DokOdeslani->odeslaneZpravy($dokument_id);
            }


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
                    if ( $stav <= $wf->stav_dokumentu && $wf->aktivni == 1 ) {
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

                //$dokument->datum_skartace = $dokument->skartacni_rok;
                //= new DateTime($dokument->skartacni_rok);
                //Debug::dump($datum_skartace);

            } else {
                $dokument->datum_skartace = 'neurčeno';
            }

            // spisovy znak
            $dokument->spisovy_znak = $row->spisznak_nazev;
            $dokument->spisovy_znak_popis = $row->spisznak_popis;
            $dokument->spisovy_znak_skart_znak = $row->spisznak_skartacni_znak;
            $dokument->spisovy_znak_skart_lhuta = $row->spisznak_skartacni_lhuta;
            $dokument->spisovy_znak_udalost = $row->spisznak_spousteci_udalost_id;
            $dokument->spisovy_znak_udalost_nazev = SpisovyZnak::spousteci_udalost($row->spisznak_spousteci_udalost_id,10);
            $dokument->spisovy_znak_udalost_stav = '';
            $dokument->spisovy_znak_udalost_dtext = '';            
            /*if ( !empty($dokument->spisovy_znak_id) && $detail == 1 ) {
                $SpisZnak = new SpisovyZnak();
                $sznak = $SpisZnak->getInfo($dokument->spisovy_znak_id);
                if ( $sznak ) {
                    $dokument->spisovy_znak = $sznak->nazev;
                    $dokument->spisovy_znak_popis = $sznak->popis;
                    $dokument->spisovy_znak_skart_znak = $sznak->skartacni_znak;
                    $dokument->spisovy_znak_skart_lhuta = $sznak->skartacni_lhuta;
                    $dokument->spisovy_znak_udalost = $sznak->spousteci_udalost_id;
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
            }*/

            //vyrizeni
            /*if ( !empty($dokument->zpusob_vyrizeni_id) ) {
                $zpvyrizeni = Dokument::zpusobVyrizeni($dokument->zpusob_vyrizeni_id);
                $dokument->zpusob_vyrizeni = $zpvyrizeni->nazev;
            } else {
                $dokument->zpusob_vyrizeni = '';
            }*/

            if ( !empty($dokument->identifikator) ) {
                $Epodatelna = new Epodatelna();
                $dokument->identifikator = $Epodatelna->identifikator(unserialize($dokument->identifikator));
            }

            if ( empty($dokument->nazev) ) {
                $dokument->nazev = "(bez názvu)";
            }
            
            if ( strpos($dokument->cislo_jednaci,"odpoved_") !== false ) {
                $dokument->cislo_jednaci = "";
            }
            
            return $dokument;


        } else {
            return null;
        }
        
        
    }

    public function getBasicInfo($dokument_id) {

        $where = array( array('id=%i',$dokument_id) );
        $order_by = array('id'=>'DESC');
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

        if ( empty($cjednaci) ) return 1;

        $result = $this->fetchAll(array('poradi'=>'DESC'),array(array('cislo_jednaci_id=%i',$cjednaci)),null,1);
        $row = $result->fetch();
        return ($row) ? ($row->poradi+1) : 1;

    }

    public function ulozit($data, $dokument_id = null) {


        if ( is_null($data) ) {
            return false;
        } else if ( is_null($dokument_id) ) {
            // novy dokument
            
            if ( empty($data['zmocneni_id']) ) $data['zmocneni_id'] = null;
            if ( empty($data['cislo_jednaci_id']) ) {
                $data['cislo_jednaci_id'] = null;
            } else {
                $data['cislo_jednaci_id'] = (int) $data['cislo_jednaci_id'];
            }
            if ( empty($data['zpusob_doruceni_id']) ) {
                $data['zpusob_doruceni_id'] = null;
            } else {
                $data['zpusob_doruceni_id'] = (int) $data['zpusob_doruceni_id'];
            }
            if ( empty($data['zpusob_vyrizeni_id']) ) {
                $data['zpusob_vyrizeni_id'] = null;
            } else {
                $data['zpusob_vyrizeni_id'] = (int) $data['zpusob_vyrizeni_id'];
            }
            if ( empty($data['spousteci_udalost_id']) ) {
                $data['spousteci_udalost_id'] = null;
            } else {
                $data['spousteci_udalost_id'] = (int) $data['spousteci_udalost_id'];
            }
            if ( empty($data['spisovy_znak_id']) ) {
                $data['spisovy_znak_id'] = null;
            } else {
                $data['spisovy_znak_id'] = (int) $data['spisovy_znak_id'];
            }            
            if ( empty($data['datum_vzniku']) ) {
                $data['datum_vzniku'] = null;
            } 
            if ( isset($data['pocet_listu']) ) {
                if ( empty($data['pocet_listu']) ) {
                    $data['pocet_listu'] = 0;
                } else {
                    $data['pocet_listu'] = (int) $data['pocet_listu'];
                }             
                if ( empty($data['pocet_priloh']) ) {
                    $data['pocet_priloh'] = 0;
                } else {
                    $data['pocet_priloh'] = (int) $data['pocet_priloh'];
                }     
            }
            if ( isset($data['vyrizeni_pocet_listu']) ) {
                if ( empty($data['vyrizeni_pocet_listu']) ) {
                    $data['vyrizeni_pocet_listu'] = 0;
                } else {
                    $data['vyrizeni_pocet_listu'] = (int) $data['vyrizeni_pocet_listu'];
                }             
                if ( empty($data['vyrizeni_pocet_priloh']) ) {
                    $data['vyrizeni_pocet_priloh'] = 0;
                } else {
                    $data['vyrizeni_pocet_priloh'] = (int) $data['vyrizeni_pocet_priloh'];
                }       
            }
            if ( empty($data['jid']) ) {
                $unique_info = Environment::getVariable('unique_info');
                $unique_part = explode('#',$unique_info);
                $app_id = 'OSS-'. $unique_part[0];
                $data['jid'] = $app_id.'-ESS-'.$dokument_id;
            } 
            if ( empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = null;           

            $data['date_created'] = new DateTime();
            $data['user_created'] = Environment::getUser()->getIdentity()->id;
            $data['date_modified'] = new DateTime();
            $data['user_modified'] = Environment::getUser()->getIdentity()->id;

            $data['stav'] = isset($data['stav'])?$data['stav']:1;
            $data['md5_hash'] = $this->generujHash($data);
            $dokument_id_new = $this->insert($data);
            $new_row = $this->getInfo($dokument_id_new);

            if ( $new_row ) {
                return $new_row;
            } else {
                return false;
            }

        } else {
            // uprava existujiciho dokumentu

            if ( empty($data['zmocneni_id']) ) $data['zmocneni_id'] = null;
            if ( empty($data['cislo_jednaci_id']) ) {
                $data['cislo_jednaci_id'] = null;
            } else {
                $data['cislo_jednaci_id'] = (int) $data['cislo_jednaci_id'];
            }
            if ( empty($data['zpusob_doruceni_id']) ) {
                $data['zpusob_doruceni_id'] = null;
            } else {
                $data['zpusob_doruceni_id'] = (int) $data['zpusob_doruceni_id'];
            }
            if ( empty($data['zpusob_vyrizeni_id']) ) {
                $data['zpusob_vyrizeni_id'] = null;
            } else {
                $data['zpusob_vyrizeni_id'] = (int) $data['zpusob_vyrizeni_id'];
            }
            if ( empty($data['spousteci_udalost_id']) ) {
                $data['spousteci_udalost_id'] = null;
            } else {
                $data['spousteci_udalost_id'] = (int) $data['spousteci_udalost_id'];
            }
            if ( empty($data['spisovy_znak_id']) ) {
                $data['spisovy_znak_id'] = null;
            } else {
                $data['spisovy_znak_id'] = (int) $data['spisovy_znak_id'];
            }               
            
            if ( empty($data['datum_vzniku']) ) {
                $data['datum_vzniku'] = null;
            } 
            if ( isset($data['pocet_listu']) ) {
                if ( empty($data['pocet_listu']) ) {
                    $data['pocet_listu'] = 0;
                } else {
                    $data['pocet_listu'] = (int) $data['pocet_listu'];
                }             
                if ( empty($data['pocet_priloh']) ) {
                    $data['pocet_priloh'] = 0;
                } else {
                    $data['pocet_priloh'] = (int) $data['pocet_priloh'];
                }     
            }
            if ( isset($data['vyrizeni_pocet_listu']) ) {
                if ( empty($data['vyrizeni_pocet_listu']) ) {
                    $data['vyrizeni_pocet_listu'] = 0;
                } else {
                    $data['vyrizeni_pocet_listu'] = (int) $data['vyrizeni_pocet_listu'];
                }             
                if ( empty($data['vyrizeni_pocet_priloh']) ) {
                    $data['vyrizeni_pocet_priloh'] = 0;
                } else {
                    $data['vyrizeni_pocet_priloh'] = (int) $data['vyrizeni_pocet_priloh'];
                }            
            }
            
            if ( empty($data['jid']) ) {
                $unique_info = Environment::getVariable('unique_info');
                $unique_part = explode('#',$unique_info);
                $app_id = 'OSS-'. $unique_part[0];
                $data['jid'] = $app_id.'-ESS-'.$dokument_id;
            }  
            if ( empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = null;           

            $old_dokument = $this->getBasicInfo($dokument_id);

            if ( $old_dokument ) {

                //Debug::dump($data); //exit;

                // sestaveni upravenych dat
                $update_data = array();
                foreach ( $old_dokument as $key => $value ) {
                    $update_data[ $key ] = $value;
                    if ( isset( $data[ $key ] ) ) {
                        $update_data[ $key ] = $data[ $key ];
                    }
                }
                $md5_hash = $this->generujHash($update_data);

                //Debug::dump($update_data);
                //Debug::dump($md5_hash);
                //exit;

                if ( $md5_hash != $old_dokument->md5_hash  ) {
                    // zjistena zmena - vytvorime zaznam do historie
                    if ( empty($old_dokument['zmocneni_id']) ) $old_dokument['zmocneni_id'] = null;
                    if ( empty($old_dokument['zpusob_doruceni_id']) ) $old_dokument['zpusob_doruceni_id'] = null;
                    if ( empty($old_dokument['zpusob_vyrizeni_id']) ) {
                        $old_dokument['zpusob_vyrizeni_id'] = null;
                    } else {
                        $old_dokument['zpusob_vyrizeni_id'] = (int) $old_dokument['zpusob_vyrizeni_id'];
                    }
                    if ( empty($old_dokument['skartacni_lhuta']) ) $old_dokument['skartacni_lhuta'] = null;           
                    if ( empty($old_dokument['spousteci_udalost_id']) ) $old_dokument['spousteci_udalost_id'] = null;
                    $old_dokument = (array) $old_dokument;
                    $old_dokument['dokument_id'] = $dokument_id;
                    $old_dokument['user_created'] = Environment::getUser()->getIdentity()->id;
                    $old_dokument['date_created'] = new DateTime();
                    unset($old_dokument['id'],$old_dokument['user_modified'],$old_dokument['date_modified']);
                    //Debug::dump($old_dokument);
                    $DokumentHistorie = new DokumentHistorie();
                    $DokumentHistorie->insert($old_dokument);
                }

                $update_data['date_modified'] = new DateTime();
                $update_data['user_modified'] = Environment::getUser()->getIdentity()->id;
                $update_data['md5_hash'] = $md5_hash;
                unset($update_data['id']);
                $updateres = $this->update($update_data, array(
                                                array('id=%i',$dokument_id)
                                                )
                                           );
                if ( $updateres ) {
                    $update_row = $this->getInfo($dokument_id);
                    return $update_row;
                } else {
                    return false;
                }

            } else {
                return false; // id dokumentu neexistuje
            }
        }
    }

    public function zmenitStav($data) {

        if ( is_array($data) ) {
            
            $dokument_id = $data['id'];
            unset($data['id']);
            $data['date_modified'] = new DateTime();
            $data['user_modified'] = Environment::getUser()->getIdentity()->id;

            $this->update($data, array(array('id=%i',$dokument_id)) );

            return true;
        } else {
            return false;
        }
    }

    protected function generujHash($data) {

        $data = Dokument::obj2array($data);

        unset( $data['id'],$data['md5_hash'],
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
            if ( empty($data->spisovy_znak_id) ) $mess[] = "Není zvolen spisový znak!";
            if ( empty($data->skartacni_znak) ) $mess[] = "Není vyplněn skartační znak!";
            //if ( empty($data->skartacni_lhuta) || $data->skartacni_lhuta !== 0 ) $mess[] = "Není vyplněna skartační lhůta!";
            if ( $data->skartacni_lhuta == null || $data->skartacni_lhuta == "" ) $mess[] = "Není vyplněna skartační lhůta!";
            if ( empty($data->spousteci_udalost_id) ) $mess[] = "Není zvolena spouštěcí událost!";

            if ( count($data->subjekty)==0 ) {
                $mess[] = "Dokument musí obsahovat aspoň jeden subjekt!";
            }

            /*if ( $data->typ_dokumentu->typ != 0 ) {
                if ( count($data->prilohy)==0 ) {
                    $mess[] = "Dokument musí obsahovat aspoň jednu přílohu!";
                }
            }*/
        }

        if ( count($mess)>0 ) {
            return $mess;
        } else {
            return null;
        }

    }

    public function odstranit_rozepsane()
    {

        $where = array('stav=0');

        $seznam = $this->seznamKlasicky(array('where'=>$where));
        if ( count($seznam)>0 ) {
            foreach ( $seznam as $dokument ) {
                $dokument_id = $dokument->id;

                $DokumentLog = new LogModel();
                $DokumentLog->deleteDokument($dokument_id);

                $DokumentSpis = new DokumentSpis();
                $DokumentSpis->delete(array(array('dokument_id=%i',$dokument_id)));

                $DokumentSubjekt = new DokumentSubjekt();
                $DokumentSubjekt->delete(array(array('dokument_id=%i',$dokument_id)));

                $DokumentPrilohy = new DokumentPrilohy();
                $DokumentPrilohy->delete(array(array('dokument_id=%i',$dokument_id)));

                $SouvisejiciDokument = new SouvisejiciDokument();
                $SouvisejiciDokument->delete(array(array('dokument_id=%i',$dokument_id)));
                
                
                $Workflow = new Workflow();
                $Workflow->delete(array(array('dokument_id=%i',$dokument_id)));

                $this->delete(array('id=%i',$dokument_id));
            }
        }
        
        return true;
    }

    public function deleteAll()
    {

        $DokumentHistorie = new DokumentHistorie();
        $DokumentHistorie->deleteAll();

        $Dokument2Subjekt = new DokumentSubjekt();
        $Dokument2Subjekt->deleteAll();
        $Dokument2Prilohy = new DokumentPrilohy();
        $Dokument2Prilohy->deleteAll();
        $Dokument2Odeslani = new DokumentOdeslani();
        $Dokument2Odeslani->deleteAll();
        $Dokument2Spis = new DokumentSpis();
        $Dokument2Spis->deleteAll();
        $DokumentLog = new LogModel();
        $DokumentLog->deleteAllDokument();

        parent::deleteAll();

        $CisloJednaci = new CisloJednaci();
        $CisloJednaci->deleteAll();


    }

    public static function typDokumentu( $kod = null, $select = 0 ) {

        $prefix = Environment::getConfig('database')->prefix;
        $tb_dokument_typ = $prefix .'dokument_typ';

        $result = dibi::query('SELECT * FROM %n', $tb_dokument_typ )->fetchAssoc('id');

        if ( is_null($kod) ) {
            if ( $select == 1 ) { // referent
                $tmp = array();
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    if ( $dt->referent == 1 ) {
                        $tmp[ $dt->id ] = $dt->nazev;
                    }
                }
                return $tmp;
            } else if ( $select == 2 ) { // podatelna
                $tmp = array();
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    if ( $dt->podatelna == 1 ) {
                        $tmp[ $dt->id ] = $dt->nazev;
                    }
                }
                return $tmp;
            } else if ( $select == 3 ) {
                $tmp = array();
                $tmp[0] = 'jakýkoli typ dokumentu';
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
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
                    if ( $dt->stav == 0 ) continue;
                    $tmp[ $dt->id ] = $dt->nazev;
                }
                return $tmp;
            } else if ( $select == 3 ) {
               $tmp = array();
                $tmp[0] = 'jakýkoli způsob vyřízení';
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    $tmp[ $dt->id ] = String::truncate($dt->nazev,90);
                }
                return $tmp;
            } else if ( $select == 4 ) {
               $tmp = array();
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    $tmp[ 'zpusob_vyrizeni_'. $dt->id ] = $dt->nazev;
                }
                return $tmp;
            } else {
                return $result;
            }
        } else {
            return ( array_key_exists($kod, $result) )?$result[ $kod ]:null;
        }

    }

    public static function zpusobDoruceni( $kod = null, $select = 0 ) {

        $prefix = Environment::getConfig('database')->prefix;
        $tb_zpusob_doruceni = $prefix .'zpusob_doruceni';

        $result = dibi::query('SELECT * FROM %n', $tb_zpusob_doruceni )->fetchAssoc('id');

        if ( is_null($kod) ) {
            if ( $select == 1 ) {
                $tmp = array();
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    $tmp[ $dt->id ] = $dt->nazev;
                }
                return $tmp;
            } else if ( $select == 2 ) {
               $tmp = array();
                $tmp[0] = '(vlastní)';
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    $tmp[ $dt->id ] = $dt->nazev;
                }
                return $tmp;                
            } else if ( $select == 3 ) {
               $tmp = array();
                $tmp[0] = 'jakýkoli způsob doručení';
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
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

    public static function zpusobOdeslani( $kod = null, $select = 0 ) {

        $prefix = Environment::getConfig('database')->prefix;
        $tb_zpusob_odeslani = $prefix .'zpusob_odeslani';

        $result = dibi::query('SELECT * FROM %n', $tb_zpusob_odeslani )->fetchAssoc('id');

        if ( is_null($kod) ) {
            if ( $select == 1 ) {
                $tmp = array();
                foreach ($result as $dt) {
                    if ( $dt->stav == 0 ) continue;
                    $tmp[ $dt->id ] = $dt->nazev;
                }
                return $tmp;
            } else if ( $select == 3 ) {
               $tmp = array();
                $tmp[0] = 'jakýkoli způsob odeslani';
                foreach ($result as $dt) {
                    $tmp[ $dt->id ] = String::truncate($dt->nazev,90);
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

class DokumentHistorie extends BaseModel
{

    protected $name = 'dokument_historie';
    protected $primary = 'id';

    
}