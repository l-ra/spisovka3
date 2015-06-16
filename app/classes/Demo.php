<?php

class Demo
{

    protected static $is_demo = null;

    public static function isDemo()
    {
        if (self::$is_demo === null) {

            self::$is_demo = (bool) Nette\Environment::getVariable('demo', false);
        }

        return self::$is_demo;
    }

    public static function canChangePassword($user)
    {
        return strstr($user->username, 'demo') === false;
    }

}

?>