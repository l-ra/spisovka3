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
        $user = new UserModel();
        $log = new LogModel();
        $row = UserModel::getUser($username, true);

        // Overeni uzivatele
        if (!$row)
            throw new Nette\Security\AuthenticationException("Uživatel '$username' nenalezen.",
            self::IDENTITY_NOT_FOUND);

        if ($row->active == 0)
            throw new Nette\Security\AuthenticationException("Uživatel '$username' byl deaktivován.",
            self::NOT_APPROVED);

        // Sestaveni roli
        $identity_roles = array();
        if (!count($row->user_roles))
            throw new Nette\Security\AuthenticationException("Uživatel '$username' nemá přiřazenou žádnou roli. Není možné ho připustit k aplikaci. Kontaktujte svého správce.",
            self::NOT_APPROVED);

        foreach ($row->user_roles as $role) {
            $identity_roles[] = $role->code;
        }

        // Overeni hesla
        try {
            $success = $this->verifyPassword($row, $credentials);
        } catch (Exception $e) {
            throw new Nette\Security\AuthenticationException(
            "Při ověřování hesla došlo k problému: " . $e->getMessage(), self::FAILURE);
        }
        if (!$success) {
            $log->logAccess($row->id, 0);
            throw new Nette\Security\AuthenticationException("Neplatné heslo.",
            self::INVALID_CREDENTIAL);
        }

        $user->zalogovan($row->id);
        $log->logAccess($row->id, 1);

        // Odstraneni hesla v identite
        unset($row->password);
        $row->klient = KLIENT;

        return new Nette\Security\Identity($row->id, $identity_roles, $row);
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
