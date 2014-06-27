<?php

class DBCache {

    protected static $cache = null;
    
    protected static function init() {
    
        static $initialized = false;
        if (!$initialized) {
        
            $initialized = true;
            $setting = Environment::getConfig('database')->cache;
            $should_cache = $setting == 1;

            if ($should_cache)
                self::$cache = Environment::getCache('db_cache');
        }
    }

    public static function get($key) {
    
        self::init();
        return self::$cache !== null ? self::$cache[$key] : null;
    }

    public static function set($key, $value) {
    
        self::init();
        if (self::$cache !== null)
            self::$cache[$key] = $value;
    }

    public static function delete($key) {
    
        self::init();
        if (self::$cache !== null)
            unset(self::$cache[$key]);
    }

}

?>