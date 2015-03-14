<?php

class Demo {

    protected static $is_demo = null;
    
    public static function isDemo() {
    
        if (self::$is_demo === null) {
        
            self::$is_demo = false;
            $config = Nette\Environment::getConfig('demo');
            if ($config !== null && $config->demo == 1) 
                self::$is_demo = true;
        }
        
        return self::$is_demo;
    }

    public static function canChangePassword($user) {
    
        return strstr($user->username, 'demo') === false;
    }
}


?>