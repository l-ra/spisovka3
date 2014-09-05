<?php

/**
 * @author Pavel Lastovicka
 * @version 1.0
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

	public function canBeDeleted()
	{
        $allowed = true; // Environment::getUser()->isAllowed('Sestava', 'smazat');
        return $this->isDeletable() && $allowed;
	}

	public function isModifiable()
	{
        return $this->typ != 2;
	}

	public function delete()
	{
        if (!$this->canBeDeleted())
            return false;
            
        parent::delete();
        return true;
	}

}
?>