<?php

/**
 * Description of Person
 *
 * @author Pavel Laštovička
 */
class Person extends CachedDBEntity
{

    const TBL_NAME = 'osoba';

    public function save()
    {
        $this->date_modified = new DateTime();
        $this->user_modified = $this->getUser()->id;
        parent::save();
    }

    public static function create(array $data)
    {
        $user_id = self::getUser()->id;
        if (!$user_id)
            $user_id = 1;   // pripad instalace aplikace

        $data['date_created'] = new DateTime();
        $data['user_created'] = $user_id;
        $data['date_modified'] = new DateTime();
        $data['user_modified'] = $user_id;
        $data['stav'] = 0;

        return parent::create($data);
    }

    public function displayName()
    {
        return Osoba::displayName($this);
    }

    /**
     * PHP magic :-)
     * @return string
     */
    public function __toString()
    {
        return $this->displayName();
    }

    /**
     * Vrati pole uzivatelskych uctu osoby.
     * @return UserAccount[]
     */
    public function getAccounts()
    {
        return UserAccount::getAll(['where' => "osoba_id = $this->id AND active = 1"]);
    }

    /**
     * Creates instance from account ID
     *
     * @param int $account_id
     * @return Person
     */
    public static function fromUserId($account_id)
    {
        $account = new UserAccount($account_id);
        return new static($account->osoba_id);
    }

}
