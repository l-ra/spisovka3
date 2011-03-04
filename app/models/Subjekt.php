<?php //netteloader=Subjekt

class Subjekt extends BaseModel
{

    protected $name = 'subjekt';
    protected $primary = 'id';

    private $staty = array();
    
    
    public function getInfo($subjekt_id, $subjekt_version=null)
    {

        if ( !is_null($subjekt_version) ) {
            $result = $this->fetchRow(array(
                                         array('id=%i',$subjekt_id),
                                         array('version=%i',$subjekt_version)
                                           )
                                     );
        } else {
            $result = $this->fetchAll(array('version'=>'DESC'),array(array('id=%i',$subjekt_id)),null,1);
        }
        $row = $result->fetch();
        return ($row) ? $row : NULL;

    }

    public function getMax()
    {

        $result = $this->fetchAll(array('id'=>'DESC'),null,null,1);
        $row = $result->fetch();
        return ($row) ? ($row->id+1) : 1;

    }

    public function insert_version($data,$subjekt_id=null) {

        //$transaction = (! dibi::inTransaction());
        //if ($transaction)
        //dibi::begin();

        if ( !is_null($subjekt_id) ) {
            // vytvoreni nove verze
            $update = array('stav%sql'=>'stav+100');
            $this->update($update, array('id=%i',$subjekt_id));
            $last_row = $this->getInfo($subjekt_id);

            $data['id'] = $last_row->id;
            $data['version'] = $last_row->version + 1;
        } else {
            // vytvoreni noveho zaznamu
            $data['id'] = $this->getMax();
            $data['version'] = 1;
        }

        $this->insert_basic($data);

        //if ($transaction)
        //dibi::commit();

        $new_row = $this->getInfo($data['id']);
        if ( $new_row ) {
            return $data['id'];
        } else {
            return false;
        }
    }

    public function zmenitStav($data) {

        if ( is_array($data) ) {
            
            $subjekt_id = $data['id'];
            $subjekt_version = $data['version'];
            unset($data['id'],$data['version']);
            $data['date_modified'] = new DateTime();

            //$transaction = (! dibi::inTransaction());
            //if ($transaction)
            //dibi::begin();

            // aktualni verze
            $this->update($data, array(array('stav<100'), array('id=%i',$subjekt_id)) );

            // ostatni verze
            $data['stav'] = $data['stav'] + 100;
            $this->update($data, array(array('stav>=100'), array('id=%i',$subjekt_id)) );

            //if ($transaction)
            //dibi::commit();

            return true;
            
        } else {
            return false;
        }
    }

    public function hledat($data, $typ = 'all') {

        $result = array();
        $cols = array('id');
        if ( $typ == 'email' ) {

            // hledani podle emailu
            if ( !empty($data->email) ) {

                $sql = array(
                    'distinct'=>1,
                    'cols'=>$cols,
                    'order'=> array('version'=>'DESC','nazev_subjektu','prijmeni','jmeno')
                );

                if ( strpos($data->email,";")!==false ) {
                    $email_a = explode(";",$data->email);
                    if ( $count($email_a)>0 ) {
                        $where_or = array();
                        foreach ( $email_a as $ea ) {
                            $ea = trim($ea);
                            if ( !empty($ea) ) {
                                $where_or[] = array('email LIKE %s','%'.$ea.'%');
                            }
                        }
                        $sql['where_or'] = $where_or;
                    } else {
                        $sql['where'] = array( array('email LIKE %s','%'.$data->email.'%') );
                    }
                } else if ( strpos($data->email,",")!==false ) {
                    $email_a = explode(",",$data->email);
                    if ( $count($email_a)>0 ) {
                        $where_or = array();
                        foreach ( $email_a as $ea ) {
                            $ea = trim($ea);
                            if ( !empty($ea) ) {
                                $where_or[] = array('email LIKE %s','%'.$ea.'%');
                            }
                        }
                        $sql['where_or'] = $where_or;
                    } else {
                        $sql['where'] = array( array('email LIKE %s','%'.$data->email.'%') );
                    }
                } else {
                    $sql['where'] = array( array('email LIKE %s','%'.$data->email.'%') );
                }

                $fetch = $this->fetchAllComplet($sql)->fetchAll();
                $result = array_merge($result, $fetch);
            }

            // hledani podle nazvu, prijmeni nebo jmena
            if ( !empty($data->nazev_subjektu) ) {
                $sql = array(
                    'distinct'=>1,
                    'cols'=>$cols,
                    'where_or'=> array( 
                        array('nazev_subjektu LIKE %s','%'.$data->nazev_subjektu.'%'),
                        array("CONCAT(prijmeni,' ',jmeno) LIKE %s",'%'.$data->nazev_subjektu.'%'),
                        array("CONCAT(jmeno,' ',prijmeni) LIKE %s",'%'.$data->nazev_subjektu.'%')
                    ),
                    'order'=> array('version'=>'DESC','nazev_subjektu','prijmeni','jmeno')
                );
                $fetch = $this->fetchAllComplet($sql)->fetchAll();
                $result = array_merge($result, $fetch);
            }


        } else if ( $typ == 'isds' ) {

            // hledani podle emailu
            if ( !empty($data->id_isds) ) {
                $sql = array(
                    'distinct'=>1,
                    'cols'=>$cols,
                    'where'=> array( array('id_isds LIKE %s','%'.$data->id_isds.'%') ),
                    'order'=> array('version'=>'DESC','nazev_subjektu','prijmeni','jmeno')
                );
                $fetch = $this->fetchAllComplet($sql)->fetchAll();
                $result = array_merge($result, $fetch);
            }

            // hledani podle nazvu, prijmeni nebo jmena
            if ( !empty($data->nazev_subjektu) ) {
                $sql = array(
                    'distinct'=>1,
                    'cols'=>$cols,
                    'where_or'=> array(
                        array('nazev_subjektu LIKE %s','%'.$data->nazev_subjektu.'%'),
                        array("CONCAT(prijmeni,' ',jmeno) LIKE %s",'%'.$data->nazev_subjektu.'%'),
                        array("CONCAT(jmeno,' ',prijmeni) LIKE %s",'%'.$data->nazev_subjektu.'%')
                    ),
                    'order'=> array('version'=>'DESC','nazev_subjektu','prijmeni','jmeno')
                );
                $fetch = $this->fetchAllComplet($sql)->fetchAll();
                $result = array_merge($result, $fetch);
            }

        }

        $tmp = array();
        if ( count($result)>0 ) {
            foreach ($result as $subjekt) {
                $tmp[ $subjekt->id ] = $this->getInfo($subjekt->id);
            }
            return $tmp;
        } else {
            return null;
        }

    }

