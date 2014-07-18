<?php

class AclModel extends BaseModel {

    protected $name = 'user_acl';
    protected $tb_role = 'user_role';
    protected $tb_resource = 'user_resource';
    protected $tb_rule = 'user_rule';

    public function  __construct() {

        $prefix = Environment::getConfig('database')->prefix;

        $this->name = $prefix . $this->name;        
        $this->tb_role = $prefix . $this->tb_role;        
        $this->tb_resource = $prefix . $this->tb_resource;        
        $this->tb_rule = $prefix . $this->tb_rule;

    }

    public function getRoles() {

        $res = DbCache::get('s3_Role');
        if ($res !== null)
            return $res;

        $res = dibi::fetchAll('SELECT r1.code, r2.code as parent_code
                               FROM ['. $this->tb_role . '] r1
                               LEFT JOIN ['. $this->tb_role . '] r2 ON (r1.parent_id = r2.id)
                               ORDER BY r1.parent_id ASC
        ');

        DbCache::set('s3_Role', $res);
            
        return $res;

    }

    public function getResources($all=0) {

        $cols = ($all==1)?'*':'code';

        $res = DbCache::get('s3_Resource_' . $cols);
        if ($res !== null)
            return $res;
            
        $res = dibi::fetchAll('SELECT '.$cols.' FROM ['. $this->tb_resource . '] ORDER BY code ASC');

        DbCache::set('s3_Resource_' . $cols, $res);
        
        return $res;
    }

    public function getPermission() {

        $res = DbCache::get('s3_Permission');
        if ($res !== null)
            return $res;

        $res = dibi::fetchAll('
                SELECT
                    a.allowed as allowed,
                    ro.code as role,
                    re.code as resource,
                    ru.privilege as privilege
                    FROM ['. $this->name . '] a
                    JOIN ['. $this->tb_role . '] ro ON (a.role_id = ro.id)
                    JOIN ['. $this->tb_rule . '] ru ON (a.rule_id = ru.id)
                    LEFT JOIN ['. $this->tb_resource . '] re ON (ru.resource_id = re.id)

                    ORDER BY ro.fixed DESC, a.allowed DESC, ro.code, ru.privilege
        ');

        DbCache::set('s3_Permission', $res);
        
        return $res;
    }

    public function hledatPravidlo($data) {

        $where = array();
        if ( isset($data['privilege']) ) $where[] = array('ru.privilege=%s',$data['privilege']);

        $rows = dibi::fetchAll('
            SELECT
                ru.*,
                re.code resource_code,
                re.name resource_name,
                re.note resource_note
                FROM ['. $this->tb_rule . '] ru
                LEFT JOIN ['. $this->tb_resource . '] re ON (ru.resource_id = re.id)
                %ex', (!empty($where) ? array('WHERE %and', $where) : NULL),
                'ORDER BY re.code ASC'
           );

        return ($rows)?$rows:null;


    }

    public function seznamPravidel($role=null) {

        $rows = dibi::fetchAll('
            SELECT
                ru.*,
                re.code resource_code,
                re.name resource_name,
                re.note resource_note
                FROM ['. $this->tb_rule . '] ru
                LEFT JOIN ['. $this->tb_resource . '] re ON (ru.resource_id = re.id)
                ORDER BY re.code, ru.name
        ');

        $tmp = array();

        foreach ($rows as $pravidlo) {

            if ( strpos($pravidlo->privilege,'orgjednotka_') !== false ) {
                continue;
            }

            $resource_id = is_null($pravidlo->resource_id)?0:$pravidlo->resource_id;

            if ( is_null($pravidlo->resource_name) ) {
                $tmp[ $resource_id ]['name'] = "Základní pravidla";
                $tmp[ $resource_id ]['code'] = ":";
                $tmp[ $resource_id ]['note'] = "";
            } else {
                $tmp[ $resource_id ]['name'] = $pravidlo->resource_name;
                $tmp[ $resource_id ]['code'] = $pravidlo->resource_code;
                $tmp[ $resource_id ]['note'] = $pravidlo->resource_note;
            }

            //$tmp[ $resource_id ]['pravidla'][ $pravidlo->rule_id ] = $pravidlo;
            $tmp[ $resource_id ]['pravidla'][ $pravidlo->id ]['name'] = $pravidlo->name;
            $tmp[ $resource_id ]['pravidla'][ $pravidlo->id ]['note'] = $pravidlo->note;
            $tmp[ $resource_id ]['pravidla'][ $pravidlo->id ]['resource'] = $pravidlo->resource_code;
            $tmp[ $resource_id ]['pravidla'][ $pravidlo->id ]['privilege'] = $pravidlo->privilege;

            if ( !is_null($role) ) {

                $authz = Environment::getService('Nette\Security\IAuthorizator');
                $povoleno = $authz->isAllowed($role, $pravidlo->resource_code, $pravidlo->privilege);
                if ( $povoleno ) {
                    $povoleno = "ano";
                } else {
                    $povoleno = "ne";
                }

                $tmp[ $resource_id ]['pravidla'][ $pravidlo->id ]['opravneni'] = $povoleno;
                $tmp[ $resource_id ]['pravidla'][ $pravidlo->id ]['role_id'] = null;
            } else {
                $tmp[ $resource_id ]['pravidla'][ $pravidlo->id ]['opravneni'] = "nejiste";
                $tmp[ $resource_id ]['pravidla'][ $pravidlo->id ]['role_id'] = null;
            }



        }

        return $tmp;
    }


    public function seznamOpravneni($role = null) {
        
        $rows = dibi::query('
            SELECT
                a.allowed allowed,
                ro.code role,
                ro.id role_id,
                
                re.code resource,
                re.id resource_id,

                ru.privilege privilege,
                ru.id rule_id


                FROM ['. $this->name . '] a
                JOIN ['. $this->tb_role . '] ro ON (a.role_id = ro.id)
                LEFT JOIN ['. $this->tb_rule . '] ru ON (a.rule_id = ru.id)
                LEFT JOIN ['. $this->tb_resource . '] re ON (ru.resource_id = re.id)
                %if', !is_null($role), 'WHERE ro.code=%s', $role,'
                ORDER BY re.code ASC
        ');

        return $rows->fetchAssoc('rule_id');
    }

    public function insertAcl($data) {

        DbCache::delete('s3_Permission');

        $data['role_id'] = (int) $data['role_id'];
        $data['rule_id'] = (int) $data['rule_id'];

        return dibi::insert($this->name, $data)
            ->execute();
    }

    public function insertResource($data) {

        DbCache::delete('s3_Resource_*');
        DbCache::delete('s3_Resource_code');
        DbCache::delete('s3_Permission');

        return dibi::insert($this->tb_resource, $data)
            ->execute(dibi::IDENTIFIER);
    }

    public function insertRule($data) {
    
        DbCache::delete('s3_Permission');

        return dibi::insert($this->tb_rule, $data)
            ->execute(dibi::IDENTIFIER);
    }

    public function deleteAcl($where) {
        if ( is_null($where) ) {
            return null;
        } else if ( !is_array($where) ) {
            $where = array($where);
        } else {
            if ( !is_array(current($where)) ) {
                $where = array($where);
            }
        }

        DbCache::delete('s3_Permission');

        return dibi::delete($this->name)->where($where)->execute();
    }

    public function deleteResource($where) {
        if ( is_null($where) ) {
            return null;
        } else if ( !is_array($where) ) {
            $where = array($where);
        } else {
            if ( !is_array(current($where)) ) {
                $where = array($where);
            }
        }
        
        DbCache::delete('s3_Resource_*');
        DbCache::delete('s3_Resource_code');
        DbCache::delete('s3_Permission');
        
        return dibi::delete($this->tb_resource)->where($where)->execute();
    }

    public function deleteRule($where) {
        if ( is_null($where) ) {
            return null;
        } else if ( !is_array($where) ) {
            $where = array($where);
        } else {
            if ( !is_array(current($where)) ) {
                $where = array($where);
            }
        }
        
        DbCache::delete('s3_Rule');
        DbCache::delete('s3_Permission');
        
        return dibi::delete($this->tb_rule)->where($where)->execute();
    }    

}
