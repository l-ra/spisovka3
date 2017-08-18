<?php

namespace Spisovka;

use Nette;

class Authenticator_Basic extends Nette\Object implements Nette\Security\IAuthenticator
{

    /**
     * @var Nette\Http\Request 
     */
    protected $httpRequest;
    
    public function __construct(Nette\Http\Request $request)
    {
        $this->httpRequest = $request;
    }
    
    public function supportsRemoteAuth()
    {
        return false;
    }

    public function authenticate(array $credentials)
    {
        // Vyhledani uzivatele
        $username = $credentials[self::USERNAME];
        $account = UserAccount::getByName($username);

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
        } catch (\Exception $e) {
            throw new Nette\Security\AuthenticationException(
            "Při ověřování hesla došlo k problému: " . $e->getMessage(), self::FAILURE);
        }
        
        $log = new LogModel();
        $ip_address = $this->httpRequest->getRemoteAddress();
        if (!$success) {
            $log->logLogin($account->id, false, $ip_address);
            throw new Nette\Security\AuthenticationException("Neplatné heslo.",
            self::INVALID_CREDENTIAL);
        }

        $account->last_login = new \DateTime();
        $account->last_ip = 
        $account->save();
        
        $log->logLogin($account->id, true, $ip_address);

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
        $hash_S3 = UserAccount::computePasswordHash($user->username, $password);
        $hash_S2 = md5(md5($password));

        return $user->password === $hash_S3 || $user->password === $hash_S2;
    }

    // základní autentizace je pouze lokální
    protected function verifyRemotePassword($credentials)
    {
        return false;
    }

}
