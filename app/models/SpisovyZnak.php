<?php

class SpisovyZnak extends TreeModel
{

    protected $name = 'spisovy_znak';
    protected $primary = 'id';
    protected $tb_spoudalost = 'spousteci_udalost';


    public function __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_spoudalost = $prefix . $this->tb_spoudalost;
        
    }

    public function seznam($args = null, $select = 0, $spisznak_parent = null )
    {

        $params = null;
        if ( !is_null($args) ) {
            $params['where'] = $args['where'];
        } else {
            //$params['where'] = array(array('stav=1'));
        }
        if ( $select == 5 ) {
            $params['paginator'] = 1;
        }
        
        return $this->nacti($spisznak_parent, true, true, $params);
    }

    public function seznamNativ($args = null, $select = 0, $spisznak_parent = null )
    {

        $sql = array(
            'from' => array($this->name => 'tb'),
            'cols' => array('*'),
        );
        //$sql['order_sql'] = "LENGTH(tb.nazev), tb.nazev";
        //$sql['order'] = array('tb.nazev');
        
        if ( isset($args['where']) ) { 
            if ( isset($sql['where']) ) {
                $sql['where'] = array_merge($sql['where'],$args['where']);     
            } else {
                $sql['where'] = $args['where']; 
            }
        }        
        
        $result = $this->fetchAllComplet($sql);
        if ( $select > 0 ) {
            return $result;
        } else {
            $rows = $result->fetchAll();
            return ($rows) ? $rows : NULL;
        }
    }    

    public function ma_podrizene_spisove_znaky($id)
    {
        $sql = array(
            'from' => array($this->name => 'tb'),
            'cols' => array('id'),
            'where' => array(array("parent_id=%i", $id)),
        );
        
        $result = $this->fetchAllComplet($sql);
        $rows = $result->fetchAll();
        return $rows != false;
    }
    
    public function getInfo($spisznak_id)
    {

        $sql = array(
            'from' => array($this->name => 'sz'),
            'cols' => array('*'),
            'leftJoin' => array(
                'spousteci_udalost' => array(
                    'from' => array($this->tb_spoudalost => 'udalost'),
                    'on' => array('udalost.id=sz.spousteci_udalost_id'),
                    'cols' => array('nazev'=>'spousteci_udalost_nazev','stav'=>'spousteci_udalost_stav','poznamka_k_datumu'=>'spousteci_udalost_dtext')
                ),
            ),
            'where' => array(array('sz.id=%i',$spisznak_id))
        );

        $result = $this->fetchAllComplet($sql);
        $row = $result->fetch();
        if ($row)
            return $row;
            
        throw new InvalidArgumentException("Spisový znak id '$spisznak_id' neexistuje.");
    }

    public function vytvorit($data) {

        $data['stav'] = 1;
        $data['date_created'] = new DateTime();
        $data['user_created'] = (int) Environment::getUser()->getIdentity()->id;
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = (int) Environment::getUser()->getIdentity()->id;

        if ( empty($data['spousteci_udalost_id']) ) $data['spousteci_udalost_id'] = 3;
        if ( !isset($data['parent_id']) ) $data['parent_id'] = null;
        if ( empty($data['parent_id']) ) $data['parent_id'] = null;

        if ( !empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = (int) $data['skartacni_lhuta'];
        if ( empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = null;
        if ( !empty($data['spousteci_udalost_id']) ) $data['spousteci_udalost_id'] = (int) $data['spousteci_udalost_id'];
        if ( !empty($data['stav']) ) $data['stav'] = (int) $data['stav'];
        if ( !empty($data['selected']) ) $data['selected'] = (int) $data['selected'];
        
        $part = explode(".",$data['nazev']);
        if ( count($part)>0 ) {
            foreach ($part as $pi=>$pn) {
                if ( is_numeric($pn) ) {
                    $part[$pi] = sprintf("%04d",intval($pn));
                } else {
                    $part[$pi] = $pn;
                }
            }
        }
        $data['sekvence_string'] = implode(".",$part);
        
        $spisznak_id = $this->vlozitH($data);
        return $spisznak_id;
    }

    public function upravit($data, $spisznak_id) {

        $data['date_modified'] = new DateTime();
        $data['user_modified'] = (int) Environment::getUser()->getIdentity()->id;

        if ( empty($data['spousteci_udalost_id']) ) $data['spousteci_udalost_id'] = 3;
        if ( !isset($data['parent_id']) ) $data['parent_id'] = null;
        if ( empty($data['parent_id']) ) $data['parent_id'] = null;
        if ( !isset($data['parent_id_old']) ) $data['parent_id_old'] = null;
        if ( empty($data['parent_id_old']) ) $data['parent_id_old'] = null;

        if ( !empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = (int) $data['skartacni_lhuta'];
        if ( empty($data['skartacni_lhuta']) ) $data['skartacni_lhuta'] = null;
        if ( !empty($data['spousteci_udalost_id']) ) $data['spousteci_udalost_id'] = (int) $data['spousteci_udalost_id'];
        if ( !empty($data['stav']) ) $data['stav'] = (int) $data['stav'];
        if ( !empty($data['selected']) ) $data['selected'] = (int) $data['selected'];

        $part = explode(".",$data['nazev']);
        if ( count($part)>0 ) {
            foreach ($part as $pi=>$pn) {
                if ( is_numeric($pn) ) {
                    $part[$pi] = sprintf("%04d",intval($pn));
                } else {
                    $part[$pi] = $pn;
                }
            }
        }
        $data['spisovy_znak_format'] = 1;
        $data['sekvence_string'] = implode(".",$part);

        $this->upravitH($data, $spisznak_id);
    }

    public function odstranit($spisznak_id, $odebrat_strom)
    {
        return $this->odstranitH($spisznak_id, $odebrat_strom);
    }

    /* Hodnoty parametru select:
       0 - vrat seznam pro pouziti v select boxu
       1 - vrat seznam pro pouziti v select boxu s polozkou "Zadna"
       3 - to same, ale pro select box pro vyhledavani
       8 - vrat objekt DibiRow spousteci udalosti (pouzito ve tride Dokument)
       10 - vrat nazev spousteci udalosti dane parametrem kod
    */
    public static function spousteci_udalost( $kod = null, $select = 0 ) {

        $result = DbCache::get('s3_Spousteci_udalost');
        
        if ($result === null) {           
            $prefix = Environment::getConfig('database')->prefix;
            $tb_spoudalost = $prefix .'spousteci_udalost';
            $result = dibi::query('SELECT * FROM %n', $tb_spoudalost, 'WHERE stav<>0')->fetchAssoc('id');
            
            DbCache::set('s3_Spousteci_udalost', $result);
        }

        if ( $select == 8 )
            return isset($result[$kod]) ? $result[ $kod ] : null;

        if ( $select == 10 )
            if ( $kod === null )
                return '';
            else
                return isset($result[$kod]) ? $result[ $kod ]->nazev : '';
            
        if ( $select == 1 ) {
            $tmp = array();
            // Viz Task #166
            // $tmp[''] = 'Žádná';
            foreach ($result as $dt) {
                $tmp[ $dt->id ] = String::truncate($dt->nazev,90);
            }
            return $tmp;
        } 
        if ( $select == 3 ) {
            $tmp = array();
            $tmp[''] = 'všechny spouštěcí události';
            foreach ($result as $dt) {
                $tmp[ $dt->nazev ] = String::truncate($dt->nazev,90);
            }
            return $tmp;
        }

        // Pri jakekoli jine hodnote parametru $select vrat prosty seznam vsech udalosti
        return $result;
    }

    public static function stav($stav = null) {

        $stav_array = array('1'=>'aktivní',
                            '0'=>'neaktivní'
                     );

        if ( is_null($stav) ) {
            return $stav_array;
        } else {
            return isset($stav_array[$stav])?$stav_array[$stav]:null;
        }


    }

}
