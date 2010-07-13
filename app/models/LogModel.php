<?php

class LogModel extends BaseModel {

    protected $name = 'log_system';
    protected $tb_logaccess = 'log_access';
    protected $tb_user = 'user';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;
        $this->name = $prefix . $this->name;
        $this->tb_logaccess = $prefix . $this->tb_logaccess;
        $this->tb_user = $prefix . $this->tb_user;
 
    }


    public function logAccess($user_id,$stav=1) {

        $row = array();
        $row['user_id'] = $user_id;
        $row['date'] = new DateTime();
        $row['ip'] = Environment::getHttpRequest()->getRemoteAddress();
        $row['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $row['stav'] = $stav;

        return dibi::insert($this->tb_logaccess, $row)
            ->execute($this->autoIncrement ? dibi::IDENTIFIER : NULL);

    }

    public function seznamPristupu($limit = 50, $offset = 0, $user_id = null) {

        $res = dibi::query(
            'SELECT * FROM %n la', $this->tb_logaccess,
            'LEFT JOIN %n',$this->tb_user,' u ON (u.user_id=la.user_id)',
            '%if', !is_null($user_id), 'WHERE %and', !is_null($user_id) ? array('la.user_id=%i',$user_id) : array(), '%end',
            'ORDER BY la.logaccess_id DESC'
        );
        return $res->fetchAll($offset, $limit);
        

    }


}