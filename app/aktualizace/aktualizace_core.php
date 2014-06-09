<?php


class Updates {

    public static $update_dir;
    public static $alter_scripts = array();
    public static $revisions = array();
    public static $clients = array();
    
    public static function init() {
        self::$update_dir = WWW_DIR . '/app/aktualizace/';
    }
    
    public static function find_updates() {

        $alter_scripts = array();
        $revisions = array();
        
        $dir_handle = opendir(self::$update_dir);
        if ($dir_handle !== FALSE) {
            while (($filename = readdir($dir_handle)) !== false) {
                
                $parts = explode("_", $filename);
                if ( is_numeric($parts[0]) ) {
                
                    $revision = $parts[0];
                    $revisions[ $revision ] = $revision;
                    
                    if ( strpos($filename,"_alter.sql") !== false ) {
                        $sql_source = file_get_contents(self::$update_dir . $filename);
                        $sql_queries = explode(";", $sql_source);
                        // odstran komentar na zacatku souboru, ktery je oddelen strednikem
                        unset($sql_queries[0]);
                        $alter_scripts[ $revision ] = $sql_queries;
                    }                
                    
                }
            }
            closedir($dir_handle);
        }
        
        ksort( $revisions, SORT_NUMERIC ); //setridit pole, aby se alter skripty spoustely ve spravnem poradi
        
        self::$alter_scripts = $alter_scripts;
        self::$revisions = $revisions;
        return array('revisions' => $revisions, 'alter_scripts' => $alter_scripts);
    }

    public static function find_clients() {
    
        $clients = array();
        if ( defined('MULTISITE') && MULTISITE == 1 ) {
            $dh = opendir(WWW_DIR . "/clients");
            if ($dh !== false)
                while (($filename = readdir($dh)) !== false) {
                    if ( $filename == "." || $filename == ".." || $filename[0] == '@')
                        // Adresáře začínající na @ jsou speciální adresáře, není tam instalace klienta
                        continue;
                        
                    if ( is_dir(WWW_DIR . "/clients/$filename"))
                        $clients[ WWW_DIR . "/clients/$filename" ] = "$filename (" . WWW_DIR . "/clients/$filename)";
                }
        } else
            $clients[ WWW_DIR . "/client" ] = "STANDALONE (" . WWW_DIR . "/client)";

        asort($clients);     // Setrid klienty podle abeceny  
        self::$clients = $clients;
        return $clients;
    }

}


class Client_To_Update {

    private $db_config;
    private $path;   // cesta k adresari klienta v souborovem systemu
    private $revision_filename;
    
    public function __construct($path_to_client)
    {   
        $this->path = $path_to_client;
        $this->revision_filename = "{$this->path}/configs/_aktualizace";
        
        $ini = parse_ini_file("{$this->path}/configs/system.ini", true);
        if ($ini === FALSE)
            throw new Exception("Nemohu přečíst konfigurační soubor system.ini");
            
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
    }

    public function get_db_config()
    {
        return $this->db_config;
    }

    public function get_path()
    {
        return $this->path;
    }

    public function connect_to_db()
    {
        try {
            dibi::connect($this->db_config);
        }
        catch(DibiException $e) {
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
        }
        catch (Exception $e) {
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
            dibi::query('UPDATE %n', $this->db_config['prefix'] . 'settings', 'SET [value] = %i',
                $revision, "WHERE [name] = 'db_revision'");
            return true;
        }
        catch (Exception $e) {
            // V databazi pravdepodobne neexistuje zminena tabulka
            // fall through
        }
        
        return file_put_contents($this->revision_filename, $revision);
    }
    
    
}


?>