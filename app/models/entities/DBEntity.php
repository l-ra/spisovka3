<?php

/**
 * @author Pavel Lastovicka
 * @created 02-IX-2014 13:06:54
 */
abstract class DBEntity implements IteratorAggregate
{

    const TBL_NAME = 'dbentity';

    /**
     * integer primary key
     */
    protected $id;
    protected $_data = null;
    protected $_data_changed = false;
    private $_columns_changed = [];

    /**
     * @param id    int
     */
    public function __construct($id)
    {
        if (!is_integer($id))
            if (!is_string($id) || !ctype_digit($id) || $id == 0)
                throw new InvalidArgumentException(__METHOD__ . "() - neplatný parametr");

        $this->id = (int) $id;
    }

    protected function _load()
    {
        $id = $this->id;
        $result = dibi::query("SELECT * FROM %n WHERE id = $id", ':PREFIX:' . $this::TBL_NAME);
        if (!count($result))
            throw new Exception(__METHOD__ . "() - entita " . get_class($this) . " ID $id neexistuje");

        $this->_data = $result->fetch();
    }

    /**
     * Force reload of the entity from database.
     */
    protected function _invalidate()
    {
        $this->_data = null;
        $this->_columns_changed = [];
        $this->_data_changed = false;
    }
    
    /**
     * Transaction support
     */
    protected function _rollback()            
    {
        dibi::rollback();
        $this->_invalidate();
    }
    
    protected function _setData(DibiRow $data)
    {
        $this->_data = $data;
    }

    /**
     * @param name    string
     */
    public function __get($name)
    {
        // Nenahrávej data, když potřebujeme pouze ID
        if ($name == 'id')
            return $this->id;

        $data = $this->getData();

        if (array_key_exists($name, $data))
            return $data[$name];

        $method_name = "get" . ucfirst($name);
        if (method_exists($this, $method_name)) {
            $reflection = new ReflectionMethod($this, $method_name);
            if ($reflection->isPublic())
                return $this->$method_name();
        }

        throw new InvalidArgumentException(__METHOD__ . "() - atribut '$name' nenalezen");
    }

    public function __isset($name)
    {
        $data = $this->getData();

        if (array_key_exists($name, $data))
            return isset($data[$name]);

        throw new InvalidArgumentException(__METHOD__ . "() - atribut '$name' nenalezen");
    }

    protected function _attributeExists($name)
    {        
        return array_key_exists($name, $this->getData());
    }
    
    /**
     * 
     * @param name    string
     * @param value   mixed
     */
    public function __set($name, $value)
    {
        if (!$this->_data)
            $this->_load();

        if (!array_key_exists($name, $this->_data))
            throw new InvalidArgumentException(__METHOD__ . "() - atribut '$name' nenalezen");

        if (strcasecmp($name, 'id') == 0)
        // simply ignore setting id column by mistake
            return;

        if ($this->_data[$name] !== $value) {
            $this->_data[$name] = $value;
            $this->_data_changed = true;
            $this->_columns_changed[] = $name;
        }
    }

    /**
     * Returns entity data as array. For reading only!
     * @return DibiRow
     */
    public function getData()
    {
        if (!$this->_data)
            $this->_load();

        return $this->_data;
    }

    /**
     * @param array $data
     */
    public function modify($data)
    {
        if (!is_array($data) && !($data instanceof ArrayAccess))
            throw new InvalidArgumentException(__METHOD__ . "() - invalid argument");
        
        foreach ($data as $key => $value)
            $this->__set($key, $value);
    }

    public function save()
    {
        if (!$this->canUserModify())
            throw new Exception("Uložení entity " . get_class($this) . " ID $this->id bylo zamítnuto.");

        $this->_saveInternal();
    }

    /**
     * Bohužel ve vyjímečných případech je nutné entitu změnit, přestože normálně
     * ji uživatel právo měnit nemá.
     */
    protected function _saveInternal()
    {
        if ($this->_data_changed) {
            if ($this->_attributeExists('date_modified'))
                $this->date_modified = new DateTime();
            if ($this->_attributeExists('user_modified'))
                $this->user_modified = self::getUser()->id;
            
            $update_data = [];
            foreach ($this->_columns_changed as $col)
                $update_data[$col] = $this->_data[$col];

            dibi::update(':PREFIX:' . $this::TBL_NAME, $update_data)->where("id = {$this->id}")->execute();
            $this->_data_changed = false;
            $this->_columns_changed = [];
        }        
    }

    /**
     * deletes the entity from a database
     */
    public function delete()
    {
        if (!$this->canUserDelete())
            throw new Exception("Smazání entity " . get_class($this) . " ID $this->id bylo zamítnuto.");

        dibi::query("DELETE FROM %n WHERE id = {$this->id}", ':PREFIX:' . $this::TBL_NAME);
        
        // further attempts to access the entity will result in exception
        $this->_invalidate();
    }

    public function canUserModify()
    {
        return true;
    }

    public function canUserDelete()
    {
        return true;
    }

    /**
     * @param params array
     */
    public static function getAll(array $params = array())
    {
        $query = array('SELECT * FROM %n', ':PREFIX:' . static::TBL_NAME);

        if (isset($params['where']))
            array_push($query, is_array($params['where']) ? 'WHERE %and' : 'WHERE',
                    $params['where']);

        if (isset($params['order']))
            array_push($query, 'ORDER BY %by', $params['order']);

        if (isset($params['limit']))
            array_push($query, 'LIMIT %i', $params['limit']);

        if (isset($params['offset']))
            array_push($query, 'OFFSET %i', $params['offset']);

        $resultSet = dibi::query($query);

        $a = array();

        foreach ($resultSet as $row) {
            $o = new static((int) $row->id);
            $o->_setData($row);
            $a[$o->id] = $o;
        }

        return $a;
    }

    public static function getCount(array $where = array())
    {
        $query = array('SELECT COUNT(*) FROM %n', ':PREFIX:' . static::TBL_NAME);

        if (!empty($where))
            array_push($query, 'WHERE %and', $where);

        $result = dibi::query($query);
        return $result->fetchSingle();
    }

    /**
     * creates an instance and returns it
     * 
     * @param   data
     * @returns object
     */
    public static function create(array $data)
    {
        $id = dibi::insert(':PREFIX:' . static::TBL_NAME, $data)->execute(dibi::IDENTIFIER);

        return new static($id);
    }

    /**
     * Utility function. So getting user is at one place.
     * @return Spisovka\User
     */
    public static function getUser()
    {
        return Nette\Environment::getUser();
    }

    /**
     * Object factory.
     * @param int $id
     * @return \static
     */
    public static function fromId($id)
    {
        return new static($id);
    }

    /**
     * IteratorAggregate interface
     * @return \ArrayIterator
     */
    public function getIterator()
    {        
        return new ArrayIterator($this->getData());
    }
}
