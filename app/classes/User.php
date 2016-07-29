<?php

namespace Spisovka;

/**
 * Rozšíření třídy frameworku o nejpoužívanější funkce
 *
 * @author Pavel Laštovička
 * 
 * @property-read string $displayName
*/
class User extends \Nette\Security\User
{

    public function getDisplayName()
    {
        $person = \Person::fromUserId($this->getId());
        return \Osoba::displayName($person);
    }
    
    // Urcuje, zda uzivatel vystupuje pod uvedenou roli
    // Pri kontrole bere v uvahu primo nadrazene role tem, ktere ma uzivatel prirazen
    public function inheritsFromRole($roles)
    {
        $authz = $this->authorizator;
        $user_roles = array();
        $roles_a = array();

        $user_roles = $this->roles;
        foreach ($user_roles as $user_role) {
            $user_roles = array_merge($user_roles, $authz->getRoleParents($user_role));
        }
        $user_roles = array_flip($user_roles);

        if (strpos($roles, ",") !== false) {
            $roles_a = explode(",", $roles);
            if (count($roles_a) > 0) {
                foreach ($roles_a as $role) {
                    if (isset($user_roles[$role])) {
                        return true;
                    }
                }
            }
            return false;
        } else {
            return isset($user_roles[$roles]);
        }
    }
    
    /**
     * @return \OrgUnit|null
     */
    public function getOrgUnit()
    {
        $account = new \UserAccount($this->id);
        return $account->getOrgUnit();
    }
    
    public function isVedouci()
    {
        return $this->isAllowed(NULL, 'is_vedouci');
    }
}
