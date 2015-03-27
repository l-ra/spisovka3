<?php

namespace Spisovka;

class Config {
    
    protected $name;
    protected $data;
    
    public function __construct($name) {
        
        if ($name != 'epodatelna' && $name != 'klient')
            throw new \InvalidArgumentException(__METHOD__ . "() - neplatný konfigurační soubor '$name'");
        
        $loader = new \Nette\DI\Config\Loader();
        $array = $loader->load(CLIENT_DIR . "/configs/$name.ini");
        $this->name = $name;
        $this->data = \Spisovka\ArrayHash::from($array);        
    }
    
    public function get() {
        return $this->data;
    }
    
    public function save($data) {
        
        if (is_array($data)) ;
        else if ($data instanceof \Spisovka\ArrayHash) {
            $data = $data->toArray();
        }
        else throw new InvalidArgumentException(__METHOD__ . '() - neplatný argument');
        
        $loader = new \Nette\DI\Config\Loader();
        $loader->save($data, CLIENT_DIR . "/configs/{$this->name}.ini");        
    }
}

class ConfigEpodatelna extends Config {
    
    public function __construct() {
        parent::__construct('epodatelna');
    }
}

class ConfigClient extends Config {
    
    public function __construct() {
        parent::__construct('klient');
    }
}