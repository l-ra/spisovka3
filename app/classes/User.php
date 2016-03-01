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
        $person = \UserModel::getPerson($this->getId());
        return \Osoba::displayName($person);
    }
}
