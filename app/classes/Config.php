<?php

namespace Spisovka;

interface IConfig
{

    /**
     * @return ArrayHash
     */
    function get();

    function save($data);
}

class Config implements IConfig
{

    protected $name;
    protected $data;

    public function __construct($name, $directory = null)
    {
        if (!in_array($name, array('epodatelna', 'klient', 'database')))
            throw new \InvalidArgumentException(__METHOD__ . "() - neplatný konfigurační soubor '$name'");

        if (!$directory)
            $directory = CLIENT_DIR . '/configs';
        $directory = rtrim($directory, '/\\');
        $ext = $name === 'database' ? 'neon' : 'ini';
        $loader = new \Nette\DI\Config\Loader();
        $array = $loader->load("$directory/$name.$ext");
        $this->name = $name;
        $this->data = ArrayHash::from($array);
    }

    /**
     * 
     * @return ArrayHash
     */
    public function get()
    {
        return $this->data;
    }

    public function save($data)
    {
        self::_saveCheckParameter($data);

        $loader = new \Nette\DI\Config\Loader();
        $loader->save($data, CLIENT_DIR . "/configs/{$this->name}.ini");
    }

    public static function _saveCheckParameter(&$data)
    {
        if (is_array($data))
            ;
        else if ($data instanceof ArrayHash) {
            $data = $data->toArray();
        } else
            throw new \InvalidArgumentException(__METHOD__ . '() - neplatný argument');
    }

}

/**
 *  Tato trida cte konfiguraci ze souboru epodatelna.ini.
 *  Pouzito pri upgradu ze spisovky < 3.5.0
 */
class ConfigEpodatelnaOld extends Config
{

    public function __construct()
    {
        parent::__construct('epodatelna');
    }

    public function save($data)
    {
        // nedelej nic, nemelo by se vubec volat
    }

}

/**
 * Cte konfiguraci z databaze
 */
class ConfigEpodatelna implements IConfig
{

    public function get()
    {
        $data = Settings::get('epodatelna', null);
        if (!$data)
            throw new \Exception(__METHOD__ . '() - v databázi chybí nastavení e-podatelny');

        return $this->upgrade(ArrayHash::from(unserialize($data)));
    }

    public function save($data)
    {
        Config::_saveCheckParameter($data);
        Settings::set('epodatelna', serialize($data));
    }

    /**
     * Aktualizuje strukturu nastavení podle změn v aplikaci.
     * @param ArrayHash $data
     * @return ArrayHash
     */
    protected function upgrade($data)
    {
        $changed = false;

        // jen jedna datova schranka, nebudeme s ni pracovat jako s polem        
        if (!isset($data->isds->ucet)) {
            $i = reset($data->isds);
            $data->isds = $i;
            $changed = true;
        }

        // dtto pro nastaveni odesilani
        if (count($data->odeslani) == 1) {
            $o = reset($data->odeslani);
            $data->odeslani = $o;
            $changed = true;
        }

        // nedulezite, nemusime hned ukladat
        unset($data->isds->stav);
        unset($data->isds->stav_hesla);

        // oprav boolean hodnoty z konfiguracniho souboru
        // kvuli bugu v parse_ini_file()
        $data->isds->aktivni = (bool) $data->isds->aktivni;

        foreach ($data->email as $e) {
            $e->aktivni = (bool) $e->aktivni;
            $e->only_signature = (bool) $e->only_signature;
            $e->qual_signature = (bool) $e->qual_signature;
        }

        // --------------------------------
        if (!isset($data->odeslani->podepisovat)) {
            $data->odeslani->podepisovat = $data->odeslani->typ_odeslani == 1;
            unset($data->odeslani->typ_odeslani);
            $changed = true;
        }
        if (isset($data->odeslani->aktivni)) {
            unset($data->odeslani->aktivni);
            unset($data->odeslani->cert_key);
            $changed = true;
        }
        if (!isset($data->odeslani->bcc)) {
            $data->odeslani->bcc = '';
            $changed = true;
        }
        
        if ($changed)
            $this->save($data);

        return $data;
    }

}

class ConfigClient extends Config
{

    public function __construct()
    {
        parent::__construct('klient');

        if (!in_array($this->data->cislo_jednaci->typ_evidence, ['priorace', 'sberny_arch']))
            throw new \Exception(__METHOD__ . '() - chybné nastavení typu evidence');

        // Tato nastavení mohou v klient.ini chybět z důvodu upgrade ze starých verzí
        if (empty($this->data->cislo_jednaci->typ_deniku))
            $this->data->cislo_jednaci->typ_deniku = 'urad';
        
        if (empty($this->data->cislo_jednaci->oddelovac))
            $this->data->cislo_jednaci->oddelovac = '/';
        
        if (empty($this->data->nastaveni->pocet_polozek))
            $this->data->nastaveni->pocet_polozek = 20;
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
