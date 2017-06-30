<?php

namespace Spisovka;

/**
 * Description of UserAccount
 *
 * @author Pavel Laštovička
 */
class UserAccount extends CachedDBEntity
{

    const TBL_NAME = 'user';
    const ROLE_TABLE = 'acl_role';
    const USER2ROLE_TABLE = 'user_to_role';

    public function __construct($param)
    {
        if ($param instanceof \Nette\Security\User)
            $param = $param->getId();

        parent::__construct($param);
    }

    public static function create(array $data)
    {
        $data['date_created'] = new \DateTime();
        $data['active'] = 1;
        if (!empty($data['password']))
            $data['password'] = self::computePasswordHash($data['username'], $data['password']);

        return parent::create($data);
    }

    public static function computePasswordHash($username, $password)
    {
        return sha1($username . $password);
    }

    public function changePassword($password)
    {
        // zabran, aby uzivatel mohl u dema menit heslo k urcitym uctum
        if (Demo::isDemo() && !Demo::canChangePassword($this))
            return false;

        if (empty($password))
            return false;

        $this->last_modified = new \DateTime();
        $this->password = self::computePasswordHash($this->username, $password);
        $this->save();

        return true;
    }

    public function getPerson()
    {
        return new Person($this->osoba_id);
    }

    /**
     * 
     * @return DibiRow[] | null
     */
    public function getRoles()
    {
        $rows = dibi::fetchAll('SELECT r.*
                                 FROM [:PREFIX:' . self::USER2ROLE_TABLE . '] ur
                                 LEFT JOIN [:PREFIX:' . self::ROLE_TABLE . '] r ON (r.id = ur.role_id)
                                 WHERE ur.user_id = %i', $this->id);

        return $rows ? $rows : NULL;
    }

    /**
     * @return OrgUnit|null
     */
    public function getOrgUnit()
    {
        $ou_id = $this->orgjednotka_id;
        return $ou_id !== null ? new OrgUnit($ou_id) : null;
    }

    /**
     * Trvale deaktivuje účet. V aplikaci přestane být vidět.
     */
    public function deactivate()
    {
        if ($this->id == self::getUser()->id) {
            throw new \Exception('Nemůžete smazat účet, pod kterým jste přihlášen!');
        }

        $this->active = 0;
        $this->username = $this->username . "_" . time();
        $this->save();
    }

    /**
     * @param string $username
     * @return null|self
     */
    public static function getByName($username)
    {
        $id = dibi::query("SELECT [id] FROM %n WHERE [username] = %s", self::TBL_NAME,
                        $username)->fetchSingle();

        return $id === false ? null : self::fromId($id);
    }

    /**
     * @return array
     */
    public static function getAllUserNames()
    {
        return dibi::query('SELECT [username] FROM %n', self::TBL_NAME)->fetchPairs();
    }

}
