<?php

class RoleModel extends TreeModel
{

    protected $name = 'user_role';
    protected $nazev = 'name';
    protected $nazev_sekvence = 'code';
    protected $primary = 'id';

    protected $cache;

    public function  __construct() {

        parent::__construct();

        if ( defined('DB_CACHE') ) {
            $this->cache = DB_CACHE;
        } else {
            $this->cache = Environment::getConfig('database')->cache;
        }

    }

    /* simple = 1  je použito pro seznam rolí pro combo box formuláře nová role / upravit roli */

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
            //$tmp[0] = '(nedědí)';
            foreach ($rows as $r) {
                if (!empty($r->orgjednotka_id))
                    // P.L. Z automaticky vytvořených rolí organizačních jednotek nelze dědit
                    continue;
                $tmp[ $r->id ] = $r->name;
            }
            return $tmp;
        } else if ( $simple == 2 ) {
            return $query;
        } else {
            return $query->fetchAll();
        }
    }

    public function seznam_pro_zmenu_dedeni($id)
    {
        $query = dibi::query(
            'SELECT r.*,pr.name parent_name FROM %n r', $this->name,
            'LEFT JOIN '. $this->name .' pr ON pr.id=r.parent_id',
            '%ex', (isset($where) ? array('WHERE %and', $where) : NULL),
            'ORDER BY r.fixed DESC, r.order DESC, r.name'
        );

        $rows = $query->fetchAll();
        $tmp = array();
        foreach ($rows as $r) {
            if (!empty($r->orgjednotka_id))
                // P.L. Z automaticky vytvořených rolí organizačních jednotek nelze dědit
                continue;
            if (strstr($r->sekvence_string, '.' . $id . '#'))
                // nutno zabránit vytvoření cyklického grafu
                continue;

            $tmp[ $r->id ] = $r->name;
        }
        return $tmp;        
    }
    
    public function getInfo($role_id) {

        $res = dibi::query(
            'SELECT r.*,pr.name parent_name FROM %n r', $this->name,
            'LEFT JOIN '. $this->name .' pr ON pr.id=r.parent_id',
            'WHERE r.id=%i', $role_id
        );

        if (count($res) == 0)
            throw new InvalidArgumentException("Role id '$role_id' neexistuje.");

        return $res->fetch();
    }

    public function getRoleByOrg($orgjednotka_id, $role_id) {

        $query = dibi::query(
            'SELECT r.*,pr.name parent_name FROM %n r', $this->name,
            'LEFT JOIN '. $this->name .' pr ON pr.id=r.parent_id',
            'WHERE r.parent_id=%i', $role_id, ' AND r.orgjednotka_id=%i', $orgjednotka_id
        );

        return $query->fetch();

    }


    public function vlozit($data) {

        if ( $this->cache ) {
            $cache = Environment::getCache('db_cache');
            unset($cache['s3_Role'], $cache['s3_Permission']);
        }

        if ( empty($data['parent_id']) ) $data['parent_id'] = null;

        return $this->vlozitH($data);
    }
    public function upravit($data,$id) {

        if ( $this->cache ) {
            $cache = Environment::getCache('db_cache');
            unset($cache['s3_Role'], $cache['s3_Permission']);
        }

        if ( empty($data['parent_id']) || $data['parent_id'] == 0 ) $data['parent_id'] = null;

        return $this->upravitH($data, $id);
    }
    public function smazat($where) {
        if ( $this->cache ) {
            $cache = Environment::getCache('db_cache');
            unset($cache['s3_Role'], $cache['s3_Permission']);
        }
        return parent::delete($where);
    }


}
