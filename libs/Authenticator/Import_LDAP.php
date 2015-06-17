<?php

class Import_LDAP implements IUserImport
{

    protected $ldap;

    public function __construct(Spisovka_LDAP $ldap)
    {
        $this->ldap = $ldap;
    }

    public function getRemoteUsers()
    {
        /* $a = array();
          $a[] = ['uid' => 'marek', 'jmeno' => 'Marek', 'prijmeni' => 'Janouch' ];
          $a[] = ['uid' => 'pnovotny', 'jmeno' => 'Petr', 'prijmeni' => 'Novotný',
          'email' => 'petr.novotny@test.cz'];
          $a[] = ['uid' => 'referent', 'jmeno' => 'Miroslav', 'prijmeni' => 'Neliba' ];

          return $a; */

        try {
            return $this->ldap->get_users();
        } catch (Exception $e) {
            if ($e->getCode() == 32) // no such object
                return array();

            throw $e;
        }
    }

}
