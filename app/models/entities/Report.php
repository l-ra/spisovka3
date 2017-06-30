<?php

namespace Spisovka;

/**
 * @author Pavel Lastovicka
 * @created 02-IX-2014 12:28:36
 */
class Report extends DBEntity
{

    const TBL_NAME = 'sestava';

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
        $allowed = self::getUser()->isAllowed('Sestava', 'mazat');
        return $this->isDeletable() && $allowed;
    }

    public function canUserModify()
    {
        $allowed = self::getUser()->isAllowed('Sestava', 'menit');
        return $this->isModifiable() && $allowed;
    }

    /**
     * check if user has read access to reports
     */
    public static function isUserAllowed()
    {
        return self::getUser()->isAllowed('Sestava', 'zobrazit');
    }

}
