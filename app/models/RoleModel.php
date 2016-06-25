<?php

class RoleModel extends TreeModel
{

    protected $name = 'acl_role';
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

}
