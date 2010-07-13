<?php

class Epodatelna extends BaseModel
{

    protected $name = 'epodatelna';
    protected $primary = 'epodatelna_id';
    protected $tb_file = 'file';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_file = $prefix . $this->tb_file;

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
            $order = array('epodatelna_id'=>'DESC');
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
            'from' => array($this->name => 'ep'),
            'where' => $where,
            'where_or' => $where_or,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
            'leftJoin' => array(
                'dokument' => array(
                    'from' => array($this->tb_file => 'f'),
                    'on' => array('f.file_id=ep.source_id'),
                    'cols' => array('real_path')
                )
            )
        
        );

        //echo "<pre>";
        //print_r($sql);
        //echo "</pre>";

        $select = $this->fetchAllComplet($sql);
        //$result = $select->fetchAll();

        return $select;

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
            $order = array('cislo_jednaci'=>'DESC','datum_vytvoreni'=>'DESC');
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
                array('d.nazev LIKE %s','%'.$query.'%')
                //array( $this->tb_spis .'.nazev LIKE %s','%'.$query.'%')
            )
        );
        return $args;

    }

    public function existuje($id_zpravy, $typ = 'ie') {

        if ( $typ == 'isds' ) {
            $args = array(
                'where' => array(
                    array('isds_signature = %s',$id_zpravy)
                )
            );
        } else if ( $typ == 'email' ) {
            $args = array(
                'where' => array(
                    array('email_signature = %s',$id_zpravy)
                )
            );
        } else if ( $typ == 'vse' ) {
            $args = array(
                'where_or' => array(
                    array('isds_signature = %s',$id_zpravy),
                    array('email_signature = %s',$id_zpravy)
                )
            );
        } else {
            return 0;
        }

        $query = $this->fetchAllComplet($args);
        return $query->count();

    }

    public function filtr($nazev, $params = null) {

        $user = Environment::getUser()->getIdentity();

        switch ($nazev) {
            case 'moje':
                $args = array(
                    'where' => array( array('wf.prideleno=%i',$user->user_id),array('wf.stav_osoby=1 OR wf.stav_osoby=2') )
                );
                break;
            case 'predane':
                $args = array(
                    'where' => array( array('wf.prideleno=%i',$user->user_id),array('wf.stav_osoby=0') )
                );
                break;
            case 'pracoval':
                $args = array(
                    'where' => array( array('wf.prideleno=%i',$user->user_id),array('wf.stav_osoby < 100') )
                );
                break;
            case 'moje_nove':
                $args = array(
                    'where' => array( array('wf.prideleno=%i',$user->user_id),array('wf.stav_osoby = 1'), array('wf.stav_dokumentu = 1') )
                );
                break;
            case 'vsichni_nove':
                $args = array(
                    'where' => array( array('wf.stav_dokumentu = 1') )
                );
                break;
            case 'moje_vyrizuje':
                $args = array(
                    'where' => array( array('wf.prideleno=%i',$user->user_id),array('wf.stav_osoby = 1'), array('wf.stav_dokumentu = 3') )
                );
                break;
            case 'vsichni_vyrizuji':
                $args = array(
                    'where' => array( array('wf.stav_dokumentu = 3') )
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

    public function getInfo($epodatelna_id, $detail = 0) {

        $args = array(
            'where' => array(
                array('epodatelna_id=%i',$epodatelna_id)
            )
        );


        $query = $this->fetchAllComplet($args);
        return $query->fetch();

    }

    public function getMax($smer = 0) {

        $result = $this->fetchAll(array('poradi'=>'DESC'),array('rok'=>date('Y'),array('epodatelna_typ=%i',$smer)),null,null,1);
        $row = $result->fetch();
        return ($row) ? ($row->poradi+1) : 1;

    }

    public function ulozit($data, $dokument_id = null, $dokument_version = null) {

        if ( is_null($data) ) {
            return false;
        } else if ( is_null($dokument_id) ) {
            // novy dokument

            //dibi::begin('newdok');

            $data['dokument_id'] = $this->getMax();
            $data['dokument_version'] = 1;
            $data['date_created'] = new DateTime();
            $data['user_created'] = Environment::getUser()->getIdentity()->user_id;
            $data['stav'] = isset($data['stav'])?$data['stav']:1;
            $data['md5_hash'] = $this->generujHash($data);
            $this->insert_basic($data);
            $new_row = $this->getInfo($data['dokument_id']);

            //if ($transaction)
            //dibi::commit('newdok');

            if ( $new_row ) {
                return $new_row;// array( 'dokument_id'=> $new_row->dokument_id, 'dokument_version'=>$new_row->dokument_version );
            } else {
                return false;
            }
        } else {
            // uprava existujiciho dokumentu

            //dibi::begin('newdok');

            $md5_hash = $this->generujHash($data);
            $old_dokument = $this->getBasicInfo($dokument_id);

            if ( $old_dokument ) {
                if ( !is_null($dokument_version) ) {
                    // update na stavajici verzi

                    $data['date_modified'] = new DateTime();
                    $data['user_modified'] = Environment::getUser()->getIdentity()->user_id;
                    unset($data['dokument_id'],$data['dokument_version']);
                    $updateres = $this->update($data, array(
                                                    array('dokument_id=%i',$dokument_id),
                                                    array('dokument_version=%i',$dokument_version)
                                                    )
                                               );
                    //if ($transaction) dibi::commit();
                    //dibi::commit('newdok');
                    if ( $updateres ) {
                        return array( 'dokument_id'=> $dokument_id, 'dokument_version'=>$dokument_version );
                    } else {
                        return false;
                    }
                } else {

                    if ( $md5_hash == $old_dokument->md5_hash  ) {

                        // shodny hash - zadna zmena - pouze update
                        $data['date_modified'] = new DateTime();
                        $data['user_modified'] = Environment::getUser()->getIdentity()->user_id;
                        unset($data['dokument_id'],$data['dokument_version']);
                        $updateres = $this->update($data, array(
                                                    array('dokument_id=%i',$old_dokument->dokument_id),
                                                    array('dokument_version=%i',$old_dokument->dokument_version)
                                                    )
                                               );
                        //if ($transaction) dibi::commit();
                        //dibi::commit('newdok');
                        if ( $updateres ) {
                            return array( 'dokument_id'=> $old_dokument->dokument_id, 'dokument_version'=>$old_dokument->dokument_version );
                        } else {
                            return false;
                        }
                    } else {

                        // zjistena zmena - nova verze
                        $last_row = $this->getBasicInfo($dokument_id,$dokument_version);
                        $update = array('stav%sql'=>'stav+100');
                        $this->update($update, array('dokument_id=%i',$dokument_id));

                        $in_data = $data;
                        $md5_hash = $this->generujHash($data);
                        $data = $this->obj2array($last_row);
                        $data['dokument_id'] = $last_row->dokument_id;
                        $data['dokument_version'] = $last_row->dokument_version + 1;

                        $data['nazev'] = $in_data['nazev'];
                        $data['popis'] = $in_data['popis'];
                        $data['typ_dokumentu'] = $in_data['typ_dokumentu'];
                        $data['cislo_jednaci_odesilatele'] = $in_data['cislo_jednaci_odesilatele'];
                        $data['datum_vzniku'] = $in_data['datum_vzniku'];
                        $data['poznamka'] = $in_data['poznamka'];

                        $data['date_created'] = new DateTime();
                        $data['user_created'] = Environment::getUser()->getIdentity()->user_id;
                        $data['stav'] = 1;
                        $data['md5_hash'] = $md5_hash;

                        $this->insert_basic($data);
                        $new_row = $this->getBasicInfo($data['dokument_id']);

                        //if ($transaction)
                        //dibi::commit('newdok');

                        if ( $new_row ) {
                            return array( 'dokument_id'=> $new_row->dokument_id, 'dokument_version'=>$new_row->dokument_version );
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
            
            $dokument_id = $data['dokument_id'];
            $dokument_version = $data['dokument_version'];
            unset($data['dokument_id'],$data['dokument_version']);
            $data['date_modified'] = new DateTime();

            //$transaction = (! dibi::inTransaction());
            //if ($transaction)
            //dibi::begin('stavdok');

            // aktualni verze
            $this->update($data, array(array('stav<100'), array('dokument_id=%i',$dokument_id)) );

            // ostatni verze
            $data['stav'] = $data['stav'] + 100;
            $this->update($data, array(array('stav>=100'), array('dokument_id=%i',$dokument_id)) );

            //if ($transaction)
            //dibi::commit('stavdok');

            return true;
            
        } else {
            return false;
        }
    }

    protected function generujHash($data,$orig = null) {

        $data = Dokument::obj2array($data);
        /*$orig = Dokument::obj2array($orig);

        if ( !is_null($orig) ) {
            foreach ($orig as $key => $value) {
                if ( !array_key_exists($key, $data) ) {
                    unset( $orig[$key] );
                }
            }
        }*/

        /** TODO
         * 
         * Zajistit konzistenci - pokud mozno na vsechny sloupce
         * - tj. k vstupnim datum pridat i ulozene zaznamy z DB
         * muze dojit k externimu zasahu a tim padem by pak melo dojit k poskozeni
         * 
         */

        unset( $data['dokument_id'],$data['dokument_version'],$data['stav'],$data['md5hash'],
               $data['date_created'],$data['user_created'],$data['date_modified'],$data['user_modified'],
               $data['predani_poznamka']
             );

        $data_implode = implode('#', $data);

        // věc#popis#1##2010-05-23#30##0#9#OUV-9/2010#denik#9#2010
        // věc#popis#1##2010-05-23#věc#popis#1##2010-05-23#
        //echo $data_implode;
        return md5($data_implode);

    }

    public static function typDokumentu( $kod = null, $select = 0 ) {

        $prefix = Environment::getConfig('database')->prefix;
        $tb_dokument_typ = $prefix .'dokument_typ';

        $result = dibi::query('SELECT * FROM %n', $tb_dokument_typ )->fetchAssoc('dokument_typ_id');

        if ( is_null($kod) ) {
            if ( $select == 1 ) {
                $tmp = array();
                foreach ($result as $dt) {
                    $tmp[ $dt->dokument_typ_id ] = $dt->nazev;
                }
                return $tmp;
            } else {
                return $result;
            }
        } else {
            return ( @key_exists($kod, $result) )?$result[ $kod ]:null;
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

    public static function array2obj($data) {

        if ( is_object($data) ) {
            return $data;
        } else if ( is_array($data) ) {
            $tmp = new stdClass();
            foreach ($data as $key => $value) {
                $tmp->$key = $value;
            }
            return $tmp;
        } else {
            return null;
        }
        
    }

    public static function obj2array($data) {

        if ( is_array($data) ) {
            return $data;
        } else if ( is_object($data) ) {
            $tmp = array();
            foreach ($data as $key => $value) {
                $tmp[$key] = $value;
            }
            return $tmp;
        } else {
            return null;
        }

    }

}
