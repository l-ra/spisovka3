<?php

class Authenticator_Basic extends Object implements IAuthenticator
{
    public function authenticate(array $credentials)
    {

        // vstupy
        $username = $credentials[self::USERNAME];
        $password = sha1( $credentials[self::USERNAME] . $credentials[self::PASSWORD] );

        // Vyhledani uzivatele
        $user = new UserModel();
        $log = new LogModel();
        $row = $user->getUser($username,true);

        //Debug::dump($row); //exit;

        // Overeni uzivatele
        if (!$row) {
            throw new AuthenticationException("Uživatel '$username' nenalezen.", self::IDENTITY_NOT_FOUND);
        }

        // Overeni hesla
        if ($row->password !== $password) {
            $log->logAccess($row->id, 0);
            throw new AuthenticationException("Neplatné heslo.", self::INVALID_CREDENTIAL);
        } else {
            $user->zalogovan($row->id);
            $log->logAccess($row->id, 1);
        }

        // Odstraneni hesla ve vypisu
        unset($row->password);

        // Sestaveni roli
        $identity_role = array();
        foreach ($row->user_roles as $role) {
            $identity_role[] = $role->code;
        }

        $row->klient = KLIENT;

        // tady nacitam taky roli
        return new Identity($row->display_name, $identity_role, $row);
    }
}
