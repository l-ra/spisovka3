<?php

class Settings
{
    const TABLE_NAME = 'settings';

    protected static $instance = null;

    protected static function _getInstance() {
    
        if (self::$instance === null)
            self::$instance = new self;
        return self::$instance;
    }

    public static function get($key, $default = null) {
    
        $i = self::_getInstance();
        return $i->_get($key, $default);
    }

    public static function set($key, $value) {
    
        $i = self::_getInstance();
        $i->_set($key, $value);
    }

    public static function remove($key) {
    
        $i = self::_getInstance();
        $i->_set($key, null);
    }
    
    // ------------------------------------------------------------

    protected $settings = array();
    protected $table_prefix;
   
    protected function __construct() {
    
        $this->table_prefix = BaseModel::getDbPrefix();
        
        $result = dibi::query('SELECT * FROM %n', $this->table_prefix . self::TABLE_NAME);
        if (count($result) > 0)
            foreach ($result as $row) {
                $value = $row->value;
                if (strcasecmp($value, 'false') == 0)
                    $value = false;
                else if (strcasecmp($value, 'true') == 0)
                    $value = true;
                $this->settings[$row->name] = $value;
            }
    }
        
    protected function _get($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    protected function _set($key, $value) {
    
        if ($value === false)
            $db_value = 'false';
        else if ($value === true)
            $db_value = 'true';
        else
            $db_value = $value;

        if ($value === null) {
            unset($this->settings[$key]);
            dibi::query('DELETE FROM %n', $this->table_prefix . self::TABLE_NAME, 'WHERE [name] = %s', $key);
        }
        else if (isset($this->settings[$key])) {
            // Je-li promenna jiz nastavena na stejnou hodnotu, pak nic neprovadej
            if ($this->settings[$key] != $value) {
                $this->settings[$key] = $value;
                dibi::query('UPDATE %n', $this->table_prefix . self::TABLE_NAME, 'SET [value] = %s', $db_value, 'WHERE [name] = %s', $key);
            }
        }
        else {
            $this->settings[$key] = $value;
            dibi::query('INSERT INTO %n', $this->table_prefix . self::TABLE_NAME, '([name], [value]) VALUES (%s, %s)', $key, $db_value);
        }
    }
    
}

?>