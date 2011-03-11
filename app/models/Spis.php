<?php

class Spis extends BaseModel
{

    protected $name = 'spis';
    protected $primary = 'id';
    
    
    public function getInfo($spis_id)
    {

        if ( !is_numeric($spis_id) ) {
            // string - nazev
            $result = $this->fetchRow(array('nazev=%s',$spis_id));
        } else {
            // int - id
            $result = $this->fetchRow(array('id=%i',$spis_id));
        }
        
        $row = $result->fetch();
        return ($row) ? $row : NULL;

    }

    public function seznam($args = null, $select = 0, $spis_parent = array(0))
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
        $rows = $this->setridit($rows, $select, $spis_parent);

        return ($rows) ? $rows : NULL;

    }

    public function seznam_pod($spis_id, $full = 0, &$tmp = array()) {


        $args = array(
            'where' => array(
                'spis_parent = %i',$spis_id
            )
        );

        $ret = $this->seznam($args, ($full==0)?3:0, array($spis_id) );

        if ( count($ret)>0 ) {
            foreach ( $ret as $id => $s ) {
                $tmp[ $id ] = $s;
                $this->seznam_pod($id, $full, $tmp);
            }
            return $tmp;
        } else {
            return null;
        }

    }

    public function seznam_nad($spis_id, $full = 0, &$tmp = array()) {

        $result = $this->_seznam_nad($spis_id, $full, $tmp);
        if ( isset($result[ $spis_id ]) ) {
            unset( $result[ $spis_id ] );
        }

        return ( count($result)>0 )? $result : null;


    }

    public function _seznam_nad($spis_id, $full = 0, &$tmp = array() )
    {

        $spis = $this->getInfo($spis_id);
        if ( $spis ) {

            if ( $full == 1 ) {
                $tmp[ $spis->id ] = $spis;
            } else {
                $tmp[ $spis->id ] = $spis->nazev;
            }

            if ( $spis->spis_parent != 0 ) {
                $this->seznam_nad($spis->spis_parent, $full, $tmp);
            }

            return $tmp;
        } else {
            return null;
        }

    }

    private function setridit($data, $simple=0, $spis_parent = array(0), &$tmp = array() )
    {
        if ( count($data)>0 ) {
            if ( $simple == 1 ) {
                $tmp[0] = 'vyberte z nabídky ...';
            } else if ( $simple == 2 ) {
                $tmp[0] = '(hlavní větev)';
            }
            $spis_parent_id = end($spis_parent);
            foreach ( $data as $index => $d ) {
                if ( $d->spis_parent == $spis_parent_id ) {

                    $d->parent = $spis_parent;
                    $d->uroven = count($spis_parent);
                    $d->class = ' item'. implode(' item',$spis_parent) .'';

                    if ( $simple == 1 || $simple == 2 ) {
                        $nazev = str_repeat(".", 2*$d->uroven) .' '. $d->nazev;
                        $tmp[ $d->id ] = $nazev;
                    } else if ( $simple == 3 ) {
                        $tmp[ $d->id ] = $d->nazev;
                    } else {
                        $tmp[ $d->id ] = $d;
                    }

                    $spis_parent[] = $d->id;

                    if ( isset($tmp[ $spis_parent_id ] ) ) {
                        @$tmp[ $spis_parent_id ]->potomky = 1;
                    }

                    $this->setridit($data, $simple, $spis_parent, $tmp);
                    end($spis_parent);
                    unset( $spis_parent[ key($spis_parent) ] );

                }
            }

            return $tmp;
        } else {
            return null;
        }
    }

    public function vytvorit($data) {

        $spis_id = $this->insert($data);
        return $spis_id;

    }

    public function upravit($data,$spis_id) {

        $data_pod['sekvence'] = null;
        $data_pod['uroven'] = 1;

        unset($data['spis_parent_old']);
        $ret = $this->update($data,array('id=%i',$spis_id));
        
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

        $stav_array = array('1'=>'otevřený',
                            '0'=>'uzavřený'
                     );

        if ( is_null($stav) ) {
            return $stav_array;
        } else {
            return array_key_exists($stav, $stav_array)?$stav_array[$stav]:null;
        }


    }

}
