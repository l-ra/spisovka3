<?php

class Acl extends Permission {

    private static $instance = false;

    public function __construct() {

        $model = new AclModel();

        // roles
        foreach($model->getRoles() as $role)
            $this->addRole($role->code, $role->parent_code);

        // resources
        foreach($model->getResources() as $resource)
            $this->addResource($resource->code);
        $this->addResource('Default');

        // permission
        $this->allow(Permission::ALL, 'Default');
        $this->allow(Permission::ALL, 'ErrorPresenter');
        $this->allow(Permission::ALL, 'Spisovka_ErrorPresenter');
        
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
        

    }

    public static function getInstance() {

        if(self::$instance === false){
            self::$instance = new Acl();
            return self::$instance;
        } else {
            return self::$instance;
        }

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
        if ( strpos($roles,",") !== false ) {
            $role_part = explode(",",$roles);
            if ( count($role_part)>0 ) {
                foreach ( $role_part as $role ) {
                    $is = Environment::getUser()->isInRole($role);
                    if ( $is ) return true;
                }
                return false;
            } else {
                return Environment::getUser()->isInRole($roles);
            }
        } else {
            return Environment::getUser()->isInRole($roles);
        }
    }
    
}
