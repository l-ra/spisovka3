<?php

namespace Spisovka;

use Nette;

class Authenticator_LDAP extends Authenticator_Basic
{

    protected $ldap;

    public function __construct(Nette\Http\Request $request, Spisovka_LDAP $ldap)
    {
        parent::__construct($request);
        $this->ldap = $ldap;
    }

    public function supportsRemoteAuth()
    {
        return true;
    }

    protected function verifyRemotePassword($credentials)
    {
        return $this->ldap->verify_user($credentials[self::USERNAME],
                        $credentials[self::PASSWORD]);
    }

}
