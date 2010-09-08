<?php

class SpisovyZnak extends BaseModel
{

    protected $name = 'spisovy_znak';
    protected $primary = 'id';
    protected $tb_spoudalost = 'spousteci_udalost';


    public function __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_spoudalost = $prefix . $this->tb_spoudalost;

        
    }


   public function getInfo($spisznak_id)
    {

        $result = $this->fetchRow(array('id=%i',$spisznak_id));
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

    public function seznam_pod($spisznak_id, $full = 0) {

        if ( $spisznak_id == 1 ) {
            $args = array('where'=>
                            array('sekvence like %s',"%".$spisznak_id."%")
                    );
        } else {
            $args = array('where'=>
                            array('sekvence like %s',"%.".$spisznak_id."%")
                    );
        }
        $ret = $this->seznam($args);
        if ( $full == 1 ) {
            return $ret;
        } else {
            $tmp = array();
            if ( count($ret)>0 ) {
                foreach ($ret as $r) {
                    $tmp[] = $r->id;
                }
                return $tmp;
            } else {
                return null;
            }
            
        }

    }

    public function seznam_nad($spisznak_id, $full = 0) {

        $spis = $this->getInfo($spisznak_id);
        if ( $spis ) {
            $casti = explode(".",$spis->sekvence);
            if ( $full == 1 ) {
                $where_numbers = implode(",",$casti);
                if ( !empty($where_numbers) ) {
                    $args = array('where'=>array('id IN ('.$where_numbers.')'));
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
                $string = '$tmp'.$sekvence_array.'['.$d->id.']["spisznak"] = array("id"=>$d->id,"nazev"=>"'.$nazev.'");';
            } else {
                $string = '$tmp'.$sekvence_array.'['.$d->id.']["spisznak"] = $d;';
            }
            eval($string);
        }

        $tmp1 = $this->sestav($tmp);

        return $tmp1;
        //return array ( $tmp , $tmp1 ); // pro porovnani vstupu a vystupu
    }

    private function sestav($data,$tmp = array()) {

        // TODO zjistit chybu na undefined $data['spisznak']

        foreach ( $data as $index => $d ) {
            if ( $index == "spisznak" ) {
                if ( @is_array($data['spisznak']) ) {
                    $tmp[ @$data['spisznak']['id'] ] = @$data['spisznak']['nazev'];
                } else {
                    $tmp[ @$data['spisznak']->id ] = @$data['spisznak'];
                }
            } else if ( is_numeric($index) ) {
                $tmp = $this->sestav($data[$index], $tmp);
            }
        }
        return $tmp;

    }

    public function vytvorit($data) {

        if ( $data['spisznak_parent'] == 1 ) {
            $data['uroven'] = 1;
            $data['sekvence'] = '1';
        } else {
            $spisznak_parent = $this->getInfo($data['spisznak_parent']);
            if ( $spisznak_parent ) {
                $data['uroven'] = $spisznak_parent->uroven + 1;
                $data['sekvence'] = $spisznak_parent->sekvence .'.'. $data['spisznak_parent'];
            } else {
                $data['uroven'] = 1;
                $data['sekvence'] = $data['spisznak_parent'];
            }
        }


        $spisznak_id = $this->insert($data);

        return $spisznak_id;

    }

    public function upravit($data,$spisznak_id) {

        //$transaction = (! dibi::inTransaction());
        //if ($transaction)
        //dibi::begin();

        //Debug::dump($data); exit;

        if ( $data['spisznak_parent'] !== $data['spisznak_parent_old'] ) {
            // nove postaveni ve strukture
            $spisznak = $this->getInfo($spisznak_id);
            $spisznak_parent = $this->getInfo($data['spisznak_parent']);
            $data['uroven'] = $spisznak_parent->uroven + 1;

            if ( $data['spisznak_parent'] == '1' ) {
                $data['sekvence'] = $data['spisznak_parent'];
            } else {
                $data['sekvence'] = $spisznak_parent->sekvence .'.'. $data['spisznak_parent'];
            }
            //Debug::dump($data);
            
            // aplikace postaveni i na vsechny podrizene
            $pod_spisznaky = $this->seznam_pod($spisznak_id,1);
            if ( count($pod_spisznaky)>0 ) {
                foreach( $pod_spisznaky as $spisznakyPod ) {
                    $data_pod = array();
                    $data_pod['nazev'] = $spisznakyPod->nazev;
                    $data_pod['sekvence'] = $data['sekvence'] .'.'. $spisznakyPod->spisznak_parent;
                    $data_pod['uroven'] = $data['uroven'] + 1;
                    //Debug::dump($data_pod);
                    $this->update($data_pod,array('id=%i',$spisznakyPod->id));
                    unset($data_pod);
                }
            }

            //exit;
        }

        unset($data['spisznak_parent_old']);
        $ret = $this->update($data,array('id=%i',$spisznak_id));

        //if ($transaction)
        //dibi::commit();

        return $ret;

    }

    public static function spousteci_udalost( $kod = null, $select = 0 ) {

        $prefix = Environment::getConfig('database')->prefix;
        $tb_spoudalost = $prefix .'spousteci_udalost';
        
        $result = dibi::query('SELECT * FROM %n', $tb_spoudalost)->fetchAssoc('id');

        $tmp = new stdClass();
            $tmp->id = 0;
            $tmp->nazev = 'Žádná';
            $tmp->poznamka = '';
            $tmp->stav = 1;
        $result[''] = $tmp;
        unset($tmp);

        if ( is_null($kod) ) {
            if ( $select == 1 ) {
                $tmp = array();
                $tmp[''] = 'Žádná';
                foreach ($result as $dt) {
                    $tmp[ $dt->id ] = String::truncate($dt->nazev,90);
                }
                return $tmp;
            } else {
                return $result;
            }
        } else {
            return ( array_key_exists($kod, $result) )?$result[ $kod ]->nazev:'';
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
