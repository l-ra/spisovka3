<?php //netteloader=Subjekt

class Subjekt extends BaseModel
{

    protected $name = 'subjekt';
    protected $primary = 'id';

    private $staty = array();
    
    
    public function getInfo($subjekt_id)
    {
        $result = $this->select(array(array('id=%i',$subjekt_id)));
        if (count($result) == 0)
            throw new InvalidArgumentException("Subjekt id '$subjekt_id' neexistuje.");
        
        return $result->fetch();
    }

    public function ulozit($data, $subjekt_id = null)
    {

        if ( empty($data['datum_narozeni']) ) $data['datum_narozeni'] = null;
        if ( $data['datum_narozeni'] == "-0001-11-30" ) $data['datum_narozeni'] = null;
        
        if ( !is_null($subjekt_id) ) {

            // ulozit do historie
            $old_data = (array) $this->getInfo($subjekt_id);
            $old_data['subjekt_id'] = $subjekt_id;
            $old_data['user_created'] = Nette\Environment::getUser()->getIdentity()->id;
            $old_data['date_created'] = new DateTime();
            unset($old_data['id'],$old_data['user_modified'],$old_data['date_modified']);
            $SubjektHistorie = new SubjektHistorie();
            $SubjektHistorie->insert($old_data);

            // aktualizovat
            $data['date_modified'] = new DateTime();
            $data['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;
            $this->update($data, array(array('id = %i',$subjekt_id)));

        } else {

            // insert
            $data['date_created'] = new DateTime();
            $data['user_created'] = Nette\Environment::getUser()->getIdentity()->id;
            $data['date_modified'] = new DateTime();
            $data['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;
            $subjekt_id = $this->insert($data);
            
        }

        if ( $subjekt_id ) {
            return $subjekt_id;
        } else {
            return false;
        }
    }

    public function zmenitStav($data) {

        if ( is_array($data) ) {
            
            $subjekt_id = $data['id'];
            unset($data['id']);
            $data['date_modified'] = new DateTime();
            $data['user_modified'] = Nette\Environment::getUser()->getIdentity()->id;

            $this->update($data, array(array('id=%i',$subjekt_id)) );

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
                    /*'order'=> array('nazev_subjektu','prijmeni','jmeno')*/
                    /* P.L. nechapu, proc se zde zabyvat komplikovanym razenim. Subjekt s danou emailovou adresou bude obvykle jeden, ne? */
                    'order_sql' => 'CONCAT(nazev_subjektu,prijmeni,jmeno)'
                );

                if ( strpos($data->email,";")!==false ) {
                    $email_a = explode(";",$data->email);
                    if ( count($email_a)>0 ) {
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
                    if ( count($email_a)>0 ) {
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

                $fetch = $this->selectComplex($sql)->fetchAll();
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
                    'order'=> array('nazev_subjektu','prijmeni','jmeno')
                );
                $fetch = $this->selectComplex($sql)->fetchAll();
                $result = array_merge($result, $fetch);
            }


        } else if ( $typ == 'isds' ) {

            // hledani podle emailu
            if ( !empty($data->id_isds) ) {
                $sql = array(
                    'distinct'=>1,
                    'cols'=>$cols,
                    'where'=> array( array('id_isds LIKE %s','%'.$data->id_isds.'%') ),
                    'order'=> array('nazev_subjektu','prijmeni','jmeno')
                );
                $fetch = $this->selectComplex($sql)->fetchAll();
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
                    'order'=> array('nazev_subjektu','prijmeni','jmeno')
                );
                $fetch = $this->selectComplex($sql)->fetchAll();
                $result = array_merge($result, $fetch);
            }

        }

        $tmp = array();
        if ( count($result)>0 ) {
            foreach ($result as $subjekt) {
                $tmp[ $subjekt->id ] = $this->getInfo($subjekt->id);
                $tmp[ $subjekt->id ]->full_name = Subjekt::displayName($tmp[ $subjekt->id ],'full');
            }
            return $tmp;
        } else {
            return null;
        }

    }

    public function seznam($args = null)
    {
        $params = array();
        
        if ( isset($args['where']) ) {
            $params['where'] = $args['where'];
        }
        
        if ( isset($args['order']) ) {
            $params['order'] = $args['order'];
        } else {
            $params['order_sql'] = "CONCAT(nazev_subjektu,prijmeni,jmeno)";
        }

        if ( isset($args['offset']) ) {
            $params['offset'] = $args['offset'];
        }

        if ( isset($args['limit']) ) {
            $params['limit'] = $args['limit'];
        }

        $res = $this->selectComplex($params);
        return ($res) ? $res : NULL;
    }

    public static function displayName($data, $display = 'basic')
    {

        if (is_string($data))
            return $data;
        if (is_array($data))
            $data = new ArrayObject($data, ArrayObject::ARRAY_AS_PROPS);        
        if (!is_object($data))
            return "";

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
        $prostredni_jmeno_item = "";
        if ( isset( $data->prostredni_jmeno ) ) {
            if ( !empty( $data->prostredni_jmeno ) ) {
                $prostredni_jmeno = $data->prostredni_jmeno ." ";
                $prostredni_jmeno_item = " ". $data->prostredni_jmeno;
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

            $nazev = trim($data->nazev_subjektu);
            $nazev_item = trim($data->nazev_subjektu);
            $osoba = trim($titul_pred . $jmeno ." ". $prostredni_jmeno . $prijmeni . $titul_za);
            $osoba_item = trim($prijmeni." ". $jmeno. $prostredni_jmeno_item . $titul_pred_item . $titul_za);

            if ( !empty($nazev) && !empty($osoba) ) {
                $d_nazev = $nazev .", ". $osoba;
                $d_nazev_item = $nazev_item .", ". $osoba_item;
                $d_osoba = $osoba;
                $d_osoba_item = $osoba_item;
            } else if ( !empty($nazev) && empty($osoba) ) {
                $d_nazev = $nazev;
                $d_nazev_item = $nazev_item;
                $d_osoba = "";
                $d_osoba_item = "";
            } else if ( empty($nazev) && !empty($osoba) ) {
                $d_nazev = $osoba;
                $d_nazev_item = $osoba_item;
                $d_osoba = $osoba;
                $d_osoba_item = $osoba_item;
            } else {
                $d_nazev = "";
                $d_nazev_item = "";
                $d_osoba = "";
                $d_osoba_item = "";
            }

            //if ( strpos(@$data->type,'OVM')!==false || strpos(@$data->type,'PO')!==false ) {
                // nazev subjektu
            //    $d_nazev = $nazev;
            //    $d_nazev_item = $nazev;
            //}


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
            $d_ulice = @$data->adresa_ulice .' '. @$data->adresa_co;
        }

        $d_adresa = $d_ulice .', '. @$data->adresa_psc .' '. @$data->adresa_mesto;
        if (trim($d_adresa) == ',')
            $d_adresa = '';
            
        if ( empty($d_nazev) ) $d_nazev = "(bez názvu)";
        if ( empty($d_osoba) ) $d_osoba = "(bez názvu)";
        if ( empty($d_nazev_item) ) $d_nazev_item = "(bez názvu)";
        if ( empty($d_osoba_item) ) $d_osoba_item = "(bez názvu)";
        
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
                $res = $d_nazev;
                if (!empty($d_adresa))
                    $res .= ', ' . $d_adresa;
                return $res;
            case 'formalni_adresa':
                return $d_ulice .'<br />'. $data->adresa_psc .' '. $data->adresa_mesto .'<br />'. Subjekt::stat($data->adresa_stat,10);
                break;
            case 'plna_formalni_adresa':
                return $d_nazev .'<br />'. $d_ulice .'<br />'. $data->adresa_psc .' '. $data->adresa_mesto .'<br />'. Subjekt::stat($data->adresa_stat,10);
                break;
            case 'ulice':
                return $d_ulice;
                break;
            case 'mesto':
                return $data->adresa_psc .' '. $data->adresa_mesto;
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

    public static function stat($kod = null, $select = 0) {


        $prefix = self::getDbPrefix();
        $tb_staty = $prefix .'stat';

        $result = dibi::query('SELECT nazev,kod FROM %n', $tb_staty,'WHERE stav=1 ORDER BY nazev')->fetchAll();        
        
        $stat = array();
        // Dej CR na prvni misto v seznamu
        $stat['CZE'] = 'Česká republika';
        foreach ($result as $rdata ) {
            $stat[ $rdata->kod ] = $rdata->nazev;
        }
        
        if ( !is_null($kod) )
            return array_key_exists($kod, $stat) ? $stat[$kod] : null;

        if ( $select == 3 )
            return array('' => 'v jakémkoli státě') + $stat;
        else if ( $select == 10 )
            // prazdna hodnota
            return "";

        return $stat;
    }

    public static function typ_subjektu( $kod = null, $select = 0 ) {

        $typ = array( ''=>'Neuveden / neznámý',
                      'OVM'=>'Orgán veřejné moci',
                      'FO'=>'Fyzická osoba',
                      'PFO'=>'Fyzická osoba s podnikatelskou činností',
                      'PO'=>'Firma, subjekt s podnikatelskou činností',
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
            if ( $select == 3 )
                $typ[''] = 'jakýkoli typ subjektu';
            
            return $typ;
        } else {
            return ( array_key_exists($kod, $typ) )?$typ[ $kod ]:null;
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

    public function  deleteAll() {

        $DokumentSubjekt = new DokumentSubjekt();
        $DokumentSubjekt->deleteAll();

        $SubjektHistorie = new SubjektHistorie();
        $SubjektHistorie->deleteAll();

        parent::deleteAll();
    }

}

class SubjektHistorie extends BaseModel
{

    protected $name = 'subjekt_historie';
    protected $primary = 'id';

}