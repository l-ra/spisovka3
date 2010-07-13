<?php

class Spis extends BaseModel
{

    protected $name = 'spis';
    protected $primary = 'spis_id';
    
    
    public function getInfo($spis_id)
    {

        $result = $this->fetchRow(array('spis_id=%i',$spis_id));
        $row = $result->fetch();
        return ($row) ? $row : NULL;

    }

    public function seznam($args = null,$select = 0)
    {

        if ( isset($args['where']) ) {
            $where = array($args['where']);
        } else {
            $where = null;// array(array('stav=1'));
        }

        if ( isset($args['order']) ) {
            $order = $args['order'];
        } else {
            $order = array('sekvence','nazev');
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


        $query = $this->fetchAll($order,$where,$offset,$limit);

        $rows = $query->fetchAll();

        if ( $select == 1 ) {
            $rows = $this->setridit($rows,1);
        } else {
            $rows = $this->setridit($rows);
        }

        return ($rows) ? $rows : NULL;

    }

    public function seznam_pod($spis_id, $full = 0) {


        if ( $spis_id == 1 ) {
            $args = array('where'=>
                            array('sekvence like %s',"%".$spis_id."%")
                    );
        } else {
            $args = array('where'=>
                            array('sekvence like %s',"%-".$spis_id."%")
                    );
        }
        $ret = $this->seznam($args);
        if ( $full == 1 ) {
            return $ret;
        } else {
            $tmp = array();
            if ( count($ret)>0 ) {
                foreach ($ret as $r) {
                    $tmp[] = $r->spis_id;
                }
                return $tmp;
            } else {
                return null;
            }
            
        }

    }

    public function seznam_nad($spis_id, $full = 0) {

        $spis = $this->getInfo($spis_id);
        if ( $spis ) {
            $casti = explode("-",$spis->sekvence);
            if ( $full == 1 ) {
                $where_numbers = implode(",",$casti);
                if ( !empty($where_numbers) ) {
                    $args = array('where'=>array('spis_id IN ('.$where_numbers.')'));
                    return $this->seznam($args);
                } else {
                    return null;
                }
            } else {
                return $casti;
            }
        } else {
            return null;
        }

    }

    private function setridit($data, $simple=0) {
        
        $tmp = array();

        foreach ($data as $index => $d) {

            $sekvence = explode("-",$d->sekvence);
            if ( $sekvence[0] == '' ) {
                $sekvence_array = '';
                $sekvence_class = '';
            } else {
                $sekvence_array = '['. implode('][',$sekvence) .']';
                $sekvence_class = ' item'. implode(' item',$sekvence) .'';
            }
            $d->class = $sekvence_class;

            if ( $simple == 1 ) {
                $nazev = str_repeat(".", 2*$d->uroven) .' '. $d->nazev;
                $string = '$tmp'.$sekvence_array.'['.$d->spis_id.']["spis"] = array("id"=>$d->spis_id,"nazev"=>"'.$nazev.'");';
            } else {
                $string = '$tmp'.$sekvence_array.'['.$d->spis_id.']["spis"] = $d;';
            }
            eval($string);
        }

        $tmp1 = $this->sestav($tmp);

        return $tmp1;
        //return array ( $tmp , $tmp1 ); // pro porovnani vstupu a vystupu
    }

    private function sestav($data,$tmp = array()) {
        foreach ( $data as $index => $d ) {
            if ( $index == "spis" ) {
                if ( is_array($data['spis']) ) {
                    $tmp[ $data['spis']['id'] ] = $data['spis']['nazev'];
                } else {
                    $tmp[ $data['spis']->spis_id ] = $data['spis'];
                }
            } else if ( is_numeric($index) ) {
                $tmp = $this->sestav($data[$index], $tmp);
            }
        }
        return $tmp;

    }

    public function vytvorit($data) {

        if ( $data['spis_parent'] == 1 ) {
            $data['uroven'] = 1;
            $data['sekvence'] = '1';
        } else {
            $spis_parent = $this->getInfo($data['spis_parent']);
            $data['uroven'] = $spis_parent->uroven + 1;
            $data['sekvence'] = $spis_parent->sekvence .'-'. $data['spis_parent'];
        }


        $spis_id = $this->insert($data);

        return $spis_id;

    }

    public function upravit($data,$spis_id) {

        //$transaction = (! dibi::inTransaction());
        //if ($transaction)
        //dibi::begin();

        

        if ( $data['spis_parent'] != $data['spis_parent_old'] ) {
            // nove postaveni ve strukture
            $spis = $this->getInfo($spis_id);
            $spis_parent = $this->getInfo($data['spis_parent']);
            $data['uroven'] = $spis_parent->uroven + 1;

            if ( $data['spis_parent'] == '1' ) {
                $data['sekvence'] = $data['spis_parent'];
            } else {
                $data['sekvence'] = $spis_parent->sekvence .'-'. $data['spis_parent'];
            }
            //Debug::dump($data);
            
            // aplikace postaveni i na vsechny podrizene
            $pod_spisy = $this->seznam_pod($spis_id,1);
            if ( count($pod_spisy)>0 ) {
                foreach( $pod_spisy as $spisyPod ) {
                    $data_pod = array();
                    $data_pod['nazev'] = $spisyPod->nazev;
                    $data_pod['sekvence'] = $data['sekvence'] .'-'. $spisyPod->spis_parent;
                    $data_pod['uroven'] = $data['uroven'] + 1;
                    //Debug::dump($data_pod);
                    $this->update($data_pod,array('spis_id=%i',$spisyPod->spis_id));
                    unset($data_pod);
                }
            }

            //exit;
        }

        unset($data['spis_parent_old']);
        $ret = $this->update($data,array('spis_id=%i',$spis_id));

        //if ($transaction)
        //dibi::commit();

        return $ret;

    }

    public static function typSpisu($typ = null, $sklonovat = 0) {

        $typ_array1 = array('S'=>'spis',
                     'VS'=>'věcná skupina'
                     );
        $typ_array2 = array('S'=>'spisu',
                     'VS'=>'věcné skupiny'
                     );

        $typ_array = ($sklonovat==1)?$typ_array2:$typ_array1;

        if ( is_null($typ) ) {
            return $typ_array;
        } else {
            return array_key_exists($typ, $typ_array)?$typ_array[$typ]:null;
        }


    }

    public static function stav($stav = null) {

        $stav_array = array('1'=>'aktivní',
                            '0'=>'neaktivní'
                     );

        if ( is_null($stav) ) {
            return $stav_array;
        } else {
            return array_key_exists($stav, $stav_array)?$stav_array[$stav]:null;
        }


    }

}
