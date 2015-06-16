<?php

namespace Spisovka;

class Config
{

    protected $name;
    protected $data;

    public function __construct($name, $client_dir = null)
    {
        if (!in_array($name, array('epodatelna', 'klient', 'database')))
            throw new \InvalidArgumentException(__METHOD__ . "() - neplatný konfigurační soubor '$name'");

        if (!isset($client_dir))
            $client_dir = CLIENT_DIR;
        $ext = $name === 'database' ? 'neon' : 'ini';
        $loader = new \Nette\DI\Config\Loader();
        $array = $loader->load("$client_dir/configs/$name.$ext");
        $this->name = $name;
        $this->data = \Spisovka\ArrayHash::from($array);
    }

    public function get()
    {
        return $this->data;
    }

    public function save($data)
    {
        if (is_array($data))
            ;
        else if ($data instanceof \Spisovka\ArrayHash) {
            $data = $data->toArray();
        } else
            throw new InvalidArgumentException(__METHOD__ . '() - neplatný argument');

        $loader = new \Nette\DI\Config\Loader();
        $loader->save($data, CLIENT_DIR . "/configs/{$this->name}.ini");
    }

}

class ConfigEpodatelna extends Config
{

    public function __construct()
    {
        parent::__construct('epodatelna');
    }

}

class ConfigClient extends Config
{

    public function __construct()
    {
        parent::__construct('klient');
    }

}

class ConfigDatabase extends Config
{

    public function __construct($client_dir)
    {
        parent::__construct('database', $client_dir);
    }

    public function save($data)
    {
        throw new \LogicException(__METHOD__ . '() - konfigurace databáze je pouze pro čtení');
    }

}
