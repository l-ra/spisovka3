<?php


/**
 * @author Pavel Lastovicka
 * @created 02-IX-2014 13:06:54
 */
abstract class DBEntity
{

    const TBL_NAME = 'dbentity';
    /**
     * integer primary key
     */
    protected $id;
    protected $data = null;
    protected $data_changed = false;
    
    /**
     * @param id    int
     */
    public function __construct($param)
    {
        /* if (is_object($param) && is_a($param, 'DibiRow')) {
            $this->data = $param;
            $this->id = $param->id;
            return;
        } */
        
        if (!is_integer($param))
            if (!is_string($param) || !ctype_digit($param) || $param == 0)
                throw new InvalidArgumentException(__METHOD__ . "() - neplatný parametr");
            
        $this->id = (int)$param;
    }

    private function _errorStr($method, $message)
    {
        return "$method() - entita $message";
    }
    
    protected function _load()
    {
        $id = $this->id;
        $result = dibi::query("SELECT * FROM %n WHERE id = $id", ':PREFIX:' . $this::TBL_NAME);
        if (!count($result))
            throw new Exception(__METHOD__ . "() - entita " . get_class($this) . " ID $id neexistuje");
            
        $this->data = $result->fetch();
    }

    protected function _setData(DibiRow $data)
    {
        $this->data = $data;        
    }

    /**
     * @param name    string
     */
    public function __get($name)
    {
        if (!$this->data)
            $this->_load();
            
        if (key_exists($name, $this->data))
            return $this->data[$name];
            
        throw new InvalidArgumentException(__METHOD__ . "() - atribut '$name' nenalezen");
    }

    public function __isset($name)
    {
        if (!$this->data)
            $this->_load();
            
        if (key_exists($name, $this->data))
            return isset($this->data[$name]);
            
        throw new InvalidArgumentException(__METHOD__ . "() - atribut '$name' nenalezen");
    }
    
    /**
     * 
     * @param name    string
     * @param value   mixed
     */
    public function __set($name, $value)
    {
        if (!$this->data)
            $this->_load();
            
        if (!key_exists($name, $this->data))
            throw new InvalidArgumentException(__METHOD__ . "() - atribut '$name' nenalezen");
        
        if (strcasecmp($name, 'id') == 0)
            // simply ignore setting id column by mistake
            return;
            
        if ($this->data[$name] !== $value) {
            $this->data[$name] = $value;
            $this->data_changed = true;
        }
    }

    public function getData()
    {
        if (!$this->data)
            $this->_load();
            
        return $this->data;
    }

    public function modify(array $data)
    {           
        foreach($data as $key => $value)
            $this->__set($key, $value);
    }
    
    public function save()
    {
        if (!$this->canUserModify())
            throw new Exception("Uložení entity " . get_class($this) . " ID $this->id bylo zamítnuto.");
        if ($this->data_changed) {
            dibi::update(':PREFIX:' . $this::TBL_NAME, $this->data)->where("id = {$this->id}")->execute();
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
     * @param class   string    classname of instantiated elements
     * @param params
     */
    protected static function _getAll($class, array $params = array())
    {
        $query = array('SELECT * FROM %n', ':PREFIX:' . $class::TBL_NAME);
        
        if (isset($params['where']))
            array_push($query, 'WHERE %and', $params['where']);
            
        if (isset($params['order']))
            array_push($query, 'ORDER BY %by', $params['order']);
            
        if (isset($params['limit']))
            array_push($query, 'LIMIT %i', $params['limit']);
            
        if (isset($params['offset']))
            array_push($query, 'OFFSET %i', $params['offset']);
            
        $resultSet = dibi::query($query);
        
        $a = array();
        
        foreach ($resultSet as $row) {
            $o = new $class((int)$row->id);
            $o->_setData($row);
            $a[] = $o;
        }
        
        return $a;
    }
    
    /**
     * creates an instance and returns it
     * 
     * @param   data
     * @returns object
     */
    public static function create($class, array $data)
    {
        $id = dibi::insert(':PREFIX:' . $class::TBL_NAME, $data)->execute(dibi::IDENTIFIER);
        
        return new $class($id);
    }

}

?>