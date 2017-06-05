<?php

class Authorizator extends Nette\Security\Permission
{

    public function __construct()
    {
        // Při instalaci aplikace ještě data v databázi neexistují,
        // musíme vrátit výchozí (prázdný) authorizator
        if (defined('APPLICATION_INSTALL'))
            return;

        // roles
        $this->addAllRoles(Role::getAll());

        // resources
        $model = new AclModel();
        foreach ($model->getResources() as $resource)
            $this->addResource($resource->code);

        // permissions
        // Je potřeba toto oprávnění ve výchozím stavu povolit. Uživatel stále bude mít možnost oprávnění explicitně odepřít
        $this->allow(Nette\Security\Permission::ALL, 'Spisovka_ZpravyPresenter');

        foreach ($model->getPermission() as $perm) {
            if (!empty($perm->role) && !empty($perm->resource) && !empty($perm->privilege)) {
                // role + resource + privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}($perm->role, $perm->resource,
                        $perm->privilege);
            } else if (!empty($perm->role) && !empty($perm->resource) && empty($perm->privilege)) {
                // role + resource
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}($perm->role, $perm->resource);
            } else if (!empty($perm->role) && empty($perm->resource) && empty($perm->privilege)) {
                // role
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}($perm->role);
            } else if (empty($perm->role) && !empty($perm->resource) && empty($perm->privilege)) {
                // resource
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}(Nette\Security\Permission::ALL,
                        $perm->resource);
            } else if (empty($perm->role) && empty($perm->resource) && !empty($perm->privilege)) {
                // privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}(Nette\Security\Permission::ALL,
                        Nette\Security\Permission::ALL, $perm->privilege);
            } else if (!empty($perm->role) && empty($perm->resource) && !empty($perm->privilege)) {
                // role + privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}($perm->role,
                        Nette\Security\Permission::ALL, $perm->privilege);
            } else if (empty($perm->role) && !empty($perm->resource) && !empty($perm->privilege)) {
                // resource + privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}(Nette\Security\Permission::ALL,
                        $perm->resource, $perm->privilege);
            }
        }
    }

    // Prochazi seznam vsech roli a vklada je ve spravnem poradi
    protected function addAllRoles($roles)
    {
        $added = [];
        // Nejdrive pridej koreny stromu
        foreach ($roles as $role) {
            if ($role->parent_id === null) {
                $added[] = $role->id;
                $this->addRole($role->code);
            }
        }

        do {
            $continue = false;
            foreach ($roles as $role) {
                // echo $role->id . "   ";
                if (in_array($role->id, $added))
                    continue;
                $continue = true;
                $parent_code = $roles[$role->parent_id]->code;
                if ($this->hasRole($parent_code)) {
                    $this->addRole($role->code, $parent_code);
                    $added[] = $role->id;
                }
            }
        } while ($continue);
    }

}
