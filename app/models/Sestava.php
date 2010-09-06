<?php

class Sestava extends BaseModel
{

    protected $name = 'sestava';
    protected $primary = 'id';
    
    
    public function getInfo($sestava_id)
    {

        $result = $this->fetchRow(array('id=%i',$sestava_id));
        $row = $result->fetch();
        return ($row) ? $row : NULL;

    }

    public function seznam($args = null,$select = 0)
    {

        $sql = array();

        if ( isset($args['where']) ) {
            $sql['where'] = array($args['where']);
        }

        if ( isset($args['order']) ) {
            $sql['order'] = $args['order'];
        } else {
            $sql['order'] = array('typ'=>'DESC','nazev');
        }


        $select = $this->fetchAllComplet($sql);
        return $select;

    }

    public function vytvorit($data) {

        return $this->insert($data);

    }

    public function upravit($data,$sestava_id) {

        //$transaction = (! dibi::inTransaction());
        //if ($transaction)
        //dibi::begin();

        $ret = $this->update($data,array('id=%i',$sestava_id));

        //if ($transaction)
        //dibi::commit();

        return $ret;

    }

    public static function stav($stav = null) {

        $stav_array = array('1'=>'aktnivní',
                            '0'=>'neaktivní'
                     );

        if ( is_null($stav) ) {
            return $stav_array;
        } else {
            return array_key_exists($stav, $stav_array)?$stav_array[$stav]:null;
        }


    }

}
