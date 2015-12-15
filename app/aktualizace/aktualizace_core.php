<?php

class Updates
{

    public static $update_dir;
    public static $alter_scripts = array();
    public static $revisions = array();
    public static $descriptions = array();
    public static $clients = array();

    public static function init()
    {
        self::$update_dir = APP_DIR . '/aktualizace/';
    }

    /**
     *  $contents bude obsahovat obsah souboru v pripade, ze je soubor v ZIP archivu
     */
    protected static function _process_file($filename, $contents)
    {
        if ($filename == 'information.txt') {
            $info = file(self::$update_dir . $filename);
            if ($info)
                self::$descriptions = self::_parse_info_file($info);
            return;
        }

        $parts = explode("_", $filename);
        if (is_numeric($parts[0])) {

            $revision = $parts[0];
            self::$revisions[$revision] = $revision;

            if (strpos($filename, "_alter.sql") !== false) {
                $sql_source = $contents ? : file_get_contents(self::$update_dir . $filename);
                self::$alter_scripts[$revision] = self::_parse_sql($sql_source);
            }
        }
    }

    /**
     * 
     * @param string $data
     * @return array SQL commands, including comments
     */
    protected static function _parse_sql($data)
    {
        $queries = [];
        $query = '';
        $lines = explode("\n", $data);
        foreach ($lines as $line) {
            $line = rtrim($line);
            $query .= $line . "\n";
            if (substr($line, -1) == ';') {
                $queries[] = trim($query);
                $query = '';
            }
        }

        return $queries;
    }
    
    public static function find_updates()
    {
        self::$alter_scripts = array();
        self::$revisions = array();

        $dir_handle = opendir(self::$update_dir);
        if ($dir_handle === FALSE)
            throw new Exception(__METHOD__ . "() - nemohu otevřít adresář " . self::$update_dir);

        $zip = new ZipArchive;
        $filename = self::$update_dir . 'db_scripts.zip';
        if ($zip->open($filename) !== TRUE)
            throw new Exception(__METHOD__ . "() - nemohu otevřít soubor $filename.");

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = $stat['name'];
            self::_process_file($filename, $zip->getFromName($filename));
        }

        while (($filename = readdir($dir_handle)) !== false) {
            self::_process_file($filename, null);
        }

        closedir($dir_handle);

        ksort(self::$revisions, SORT_NUMERIC); //setridit pole, aby se alter skripty spoustely ve spravnem poradi

        return array('revisions' => self::$revisions, 'alter_scripts' => self::$alter_scripts,
            'descriptions' => self::$descriptions);
    }

    protected static function _parse_info_file($info)
    {
        $rev = 0;
        $a = array();
        $matches = [];
        
        foreach ($info as $line) {
            if ($line{0} == '[')
                if (preg_match('/^\[(\d+)\]/', $line, $matches) == 1) {
                    $rev = $matches[1];
                    continue;
                }

            // ignoruj prazdny radek
            /* if (trim($line) === '')
              continue; */

            if (!isset($a[$rev]))
                $a[$rev] = '';
            $a[$rev] .= $line;
        }

        return $a;
    }

    public static function find_clients()
    {
        $clients = array();
        // detekuj hosting mojespisovka.cz
        $hosting = file_exists(dirname(APP_DIR) . "/clients/.htaccess");
        if ($hosting) {
            $clients_dir = dirname(APP_DIR) . "/clients";
            $dh = opendir($clients_dir);
            if ($dh !== false)
                while (($filename = readdir($dh)) !== false) {
                    if ($filename == "." || $filename == ".." || $filename[0] == '@')
                    // Adresáře začínající na @ jsou speciální adresáře, není tam instalace klienta
                        continue;

                    if (is_dir("$clients_dir/$filename"))
                        $clients["$clients_dir/$filename"] = "$filename ($clients_dir/$filename)";
                }
        } else {
            $client_dir = dirname(APP_DIR) . "/client";
            $clients[$client_dir] = "STANDALONE ($client_dir)";
        }

        asort($clients);     // Setrid klienty podle abeceny  
        self::$clients = $clients;
        return $clients;
    }

}

class Client_To_Update
{

    private $db_config;
    private $path;   // cesta k adresari klienta v souborovem systemu
    private $revision_filename;

    public function __construct($path_to_client)
    {
        $this->path = $path_to_client;
        $this->revision_filename = "{$this->path}/configs/_aktualizace";
    }

    public function get_db_config()
    {
        if (!$this->db_config)
        // neprovadej autoload tridy, abychom poznali, jestli jsme volani ze spisovky
        // nebo z aktualizacniho skriptu
            if (class_exists('\Nette\Environment', false)) {
                $config = \Nette\Environment::getConfig('database');
                $this->db_config = $config;
            } else if (is_file("{$this->path}/configs/database.neon")) {
                $data = (new Spisovka\ConfigDatabase($this->path))->get();
                $this->db_config = $data->parameters->database;
                $this->db_config->profiler = false;
            } else {
                $ini = parse_ini_file("{$this->path}/configs/system.ini", true);
                if ($ini !== FALSE)
                    $this->db_config = array(
                        "driver" => $ini['common']['database.driver'],
                        "host" => $ini['common']['database.host'],
                        "username" => $ini['common']['database.username'],
                        "password" => $ini['common']['database.password'],
                        "database" => $ini['common']['database.database'],
                        "charset" => $ini['common']['database.charset'],
                        "prefix" => $ini['common']['database.prefix'],
                        "profiler" => false
                    );
                else
                    throw new Exception("Nemohu přečíst konfigurační soubor system.ini");
            }

        return $this->db_config;
    }

    public function get_path()
    {
        return $this->path;
    }

    public function connect_to_db()
    {
        $db_config = $this->get_db_config();
        try {
            dibi::connect($db_config);
            dibi::getSubstitutes()->{'PREFIX'} = $db_config['prefix'];
        } catch (DibiException $e) {
            throw new Exception("Nepodařilo se připojit k databázi. Klienta nelze aktualizovat.");
        }
    }

    function get_revision_number()
    {
        $revision = 0;

        try {
            $result = dibi::query("SELECT [value] FROM %n WHERE [name] = 'db_revision'",
                            $this->db_config['prefix'] . 'settings');
            if (count($result) > 0) {
                $revision = $result->fetchSingle();
                return $revision;
            }
        } catch (Exception $e) {
            // V databazi pravdepodobne neexistuje zminena tabulka
        }

        if (file_exists($this->revision_filename)) {
            $revision = trim(file_get_contents($this->revision_filename));
            if (empty($revision))
                $revision = 0;
        }
        return $revision;
    }

    function update_revision_number($revision)
    {
        try {
            dibi::query('UPDATE %n', $this->db_config['prefix'] . 'settings',
                    'SET [value] = %i', $revision, "WHERE [name] = 'db_revision'");

            // pokud je cislo revize v databazi, je soubor nadbytecny
            // ingoruj, pokud soubor neexistuje
            @unlink($this->revision_filename);

            return true;
        } catch (Exception $e) {
            // V databazi pravdepodobne neexistuje zminena tabulka
            // fall through
        }

        return file_put_contents($this->revision_filename, $revision);
    }

}
