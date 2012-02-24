<?php

class Authenticator_BasicS2 extends Control implements IAuthenticator
{

    protected $receivedSignal;
    protected $action;
    protected $wasRendered = FALSE;

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

        if ( $row->active == 0 ) {
            throw new AuthenticationException("Uživatel '$username' byl deaktivován.", self::NOT_APPROVED);
        }        
        
        // Overeni hesla
        if ($row->password !== $password) {

            // Test na puvodni heslo
            $passwordS2 = md5(md5($credentials[self::PASSWORD]));
            if ( $row->password != $passwordS2 ) {
                $log->logAccess($row->id, 0);
                throw new AuthenticationException("Neplatné heslo.", self::INVALID_CREDENTIAL);
            } else {

                // TODO provest prevod hesla na novy format

                $user->zalogovan($row->id);
                $log->logAccess($row->id, 1);
            }
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
