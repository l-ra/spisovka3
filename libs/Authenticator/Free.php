<?php

class Authenticator_Free extends Object implements IAuthenticator
{

    public function authenticate(array $credentials)
    {

        // input
        $username = "admin";
        $password = sha1("adminadmin");



        // search user
        $row = UserModel::getUser($username);

        // user validate
        if (!$row) {
            throw new AuthenticationException("Uživatel '$username' nenalezen.", self::IDENTITY_NOT_FOUND);
        }

        // password validate
        if ($row->password !== $password) {
            throw new AuthenticationException("Neplatné heslo.", self::INVALID_CREDENTIAL);
        }

        // unset password
        unset($row->password);

        // tady nacitam taky roli
        return new Identity($row->username, $row->role, $row);
    }
}