    public function seznam($args = null)
    {

        if ( isset($args['where']) ) {
            $where = array($args['where']);
        } else {
            $where = array(array('stav<100'));
        }

        if ( isset($args['order']) ) {
            $order = $args['order'];
        } else {
            //$order = array('nazev_subjektu','prijmeni','jmeno');
            $order = "CONCAT(nazev_subjektu,prijmeni,jmeno)";
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


        $select = $this->fetchAllSpecialOrder($order,$where,$offset,$limit);
        return ($select) ? $select : NULL;

        //$rows = $select->fetchAll();
        //return ($rows) ? $rows : NULL;

    }

    public static function displayName($data, $display = 'basic')
    {

        if ( is_string($data) ) return $data;
        if ( is_array($data) ) {
            $tmp = new stdClass();
            $tmp->type = $data['type'];
            $tmp->nazev_subjektu = $data['nazev_subjektu'];
            $tmp->ic = $data['ic'];
            $tmp->jmeno = $data['jmeno'];
            $tmp->prostredni_jmeno = $data['prostredni_jmeno'];
            $tmp->prijmeni = $data['prijmeni'];
            $tmp->titul_pred = $data['titul_pred'];
            $tmp->titul_za = $data['titul_za'];
            $tmp->adresa_ulice = $data['adresa_ulice'];
            $tmp->adresa_cp = $data['adresa_cp'];
            $tmp->adresa_co = $data['adresa_co'];
            $tmp->adresa_mesto = $data['adresa_mesto'];
            $tmp->adresa_psc = $data['adresa_psc'];
            $tmp->adresa_stat = $data['adresa_stat'];
            $tmp->email = $data['email'];
            $tmp->telefon = $data['telefon'];
            $tmp->id_isds = $data['id_isds'];

            $data = $tmp;
            unset($tmp);
        }
        if ( !is_object($data) ) return "";

        // Sestaveni casti

        $titul_pred = "";
        $titul_pred_item = "";
        if ( isset( $data->titul_pred ) ) {
            if ( !empty( $data->titul_pred ) ) {
                $titul_pred = $data->titul_pred ." ";
                $titul_pred_item = ", ". $data->titul_pred;
            }
        }

        $jmeno = "";
        if ( isset( $data->jmeno ) ) {
            if ( !empty( $data->jmeno ) ) {
                $jmeno = $data->jmeno;
            }
        }

        $prostredni_jmeno = "";
        if ( isset( $data->prostredni_jmeno ) ) {
            if ( !empty( $data->prostredni_jmeno ) ) {
                $prostredni_jmeno = $data->prostredni_jmeno ." ";
            }
        }

        $prijmeni = "";
        if ( isset( $data->prijmeni ) ) {
            if ( !empty( $data->prijmeni ) ) {
                $prijmeni = $data->prijmeni;
            }
        }

        $titul_za = "";
        if ( isset( $data->titul_za ) ) {
            if ( !empty( $data->titul_za ) ) {
                $titul_za = ', '. $data->titul_za;
            }
        }

        if ( strpos(@$data->type,'OVM')!==false || strpos(@$data->type,'PO')!==false ) {
            // nazev subjektu
            $d_nazev = $data->nazev_subjektu;
            $d_nazev_item = $data->nazev_subjektu;
        } else {
            // jmeno a prijmeni
            $d_nazev = $titul_pred . $jmeno ." ". $prostredni_jmeno . $prijmeni . $titul_za;
            $d_nazev_item = $prijmeni ." ". $jmeno .' '. $prostredni_jmeno . $titul_pred_item . $titul_za;
            $d_osoba = $titul_pred . $jmeno ." ". $prostredni_jmeno . $prijmeni . $titul_za;
            $d_osoba_item = $titul_pred . $jmeno ." ". $prostredni_jmeno . $prijmeni . $titul_za;
        }


        // sestaveni adresy
        if ( !empty($data->adresa_co) && !empty($data->adresa_cp) && !empty($data->adresa_ulice) ) {
            $d_ulice = @$data->adresa_ulice .' '. @$data->adresa_cp .'/'. @$data->adresa_co;
        } else if ( empty($data->adresa_ulice) && !empty($data->adresa_cp) ) {
            $d_ulice = 'č.p. '. @$data->adresa_cp;
        } else if ( empty($data->adresa_co) && empty($data->adresa_cp) ) {
            $d_ulice = @$data->adresa_ulice;
        } else if ( empty($data->adresa_co) ) {
            $d_ulice = @$data->adresa_ulice .' '. @$data->adresa_cp;
        } else if ( empty($data->adresa_ulice) ) {
            $d_ulice = '';
        } else {
            $d_ulice = @$data->adresa_ulice .' '. @$data->adresa_cp .'/'. @$data->adresa_co;
        }

        $d_adresa = $d_ulice .', '. @$data->adresa_psc .' '. @$data->adresa_mesto;

        // Sestaveni nazvu
        switch ($display) {
            case 'full':
                $res = $d_nazev .', '. $d_adresa;
                if ( !empty($data->email) ) { $res .= ', '. $data->email; }
                if ( !empty($data->telefon) ) { $res .= ', '. $data->telefon; }
                if ( !empty($data->id_isds) ) { $res .= ', '. $data->id_isds; }
                return $res;
                break;
            case 'jmeno':
                return $d_nazev;
                break;
            case 'osoba':
                return $d_osoba;
                break;
            case 'jmeno_item':
                return $d_nazev_item;
                break;
            case 'osoba_item':
                return $d_osoba_item;
                break;
            case 'adresa':
                return $d_adresa;
                break;
            case 'plna_adresa':
                return $d_nazev .', '. $d_adresa;
                break;
            case 'formalni_adresa':
                return $d_ulice .'<br />'. $data->adresa_psc .' '. $data->adresa_mesto .'<br />'. Subjekt::stat($data->adresa_stat);
                break;
            case 'plna_formalni_adresa':
                return $d_nazev .'<br />'. $d_ulice .'<br />'. $data->adresa_psc .' '. $data->adresa_mesto .'<br />'. Subjekt::stat($data->adresa_stat);
                break;
            case 'ulice':
                return $d_ulice;
                break;
            case 'email':
                return $d_nazev .' ('. ( empty($data->email)?'nemá email':$data->email ) .')';
                break;
            case 'isds':
                return $d_nazev .' ('. ( empty($data->id_isds)?'nemá datovou schránku':$data->id_isds ) .')';
                break;
            case 'telefon':
                return $d_nazev .' ('. ( empty($data->telefon)?'nemá telefon':$data->telefon ) .')';
                break;
            default:
                return $d_nazev .', '. $d_adresa;
                break;
        }
    }

    public static function stat($kod = null) {

        $stat = array('CZE'=>'Česká republika',
                      'SVK'=>'Slovenská republika'
                     );

        if ( is_null($kod) ) {
            return $stat;
        } else {
            return ( key_exists($kod, $stat) )?$stat[ $kod ]:null;
        }

    }

    public static function typ_subjektu( $kod = null ) {

        $typ = array('OVM'=>'Orgán veřejné moci',
                      'FO'=>'Fyzická osoba',
                      'PFO'=>'Fyzická osoba s podnikatelskou činnosti',
                      'PO'=>'Firma, subjekt s podnikatelskou činnosti',
                      'PFO_ADVOK'=>'PFO - advokáti',
                      'PFO_DANPOR'=>'PFO - daňoví poradci',
                      'PFO_INSSPR'=>'PFO - insolvenční správci',
                      'OVM_NOTAR'=>'OVM - notáři',
                      'OVM_EXEKUT'=>'OVM - exekutoři',
                      'OVM_REQ'=>'Podřízené OVM vzniklé na základě žádosti (§6 a 7)',
                      'PO_ZAK'=>'PO vzniklé ze zákona',
                      'PO_REQ'=>'PO vzniklé na žádost'
                     );

        if ( is_null($kod) ) {
            return $typ;
        } else {
            return ( key_exists($kod, $typ) )?$typ[ $kod ]:null;
        }
        
    }

    public static function stav($subjekt = null) {

        $stavy = array('1'=>'aktivní',
                       '2'=>'neaktivní',
                       '3'=>'zrušený'
            );

        if ( is_null( $subjekt ) ) {
            return $stavy;
        } else if ( !is_numeric($subjekt) ) {
            return null;
        }

        $index = ($subjekt>=100)?$subjekt-100:$subjekt;
        if ( array_key_exists($index, $stavy) ) {
         return $stavy[ $index ];
        } else {
            return null;
        }



    }

}
