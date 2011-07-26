<?php

class SpisovyZnak extends TreeModel
{

    protected $name = 'spisovy_znak';
    protected $primary = 'id';
    protected $tb_spoudalost = 'spousteci_udalost';

    protected static $spousteci_udalost;


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
        }
        if ( $select == 5 ) {
            $params['paginator'] = 1;
        }

        $params['order'] = array('tb.nazev');
        return $this->nacti($spisznak_parent, true, true, $params);

        //$result = $this->nacti($spisznak_parent, true, true, $args );
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
        return ($row) ? $row : NULL;

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
        if ( !empty($data['spousteci_udalost_id']) ) $data['spousteci_udalost_id'] = (int) $data['spousteci_udalost_id'];
        if ( !empty($data['stav']) ) $data['stav'] = (int) $data['stav'];
        
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
        if ( !empty($data['spousteci_udalost_id']) ) $data['spousteci_udalost_id'] = (int) $data['spousteci_udalost_id'];
        if ( !empty($data['stav']) ) $data['stav'] = (int) $data['stav'];
        
        //Debug::dump($data); exit;
        
        $ret = $this->upravitH($data, $spisznak_id);

        return $ret;

    }

    public function odstranit($spisznak_id, $potomky = 2)
    {

        if ( empty($spisznak_id) ) return false;

        if ( $potomky == 1 ) {
            // odstranit i potomky
            return $this->odstranitH($spisznak_id, true);
        } else if ( $potomky == 2 ) {
            // potomky maji noveho rodice
            return $this->odstranitH($spisznak_id);
        }

    }

    public static function spousteci_udalost( $kod = null, $select = 0 ) {

        if ( empty( self::$spousteci_udalost ) ) {

            $cache = Environment::getCache('db_cache');
            if (isset($cache['s3_Spousteci_udalost'])) {
                $result = $cache['s3_Spousteci_udalost'];
            } else {
                $prefix = Environment::getConfig('database')->prefix;
                $tb_spoudalost = $prefix .'spousteci_udalost';
                $result = dibi::query('SELECT * FROM %n', $tb_spoudalost)->fetchAssoc('id');
                $cache['s3_Spousteci_udalost'] = $result;
            }

            self::$spousteci_udalost = $result;
        } else {
            $result = self::$spousteci_udalost;
        }

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
            } else if ( $select == 3 ) {
               $tmp = array();
                $tmp[''] = 'všechny spouštěcí události';
                foreach ($result as $dt) {
                    $tmp[ $dt->nazev ] = String::truncate($dt->nazev,90);
                }
                return $tmp;
            } else if ( $select == 10 ) {
                return '';
            } else {
                return $result;
            }
        } else {
            return ( isset($result[$kod]) )?$result[ $kod ]->nazev:'';
        }

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
