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

        // cache - neni treba furt tahat z DB, kdyz se to po vetsinu casu nemeni
        //$storage = new FileStorage('tmp');
        //$cache = new Cache($storage);

        if (isset($cache['s3_Role'])) {
            return $cache['s3_Role'];
        } else {

            $res = dibi::fetchAll('SELECT r1.code, r2.code as parent_code
                                   FROM ['. $this->tb_role . '] r1
                                   LEFT JOIN ['. $this->tb_role . '] r2 ON (r1.parent_id = r2.role_id)
                                   ORDER BY r1.parent_id ASC
                                  ');
            $cache['s3_Role'] = $res;
            return $res;


        }




    }

    public function getResources($all=0) {
        $cols = ($all==1)?'*':'code';
        
        // cache - neni treba furt tahat z DB, kdyz se to po vetsinu casu nemeni
        //$storage = new FileStorage('tmp');
        //$cache = new Cache($storage);

        if (isset($cache['s3_Resource_'.$cols])) {
            return $cache['s3_Resource_'.$cols];
        } else {        
            $res = dibi::fetchAll('SELECT '.$cols.' FROM ['. $this->tb_resource . '] ORDER BY code ASC');
            $cache['s3_Resource_'.$cols] = $res;
            return $res;
        }
    }

    public function getPermission() {

        // cache - neni treba furt tahat z DB, kdyz se to po vetsinu casu nemeni
        //$storage = new FileStorage('tmp');
        //$cache = new Cache($storage);

        if (isset($cache['s3_Permission'])) {
            return $cache['s3_Permission'];
        } else {
            $res = dibi::fetchAll('
                SELECT
                    a.allowed as allowed,
                    ro.code as role,
                    re.code as resource,
                    ru.privilege as privilege
                    FROM ['. $this->name . '] a
                    JOIN ['. $this->tb_role . '] ro ON (a.role_id = ro.role_id)
                    LEFT JOIN ['. $this->tb_rule . '] ru ON (a.rule_id = ru.rule_id)
                    LEFT JOIN ['. $this->tb_resource . '] re ON (ru.resource_id = re.resource_id)

                    ORDER BY ro.fixed DESC, a.allowed DESC, ro.code, ru.privilege
            ');
            $cache['s3_Permission'] = $res;
            return $res;
        }


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
                LEFT JOIN ['. $this->tb_resource . '] re ON (ru.resource_id = re.resource_id)
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
                LEFT JOIN ['. $this->tb_resource . '] re ON (ru.resource_id = re.resource_id)
                ORDER BY re.code ASC
        ');

        $tmp = array();

        foreach ($rows as $pravidlo) {

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
            $tmp[ $resource_id ]['pravidla'][ $pravidlo->rule_id ]['name'] = $pravidlo->name;
            $tmp[ $resource_id ]['pravidla'][ $pravidlo->rule_id ]['note'] = $pravidlo->note;
            $tmp[ $resource_id ]['pravidla'][ $pravidlo->rule_id ]['resource'] = $pravidlo->resource_code;
            $tmp[ $resource_id ]['pravidla'][ $pravidlo->rule_id ]['privilege'] = $pravidlo->privilege;

            if ( !is_null($role) ) {

                $Acl = Acl::getInstance();
                $opravneni = $Acl->allowedByRole($role,$pravidlo->resource_code,$pravidlo->privilege);
                if ( count($opravneni)>0 ) {
                    $povoleno = "ano";
                } else {
                    $povoleno = "ne";
                }

                $tmp[ $resource_id ]['pravidla'][ $pravidlo->rule_id ]['opravneni'] = $povoleno;
                $tmp[ $resource_id ]['pravidla'][ $pravidlo->rule_id ]['role_id'] = null;
            } else {
                $tmp[ $resource_id ]['pravidla'][ $pravidlo->rule_id ]['opravneni'] = "nejiste";
                $tmp[ $resource_id ]['pravidla'][ $pravidlo->rule_id ]['role_id'] = null;
            }



        }



        return $tmp;
    }


    public function seznamOpravneni($role = null) {
        
        $rows = dibi::query('
            SELECT
                a.allowed allowed,
                ro.code role,
                ro.role_id role_id,
                
                re.code resource,
                re.resource_id resource_id,

                ru.privilege privilege,
                ru.rule_id rule_id


                FROM ['. $this->name . '] a
                JOIN ['. $this->tb_role . '] ro ON (a.role_id = ro.role_id)
                LEFT JOIN ['. $this->tb_rule . '] ru ON (a.rule_id = ru.rule_id)
                LEFT JOIN ['. $this->tb_resource . '] re ON (ru.resource_id = re.resource_id)
                %if', !is_null($role), 'WHERE ro.code=%s', $role,'
                ORDER BY re.code ASC
        ');

        return $rows->fetchAssoc('rule_id');
    }

    public function insertAcl($data) {
        $storage = new FileStorage('tmp');
        $cache = new Cache($storage);
        unset($cache['s3_Permission']);
        return dibi::insert($this->name, $data)
            ->execute($this->autoIncrement ? dibi::IDENTIFIER : NULL);
    }

    public function insertResource($data) {
        $storage = new FileStorage('tmp');
        $cache = new Cache($storage);
        unset($cache['s3_Resource_*'],$cache['s3_Resource_code'],$cache['s3_Permission']);
        return dibi::insert($this->tb_resource, $data)
            ->execute($this->autoIncrement ? dibi::IDENTIFIER : NULL);
    }

    public function insertRule($data) {
        $storage = new FileStorage('tmp');
        $cache = new Cache($storage);
        unset($cache['s3_Rule'],$cache['s3_Permission']);
        return dibi::insert($this->tb_rule, $data)
            ->execute($this->autoIncrement ? dibi::IDENTIFIER : NULL);
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
        $storage = new FileStorage('tmp');
        $cache = new Cache($storage);
        unset($cache['s3_Permission']);
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
        $storage = new FileStorage('tmp');
        $cache = new Cache($storage);
        unset($cache['s3_Resource_*'],$cache['s3_Resource_code'],$cache['s3_Permission']);
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
        $storage = new FileStorage('tmp');
        $cache = new Cache($storage);
        unset($cache['s3_Rule'],$cache['s3_Permission']);
        return dibi::delete($this->tb_rule)->where($where)->execute();
    }
    

}
