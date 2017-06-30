<?php

namespace Spisovka;

class Settings
{

    const TABLE_NAME = 'settings';

    protected static $instance = null;

    protected static function _getInstance()
    {
        if (self::$instance === null)
            self::$instance = new self;
        return self::$instance;
    }

    public static function get($key, $default = null)
    {
        $i = self::_getInstance();
        return $i->_get($key, $default);
    }

    public static function set($key, $value)
    {
        $i = self::_getInstance();
        $i->_set($key, $value);
    }

    public static function remove($key)
    {
        $i = self::_getInstance();
        $i->_set($key, null);
    }

    public static function getAll()
    {
        $i = self::_getInstance();
        return $i->_getAll();
    }

    public static function reload()
    {
        $i = self::_getInstance();
        $i->_load();
    }
    
    // ------------------------------------------------------------

    protected $settings = array();

    protected function __construct()
    {
        $this->_load();
    }
    
    protected function _load()
    {
        $this->settings = [];
        $result = dibi::query('SELECT * FROM %n', ':PREFIX:' . self::TABLE_NAME);
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

    protected function _get($key, $default = null)
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    protected function _getAll()
    {
        return $this->settings;
    }

    protected function _set($key, $value)
    {
        if (!is_scalar($value))
            throw new \Exception(__METHOD__ . '() - $value has to be a scalar');

        if ($value === false)
            $db_value = 'false';
        else if ($value === true)
            $db_value = 'true';
        else
            $db_value = $value;

        if ($value === null) {
            unset($this->settings[$key]);
            dibi::query('DELETE FROM %n', ':PREFIX:' . self::TABLE_NAME, 'WHERE [name] = %s',
                    $key);
        } else if (isset($this->settings[$key])) {
            // Je-li promenna jiz nastavena na stejnou hodnotu, pak nic neprovadej
            if ($this->settings[$key] != $value) {
                $this->settings[$key] = $value;
                dibi::query('UPDATE %n', ':PREFIX:' . self::TABLE_NAME, 'SET [value] = %s',
                        $db_value, 'WHERE [name] = %s', $key);
            }
        } else {
            $this->settings[$key] = $value;
            try {
                dibi::query('INSERT INTO %n', ':PREFIX:' . self::TABLE_NAME,
                        '([name], [value]) VALUES (%s, %s)', $key, $db_value);
            } catch (Exception $e) {
                /**
                 * Task #787 - ošetři race condition
                 */
                if ($e->getCode() != 1062 /* ER_DUP_ENTRY */)
                    throw $e;
                dibi::query('UPDATE %n', ':PREFIX:' . self::TABLE_NAME, 'SET [value] = %s',
                        $db_value, 'WHERE [name] = %s', $key);
            }
        }
    }

}
