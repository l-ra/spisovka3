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

        $sql = array(
            'from' => array($this->name => 'sz'),
            'cols' => array('*'),
            'leftJoin' => array(
                'spousteci_udalost' => array(
                    'from' => array($this->tb_spoudalost => 'udalost'),
                    'on' => array('udalost.id=sz.spousteci_udalost'),
                    'cols' => array('nazev'=>'spousteci_udalost_nazev','stav'=>'spousteci_udalost_stav','poznamka_k_datumu'=>'spousteci_udalost_dtext')
                ),
            ),
            'where' => array(array('sz.id=%i',$spisznak_id))
        );

        $result = $this->fetchAllComplet($sql);
        //$result = $this->fetchRow(array('id=%i',$spisznak_id));
        $row = $result->fetch();
        return ($row) ? $row : NULL;

    }

    public function seznam($args = null, $select = 0, $spisznak_parent = array(0) )
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
        $rows = $this->setridit($rows, $select, $spisznak_parent);

        return ($rows) ? $rows : NULL;

    }

    public function seznam_pod($spisznak_id, $full = 0, &$tmp = array()) {

        $args = array(
            'where' => array(
                'spisznak_parent = %i',$spisznak_id
            )
        );

        $ret = $this->seznam($args, ($full==0)?3:0, array($spisznak_id) );

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

    public function seznam_nad($spisznak_id, $full = 0, &$tmp = array() )
    {

        $result = $this->_seznam_nad($spisznak_id, $full, $tmp);
        if ( isset($result[ $spisznak_id ]) ) {
            unset( $result[ $spisznak_id ] );
        }
        
        return ( count($result)>0 )? $result : null;
    }

    public function _seznam_nad($spisznak_id, $full = 0, &$tmp = array() )
    {

        $spisznak = $this->getInfo($spisznak_id);
        if ( $spisznak ) {

            if ( $full == 1 ) {
                $tmp[ $spisznak->id ] = $spisznak;
            } else {
                $tmp[ $spisznak->id ] = $spisznak->nazev;
            }

            if ( $spisznak->spisznak_parent != 0 ) {
                $this->seznam_nad($spisznak->spisznak_parent, $full, $tmp);
            }

            return $tmp;
        } else {
            return null;
        }

    }

    private function setridit($data, $simple=0, $spisznak_parent = array(0), &$tmp = array() )
    {
        if ( count($data)>0 ) {
            if ( $simple == 1 ) {
                $tmp[0] = 'vyberte z nabídky ...';
            } else if ( $simple == 2 ) {
                $tmp[0] = '(hlavní větev)';
            }
            $spisznak_parent_id = end($spisznak_parent);
            foreach ( $data as $index => $d ) {
                if ( $d->spisznak_parent == $spisznak_parent_id ) {

                    $d->parent = $spisznak_parent;
                    $d->uroven = count($spisznak_parent);
                    $d->class = ' item'. implode(' item',$spisznak_parent) .'';

                    if ( $simple == 1 || $simple == 2 ) {
                        $nazev = str_repeat(".", 2*$d->uroven) .' '. $d->nazev;
                        $tmp[ $d->id ] = $nazev;
                    } else if ( $simple == 3 ) {
                        $tmp[ $d->id ] = $d->nazev;
                    } else {
                        $tmp[ $d->id ] = $d;
                    }

                    $spisznak_parent[] = $d->id;

                    if ( isset($tmp[ $spisznak_parent_id ] ) ) {
                        @$tmp[ $spisznak_parent_id ]->potomky = 1;
                    }

                    $this->setridit($data, $simple, $spisznak_parent, $tmp);
                    end($spisznak_parent);
                    unset( $spisznak_parent[ key($spisznak_parent) ] );

                }
            }

            return $tmp;
        } else {
            return null;
        }
    }

    public function vytvorit($data) {
        $spisznak_id = $this->insert($data);
        return $spisznak_id;
    }

    public function upravit($data,$spisznak_id) {

        $data['uroven'] = 1;
        $data['sekvence'] = null;

        unset($data['spisznak_parent_old']);
        $ret = $this->update($data,array('id=%i',$spisznak_id));

        return $ret;

    }

    public function odstranit($spisznak_id, $potomky = 2)
    {

        if ( empty($spisznak_id) ) return false;

        if ( $potomky == 1 ) {
            // odstranit i potomky
            $potomci = $this->seznam_pod($spisznak_id);
            if ( count($potomci)>0 ) {
                foreach ( $potomci as $id => $sz ) {
                    if ( is_int($id) ) {
                        $this->delete(array('id=%i',$id));
                    }
                }
            }
        } else if ( $potomky == 2 ) {
            // potomky maji noveho rodice
            $rodic = $this->getInfo($spisznak_id);
            $this->update(
                    array('spisznak_parent'=>$rodic->spisznak_parent),
                    array( array('spisznak_parent=%i',$spisznak_id) )
            );
        }

        return $this->delete(array('id=%i',$spisznak_id));

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
