<?php

class Authenticator_Basic extends Nette\Object implements Nette\Security\IAuthenticator
{

    public function supportsRemoteAuth()
    {
        return false;
    }

    public function authenticate(array $credentials)
    {
        // vstupy
        $username = $credentials[self::USERNAME];

        // Vyhledani uzivatele
        $log = new LogModel();
        $account = UserModel::searchUser($username);

        // Overeni uzivatele
        if (!$account)
            throw new Nette\Security\AuthenticationException("Uživatel '$username' nenalezen.",
            self::IDENTITY_NOT_FOUND);

        if ($account->active == 0)
            throw new Nette\Security\AuthenticationException("Uživatel '$username' byl deaktivován.",
            self::NOT_APPROVED);

        // Role
        $roles = $account->getRoles();
        if (!count($roles))
            throw new Nette\Security\AuthenticationException("Uživatel '$username' nemá přiřazenou žádnou roli. Není možné ho připustit k aplikaci. Kontaktujte svého správce.",
            self::NOT_APPROVED);

        $role_codes = array();
        foreach ($roles as $role) {
            $role_codes[] = $role->code;
        }

        // Overeni hesla
        try {
            $success = $this->verifyPassword($account, $credentials);
        } catch (Exception $e) {
            throw new Nette\Security\AuthenticationException(
            "Při ověřování hesla došlo k problému: " . $e->getMessage(), self::FAILURE);
        }
        if (!$success) {
            $log->logAccess($account->id, 0);
            throw new Nette\Security\AuthenticationException("Neplatné heslo.",
            self::INVALID_CREDENTIAL);
        }

        $account->last_login = new DateTime();
        $account->last_ip = Nette\Environment::getHttpRequest()->getRemoteAddress();
        $account->save();
        
        $log->logAccess($account->id, 1);

        // Odstraneni hesla v identite
        unset($account->password);

        return new Nette\Security\Identity($account->id, $role_codes);
    }

    protected function verifyPassword($user, $credentials)
    {
        if (!$user->external_auth)
            return $this->verifyLocalPassword($user, $credentials);
        else
            return $this->verifyRemotePassword($credentials);
    }

    protected function verifyLocalPassword($user, $credentials)
    {
        $password = $credentials[self::PASSWORD];
        $hash_S3 = sha1($user->username . $password);
        $hash_S2 = md5(md5($password));

        return $user->password === $hash_S3 || $user->password === $hash_S2;
    }

    // základní autentizace je pouze lokální
    protected function verifyRemotePassword($credentials)
    {
        return false;
    }

}
