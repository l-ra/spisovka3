<?php

namespace Spisovka;

/**
 * @author Pavel Laštovička
 */
final class GlobalVariables
{

    private static $vars = [];

    public static function get($name, $default = null)
    {
        if (isset(self::$vars[$name])) {
            return self::$vars[$name];
        } else
            return $default;
    }

    public static function set($name, $value)
    {
        self::$vars[$name] = $value;
    }

}
