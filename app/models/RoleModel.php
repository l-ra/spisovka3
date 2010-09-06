<?php

class RoleModel extends BaseModel
{

    protected $name = 'user_role';
    protected $primary = 'id';


    public function seznam($simple=0,$sql=null) {

        if ( !is_null($sql) ) {
            // specificke pozadavky na SQL
            if ( isset($sql['where']) ) {
                $where = $sql['where'];
                if ( !is_array($where) ) {
                    $where = array($where);
                } else {
                    if ( !is_array(current($where)) ) {
                        $where = array($where);
                    }
                }
            }



        }


        $query = dibi::query(
            'SELECT r.*,pr.name parent_name FROM %n r', $this->name,
            'LEFT JOIN '. $this->name .' pr ON pr.id=r.parent_id',
            '%ex', (isset($where) ? array('WHERE %and', $where) : NULL),
            'ORDER BY r.fixed DESC, r.order DESC, r.name'
        );

        if ( $simple == 1 ) {
            $rows = $query->fetchAll();
            $tmp = array();
            $tmp[0] = '(nedědí)';
            foreach ($rows as $r) {
                $tmp[ $r->id ] = $r->name;
            }
            return $tmp;
        } else {
            return $query->fetchAll();
        }
    }

    public function getInfo($role_id) {

        $query = dibi::query(
            'SELECT r.*,pr.name parent_name FROM %n r', $this->name,
            'LEFT JOIN '. $this->name .' pr ON pr.id=r.parent_id',
            'WHERE r.id=%i', $role_id
        );

        return $query->fetch();

    }

    public function vlozit($data) {
        $storage = new FileStorage('tmp');
        $cache = new Cache($storage);
        unset($cache['s3_Role'],$cache['s3_Permission']);
        return parent::insert($data);
    }
    public function upravit($data,$where) {
        $storage = new FileStorage('tmp');
        $cache = new Cache($storage);
        unset($cache['s3_Role'],$cache['s3_Permission']);
        return parent::update($data, $where);
    }
    public function smazat($where) {
        $storage = new FileStorage('tmp');
        $cache = new Cache($storage);
        unset($cache['s3_Role'],$cache['s3_Permission']);
        return parent::delete($where);
    }


}
