<?php

/**
 * Spouštěcí událost
 */
class StartEvent extends DBEntity
{

    const TBL_NAME = 'spousteci_udalost';
    const MANUAL = 1;
    const AUTOMATIC = 2;

    public function isAutomatic()
    {
        return $this->stav == self::AUTOMATIC;
    }

    /**
     * Vrátí výchozí spouštěcí událost
     * @return self|null
     */
    public static function getDefault()
    {
        $id = dibi::query("SELECT [id] FROM %n WHERE [nazev] LIKE '%po uzavření dokumentu%'",
                        self::TBL_NAME)->fetchSingle();
        return $id ? new static($id) : null;
    }

}
