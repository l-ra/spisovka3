<?php

/**
 * Detekce služby mojespisovka.cz
 *
 * @author Pavel Laštovička
 */
class Hosting
{

    public static function detect()
    {
        static $result = null;

        if ($result === null)
            $result = gethostname() == 'bluepoint.vshosting.cz';

        return $result;
    }

}
