<?php

class Acl extends Nette\Security\Permission {

    private static $instance = false;

    public function __construct() {

        static $pass = 0;

        // Trida je singleton, ochrana proti vicenasobnemu vytvoreni
        if (++$pass > 1)
            throw new LogicException('Acl::__construct() - objekt už byl vytvořen');

        // Při instalaci aplikace ještě data v databázi neexistují,
        // musíme vrátit výchozí (prázdný) authorizator
        if (defined('APPLICATION_INSTALL'))
            return;
        
        $model = new AclModel();

        // roles
        $this->addAllRoles($model->getRoles());

        // resources
        foreach($model->getResources() as $resource)
            $this->addResource($resource->code);

        // permission
        // Je potřeba toto oprávnění ve výchozím stavu povolit. Uživatel stále bude mít možnost oprávnění explicitně odepřít
        $this->allow(Nette\Security\Permission::ALL, 'Spisovka_ZpravyPresenter');
        
        // úvodní obrazovka po přihlášení
        $this->allow(Nette\Security\Permission::ALL, 'Spisovka_DefaultPresenter');
        
        // přihlášení / odhlášení - neni potreba, tento presenter je vyjmut z kontroly pristupu. viz BasePresenter
        // $this->allow(Permission::ALL, 'Spisovka_UzivatelPresenter');

        // Resource, který má být vždy přístupný, nebudeme definovat v databázi, ale zde
        $this->addResource('Spisovka_SeznamzmenPresenter');
        $this->allow(Nette\Security\Permission::ALL, 'Spisovka_SeznamzmenPresenter');

        foreach($model->getPermission() as $perm) {
            if ( !empty($perm->role) && !empty($perm->resource) && !empty($perm->privilege) ) {
                // role + resource + privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}($perm->role, $perm->resource, $perm->privilege);
            } else if ( !empty($perm->role) && !empty($perm->resource) && empty($perm->privilege) ) {
                // role + resource
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}($perm->role, $perm->resource);
            } else if ( !empty($perm->role) && empty($perm->resource) && empty($perm->privilege) ) {
                // role
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}($perm->role);
            } else if ( empty($perm->role) && !empty($perm->resource) && empty($perm->privilege) ) {
                // resource
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}(Nette\Security\Permission::ALL, $perm->resource);
            } else if ( empty($perm->role) && empty($perm->resource) && !empty($perm->privilege) ) {
                // privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}(Nette\Security\Permission::ALL, Nette\Security\Permission::ALL, $perm->privilege);
            } else if ( !empty($perm->role) && empty($perm->resource) && !empty($perm->privilege) ) {
                // role + privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}($perm->role, Nette\Security\Permission::ALL, $perm->privilege);
            } else if ( empty($perm->role) && !empty($perm->resource) && !empty($perm->privilege) ) {
                // resource + privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}(Nette\Security\Permission::ALL, $perm->resource, $perm->privilege);
            }
        }       
    }

    
    // Prochazi seznam vsech roli a vklada je ve spravnem poradi
    protected function addAllRoles($roles)
    {
        // Nejdrive pridej koreny stromu
        foreach($roles as &$role) {
            $role->added = false;
            if (empty($role->parent_code)) {
                $role->added = true;
                $this->addRole($role->code);
            }
        }
        
        do {
            $continue = false;
            foreach ($roles as &$role) {
                if ($role->added === true)
                    continue;
                $continue = true;
                if ($this->hasRole($role->parent_code)) {
                    $role->added = true;
                    $this->addRole($role->code, $role->parent_code);
                }
            }
        } while ($continue);
    }


    // Utility funkce, ktera muze byt v kterekoli tride
    // Urcuje, zda prihlaseny uzivatel vystupuje pod uvedenou roli
    // Pri kontrole bere v uvahu primo nadrazene role tem, ktere ma uzivatel prirazen
    public static function isInRole($roles)
    {
        $authz = Nette\Environment::getService('authorizator');
        $user_roles = array();
        $roles_a = array();
        
        $user_roles = Nette\Environment::getUser()->roles;
        foreach ( $user_roles as $user_role ) {
            $user_roles = array_merge($user_roles, $authz->getRoleParents($user_role));
        }
        $user_roles = array_flip($user_roles);

        if ( strpos($roles,",") !== false ) {
            $roles_a = explode(",",$roles);
            if ( count($roles_a)>0 ) {
                foreach( $roles_a as $role ) {
                    if ( isset($user_roles[$role]) ) {
                        return true;
                    }
                }
            }
            return false;
        } else {
            return isset($user_roles[ $roles ]);
        }
        
    }    
}
