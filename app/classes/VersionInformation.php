<?php

/**
 * Čtení informací o verzi aplikace.
 *
 * @author Pavel Laštovička
 */
class VersionInformation
{

    static private $initialized = false;
    static private $name;
    static private $version;

    public function __construct()
    {
        if (!self::$initialized) {
            self::init();
            self::$initialized = true;
        }
    }

    private static function init()
    {
        $data = parse_ini_file(APP_DIR . '/configs/version.ini');
        self::$name = $data['name'];
        self::$version = $data['version'];
    }

    public function __get($prop)
    {
        switch ($prop) {
            case 'version':
                return self::$version;
            case 'name':
                return self::$name;
            default:
                throw new Exception('Chyba v přístupu k verzi aplikace.');
        }        
    }
    
}
