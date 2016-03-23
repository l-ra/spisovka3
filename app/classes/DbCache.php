<?php

class DbCache
{

    /**
     * @var Nette\Caching\Cache
     */
    protected static $cache = null;

    protected static function init()
    {
        static $initialized = false;
        if (!$initialized) {

            $initialized = true;
            $setting = GlobalVariables::get('database')->cache;
            $should_cache = $setting == 1;

            if ($should_cache) {
                $context = Nette\Environment::getContext();
                self::$cache = new Nette\Caching\Cache($context->getByType('Nette\Caching\IStorage'),
                        'db_cache');
            }
        }
    }

    public static function get($key)
    {
        self::init();
        return self::$cache !== null ? self::$cache->load($key) : null;
    }

    public static function set($key, $value)
    {
        self::init();
        if (self::$cache !== null)
            self::$cache->save($key, $value, []);
    }

    public static function delete($key)
    {
        self::init();
        if (self::$cache !== null)
            self::$cache->remove($key);
    }

    public static function clearCache()
    {
        self::init();
        // let Nette itself clear the cache
        if (self::$cache !== null)
            self::$cache->clean([Cache::ALL => true]);

        /*        $dir = self::getCacheDirectory();
          $ok = true;

          if ($handle = opendir($dir)) {
          while ($obj = readdir($handle)) {
          if ($obj != '.' && $obj != '..')
          if (!unlink("$dir/$obj"))
          $ok = false;
          }
          closedir($handle);
          }
          else
          $ok = false;

          return $ok;
         */
    }

}

?>