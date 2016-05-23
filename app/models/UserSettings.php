<?php

class UserSettings
{

    const TABLE_NAME = 'user_settings';

    private static $instance = null;

    private static function _getInstance()
    {
        if (self::$instance === null) {
            $user_id = Nette\Environment::getUser()->id;
            self::$instance = new self($user_id);
        }
        return self::$instance;
    }

    public static function getAll()
    {
        $i = self::_getInstance();
        return $i->_getAll();
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

    // ------------------------------------------------------------

    protected $user_id;
    protected $settings = array();

    protected function __construct($user_id)
    {
        $this->user_id = $user_id;

        $result = dibi::query('SELECT [settings] FROM %n',
                        ':PREFIX:' . self::TABLE_NAME, 'WHERE [id] = %i',
                        $this->user_id);
        if (count($result) > 0) {
            $value = unserialize($result->fetchSingle());
            if ($value !== false)
                $this->settings = $value;
        } else
            dibi::query('INSERT INTO %n', ':PREFIX:' . self::TABLE_NAME,
                    'VALUES (%i, %s)', $this->user_id, serialize(array()));
    }

    protected function _getAll()
    {
        return $this->settings;
    }

    protected function _get($key, $default = null)
    {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    protected function _set($key, $value)
    {
        if ($value === null)
            unset($this->settings[$key]);
        else
            $this->settings[$key] = $value;
        $this->_flush();
    }

    protected function _flush()
    {
        dibi::query('UPDATE %n', ':PREFIX:' . self::TABLE_NAME, 'SET [settings] = %s',
                serialize($this->settings), 'WHERE [id] = %i', $this->user_id);
    }

}

/**
 *  Zpristupnuje nastaveni jineho uzivatele, pouze pro cteni
 */
class OtherUserSettings extends UserSettings
{

    public function __construct($user_id)
    {
        parent::__construct($user_id);
    }
    
    public function _getAll()
    {
        return parent::_getAll();
    }

    public function _get($key, $default = null)
    {
        return parent::_get($key, $default);
    }

}
