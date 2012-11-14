<?php

class Acl extends Permission {

    private static $instance = false;

    public function __construct() {

        static $pass = 0;
        
        if (++$pass > 1)
            throw new LogicException('Acl::__construct() - objekt už byl vytvořen');
            
        $model = new AclModel();

        // roles
        $this->addAllRoles($model->getRoles());

        // resources
        foreach($model->getResources() as $resource)
            $this->addResource($resource->code);
        $this->addResource('Default');

        // permission
        $this->allow(Permission::ALL, 'Default');
        $this->allow(Permission::ALL, 'ErrorPresenter');
        $this->allow(Permission::ALL, 'Spisovka_ErrorPresenter');
        
        // Je potřeba toto oprávnění ve výchozím stavu povolit. Uživatel stále bude mít možnost oprávnění explicitně odepřít
        $this->allow(Permission::ALL, 'Spisovka_ZpravyPresenter');

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
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}(Permission::ALL, $perm->resource);
            } else if ( empty($perm->role) && empty($perm->resource) && !empty($perm->privilege) ) {
                // privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}(Permission::ALL, Permission::ALL, $perm->privilege);
            } else if ( !empty($perm->role) && empty($perm->resource) && !empty($perm->privilege) ) {
                // role + privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}($perm->role, Permission::ALL, $perm->privilege);
            } else if ( empty($perm->role) && !empty($perm->resource) && !empty($perm->privilege) ) {
                // resource + privilege
                $this->{$perm->allowed == 'Y' ? 'allow' : 'deny'}(Permission::ALL, $perm->resource, $perm->privilege);
            }
        }
        
        // hack, ale lepe to kvuli navrhu Nette asi udelat nejde
        // Po prvnim vytvoreni objektu zrus registraci factory a zaregistruj singleton
        $locator = Environment::getServiceLocator();
        $locator->removeService('Nette\Security\IAuthenticator');
        $locator->addService('Nette\Security\IAuthenticator', $this);
    }

    public static function getInstance() {

        return Environment::getService('Nette\Security\IAuthenticator');
        
    }

    // Prochazi seznam vsech roli a vklada je ve spravnem poradi
    protected function addAllRoles($roles)
    {
        // Nejdrive pridej koreny stromu
        foreach($roles as &$role) {
            $role->added = false;
            if (empty($role->parent_code)) {
                $role->added = true;
                $this->addRole($role->code, $role->parent_code);
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

    public function allowed($resource = self::ALL, $privilege = self::ALL) {

        $user_role = Environment::getUser()->getRoles();
        $allow = 0;

        //Debug::dump($user_role);

        foreach ($user_role as $role) {
            echo $role ." - ". $resource ." - ". $privilege;
            $opravneni = $this->allowedByRole($role, $resource, $privilege);
            //Debug::dump($opravneni);
            if ( count($opravneni)>0 ) {
                if ( $allow == 0 ) $allow = 1;
            }
        }

        return ($allow==1);

    }


    /**
    * Returns the "oldest" ancestor(s) in @role's genealogy that has/have the permission for @resource and @privilege
    * @uses Let the parameter @oldest set to zero!
    *
    * @param string|array $role
    * @param string|array $resource
    * @param mixed $privilege
    * @param int $oldes
    *
    * @return array
    */
    public function allowedByRole($role = self::ALL, $resource = self::ALL, $privilege = self::ALL, $oldest = 0) {

       # Assume that @role doesn't have the permission for @resource and @privilege
       $result = array(
         "oldest" => $oldest,
         "role" => array()
       );

       if ($role != self::ALL) {
         if ($this->isAllowed($role, $resource, $privilege)) {
           # Set @role as result and improve gradually
           $result = array(
             "oldest" => $oldest,
             "role" => array($role)
           );
           $parents = $this->getRoleParents($role);

           if (count($parents)) {
             foreach ($parents as $parent) {
               $value = $this->allowedByRole($parent, $resource, $privilege, $oldest + 1);

               if ($value['oldest'] > $oldest && count($value['role'])) {
                 $result = $value;
               } elseif ($value['oldest'] == $oldest && count($value['role'])) {
                 $result['role'] += $value['role'];
               }
             }
           }
         }
       }

       if ($oldest == 0) {
         return $result['role']; # final result
       } else {
         return $result; # result during recursion
       }
    }

    public static function isInRole($roles)
    {
        $Acl = Acl::getInstance();
        
        $user_roles = array();
        $roles_a = array();
        
        $user_roles = Environment::getUser()->roles;
        foreach ( $user_roles as $user_role ) {
            $user_roles = array_merge($user_roles, $Acl->getRoleParents($user_role));
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
