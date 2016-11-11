<?php

namespace Spisovka;

/**
 * Wrapper pro dibi knihovnu
 *
 * @author Pavel Laštovička
 */
class dibi extends \dibi
{

    public static function connect($config = array(), $name = 0)
    {
        $config = self::fix_connection_params($config);
        return parent::connect($config, $name);
    }
    
    protected static function fix_connection_params($config)
    {
        if (empty($config['driver']) || $config['driver'] == 'mysql')
            $config['driver'] = 'mysqli';
        if (empty($config['sqlmode']))
            $config['sqlmode'] = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_IN_DATE,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
        
        return $config;
    }

}
