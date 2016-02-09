<?php

class RoleModel extends TreeModel
{

    protected $name = 'user_role';
    protected $column_name = 'name';
    protected $column_ordering = 'code';

    public function getDefaultRole()
    {
        $role_id = dibi::query(
                        "SELECT id  FROM [{$this->name}] WHERE code = 'referent'"
                )->fetchSingle();
        if (!$role_id)
            throw new Exception('Role \'pracovník\' neexistuje!');
        return $role_id;
    }

    public function seznam()
    {
        $query = dibi::query(
                        "SELECT id, name FROM [{$this->name}] ORDER BY name"
        );

        return $query->fetchPairs('id', 'name');
    }

    public function seznamProDedeni($id = null)
    {
        // $id = null   - při vytváření nové role
        // $id not empty - při editaci již existující role
        // nutno zabránit vytvoření cyklického grafu
        $where2 = !empty($id) ? "AND sekvence_string NOT LIKE '%.$id#%'" : "";
        $query = dibi::query(
                        "SELECT id, name FROM [{$this->name}] WHERE orgjednotka_id IS NULL $where2 ORDER BY name"
        );

        return $query->fetchPairs('id', 'name');
    }

    public function getInfo($role_id)
    {

        $res = dibi::query(
                        'SELECT r.*,pr.name parent_name FROM %n r', $this->name,
                        'LEFT JOIN ' . $this->name . ' pr ON pr.id=r.parent_id',
                        'WHERE r.id=%i', $role_id
        );

        if (count($res) == 0)
            throw new InvalidArgumentException("Role id '$role_id' neexistuje.");

        return $res->fetch();
    }

    public function getRoleByOrg($orgjednotka_id, $role_id)
    {

        $query = dibi::query(
                        'SELECT r.*,pr.name parent_name FROM %n r', $this->name,
                        'LEFT JOIN ' . $this->name . ' pr ON pr.id=r.parent_id',
                        'WHERE r.parent_id=%i', $role_id, ' AND r.orgjednotka_id=%i',
                        $orgjednotka_id
        );

        return $query->fetch();
    }

    public function vlozit($data)
    {

        DbCache::delete('s3_Role');
        DbCache::delete('s3_Permission');

        if (empty($data['parent_id']))
            $data['parent_id'] = null;

        return $this->vlozitH($data);
    }

    public function upravit($data, $id)
    {

        DbCache::delete('s3_Role');
        DbCache::delete('s3_Permission');

        if (empty($data['parent_id']) || $data['parent_id'] == 0)
            $data['parent_id'] = null;

        $this->upravitH($data, $id);
    }

    public function smazat($where)
    {

        DbCache::delete('s3_Role');
        DbCache::delete('s3_Permission');
        return parent::delete($where);
    }

}
