<?php

class Authenticator_LDAP extends Authenticator_Basic
{

    protected $ldap;

    public function __construct(Spisovka_LDAP $ldap)
    {
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
