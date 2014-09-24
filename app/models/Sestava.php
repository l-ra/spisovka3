<?php

/**
 * @author Pavel Lastovicka
 * @created 02-IX-2014 12:28:36
 */
class Sestava extends DBEntity
{

    const TBL_NAME = 'sestava';

    public static function getAll(array $params = array())
    {
        return parent::_getAll(__CLASS__, $params);
    }
    
    public function isDeletable()
    {
        return $this->id != 1;
    }

    public function isModifiable()
    {
        return $this->typ != 2;
    }

    public function canUserDelete()
    {
        $allowed = Environment::getUser()->isAllowed('Sestava', 'mazat');
        return $this->isDeletable() && $allowed;
    }

    public function canUserModify()
    {
        $allowed = Environment::getUser()->isAllowed('Sestava', 'menit');
        return $this->isModifiable() && $allowed;
    }
    
    
    /**
     * check if user has read access to reports
     */
    public static function isUserAllowed()
    {
        return Environment::getUser()->isAllowed('Sestava', 'zobrazit');
    }
}
?>