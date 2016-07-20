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
}
