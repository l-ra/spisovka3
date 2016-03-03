<?php

/**
 * Description of UserAccount
 *
 * @author Pavel Laštovička
 */
class UserAccount extends CachedDBEntity
{

    const TBL_NAME = 'user';

    public function __construct($param)
    {
        if ($param instanceof \Nette\Security\User)
            $param = $param->getId();
        
        parent::__construct($param);
    }
    
    public function changePassword($password)
    {
        // zabran, aby uzivatel mohl u dema menit heslo k urcitym uctum
        if (Demo::isDemo() && !Demo::canChangePassword($this))
            return false;

        if (empty($password))
            return false;
        
        $this->last_modified = new DateTime();
        $this->password = sha1($this->username . $password);
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
                                 FROM [:PREFIX:' . BaseModel::USER2ROLE_TABLE . '] ur
                                 LEFT JOIN [:PREFIX:' . BaseModel::ROLE_TABLE . '] r ON (r.id = ur.role_id)
                                 WHERE ur.user_id = %i', $this->id);

        return $rows ? $rows : NULL;
    }
}
