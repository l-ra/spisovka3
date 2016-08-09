<?php

class Install_DefaultPresenter extends BasePresenter
{

    public function startup()
    {
        if (!defined('APPLICATION_INSTALL') && $this->action != "kontrola")
            $this->setView('instalovano');

        $session = $this->getSession('s3_install');

        parent::startup();

        $this->template->step = $session->step;
    }

    public function renderDefault()
    {
        $session = $this->getSession('s3_install');
        unset($session->step);

        //$this->redirect('uvod');
    }

    public function renderUvod()
    {
        $session = $this->getSession('s3_install');
        if (!isset($session->step)) {
            $session->step = array();
        }
    }

    public function renderKontrola()
    {
        $installed = !defined('APPLICATION_INSTALL');
        $this->template->installed = $installed;
        if (!$installed) {
            $session = $this->getSession('s3_install');
            if (!isset($session->step)) {
                $session->step = array();
            }
            @$session->step['uvod'] = 1;
        }

        $this->template->errors = FALSE;
        $this->template->warnings = FALSE;

        foreach (array('function_exists', 'version_compare', 'extension_loaded', 'ini_get') as $function) {
            if (!function_exists($function)) {
                $this->template->errors = "Error: function '$function' is required by Nette Framework and this Requirements Checker.";
            }
        }

        $phpinfo = $this->phpinfo_array(1);

        // cURL supprot
        $curl_support = 0;
        $curl_version = "";
        if (function_exists('curl_version')) {
            $curl_support = 1;
            $curli = curl_version();
            if (isset($curli['version']))
                $curl_version .= " libcurl " . $curli['version'] . "";
            if (isset($curli['host']))
                $curl_version .= " (" . $curli['host'] . ")";
            if (isset($curli['ssl_version']))
                $curl_version .= "\nSSL implementace: " . $curli['ssl_version'];
        }

        $soap_support = class_exists('SoapClient');
        $mail_function = function_exists('mail');
        $openssl_support = function_exists('openssl_pkcs7_verify');
        
        // IMAP support
        $imap_support = 0;
        $imap_version = "";
        if (function_exists('imap_open')) {
            $imap_support = 1;

            if (isset($phpinfo['imap']['IMAP c-Client Version']))
                $imap_version = "IMAP " . $phpinfo['imap']['IMAP c-Client Version'];

            if (isset($phpinfo['imap']['SSL Support']))
                $imap_version .= ", SSL " . $phpinfo['imap']['SSL Support'];
            if (isset($phpinfo['imap']['Kerberos Support']))
                $imap_version .= ", Kerberos " . $phpinfo['imap']['Kerberos Support'];
        }


        // DB test
        try {
            $db_info = GlobalVariables::get('database');
            dibi::connect($db_info);
            $database_support = 1;
            $database_info = $db_info['driver'] . '://' . $db_info['username'] . '@' . $db_info['host'] . '/' . $db_info['database'];
        } catch (DibiDriverException $e) {
            $database_support = 0;
            $database_info = $e->getMessage();
        }

        // Appliaction info
        $app_info = new VersionInformation();

        define('CHECKER_VERSION', '1.4');

        $requirements_ess = $this->paint(array(
            array(
                'title' => 'Aplikace',
                'message' => ( $app_info->name )
            ),
            array(
                'title' => 'Web server',
                'message' => $_SERVER['SERVER_SOFTWARE'],
            ),
            // imap_open vyzaduje 5.3.2, problem s MS Exchange
            array(
                'title' => 'PHP verze',
                'required' => TRUE,
                'passed' => version_compare(PHP_VERSION, '5.5.0', '>='),
                'message' => PHP_VERSION,
                'description' => 'Používáte starou verzi PHP. Aplikace pro správný chod vyžaduje PHP verzi 5.5 nebo 5.6.',
            ),
            array(
                'title' => 'Databáze',
                'required' => TRUE,
                'passed' => $database_support,
                'message' => $database_info,
                'errorMessage' => 'Nelze se připojit k databázi.',
                'description' => 'Databáze je nutná pro běh aplikace. Zkontrolujte správnost nastavení nebo dostupnost databázového serveru.<br />SQL chyba: ' . $database_info,
            ),
            array(
                'title' => 'Funkce mail()',
                'required' => FALSE,
                'passed' => $mail_function,
                'message' => 'Ano',
                'errorMessage' => 'Funkce mail() je zakázána.',
                'description' => 'Je potřeba pro odesílání e-mailových zpráv.',
            ),
            array(
                'title' => 'Nastavení allow_url_fopen',
                'required' => FALSE,
                'passed' => ini_get('allow_url_fopen'),
                'message' => 'Povoleno',
                'errorMessage' => 'Zakázáno.',
                'description' => 'Povolte toho PHP nastavení nebo použijte rozšíření cURL.',
            ),
            array(
                'title' => 'Rozšíření cURL',
                'required' => FALSE,
                'passed' => $curl_support,
                'message' => $curl_version,
                'errorMessage' => 'Není zapnuto rozšíření cURL.',
                'description' => 'Aplikace pro komunikaci s jinými servery potřebuje buď knihovnu cURL nebo povolené nastavení "allow_url_fopen".',
            ),
            array(
                'title' => 'Rozšíření SOAP',
                'required' => FALSE,
                'passed' => $soap_support,
                'message' => 'Ano',
                'errorMessage' => 'Není zapnuto rozšíření SOAP.',
                'description' => 'Je potřeba pro komunikaci s datovou schránkou.',
            ),
            array(
                'title' => 'Rozšíření OpenSSL',
                'required' => FALSE,
                'passed' => $openssl_support,
                'message' => 'Ano',
                'errorMessage' => 'Není zapnuto rozšíření OpenSSL',
                'description' => 'Je potřeba pro elektronické podpisy u e-mailů a pro datovou schránku.',
            ),
            array(
                'title' => 'Rozšíření IMAP',
                'required' => FALSE,
                'passed' => $imap_support,
                'message' => $imap_version,
                'errorMessage' => 'Není zapnuto rozšíření IMAP',
                'description' => 'Je potřeba pro příjem e-mailových zpráv.',
            ),
            array(
                'title' => 'Rozšíření Fileinfo',
                'required' => FALSE,
                'passed' => extension_loaded('fileinfo'),
                'message' => 'Ano',
                'errorMessage' => 'Ne',
                'description' => 'Chybí rozšíření Fileinfo. Detekce MIME typů souborů bude omezena, jen podle přípony souboru.',
            ),
            array(
                'title' => 'Rozšíření ZIP',
                'required' => TRUE,
                'passed' => extension_loaded('zip'),
                'message' => 'Ano',
                'errorMessage' => 'Chybí rozšíření ZIP. Aplikaci není možné nainstalovat.',
            ),
            array(
                'title' => 'Zápis do dočasné složky',
                'required' => TRUE,
                'passed' => is_writable(TEMP_DIR),
                'message' => 'Povoleno',
                'errorMessage' => 'Není možné zapisovat do dočasné složky.',
                'description' => 'Povolte zápis do složky /client/temp/',
            ),
            array(
                'title' => 'Zápis do konfigurační složky',
                'required' => TRUE,
                'passed' => is_writable(CLIENT_DIR . '/configs/') && is_writable(CLIENT_DIR . '/configs/klient.ini'),
                'message' => 'Povoleno',
                'errorMessage' => 'Není možné zapisovat do konfigurační složky.',
                'description' => 'Povolte zápis do složky /client/configs/ a do souboru klient.ini, který se v ní nachází. Tato složka slouží k uložení některých nastavení aplikace.',
            ),
            array(
                'title' => 'Zápis do složky sessions',
                'required' => TRUE,
                'passed' => is_writable(CLIENT_DIR . '/sessions/'),
                'message' => 'Povoleno',
                'errorMessage' => 'Není možné zapisovat do složky sessions.',
                'description' => 'Povolte zápis do složky /client/sessions/. Tato složka slouží k ukládání různých stavů aplikace.',
            ),
            array(
                'title' => 'Zápis do složky s protokoly',
                'required' => FALSE,
                'passed' => is_writable(LOG_DIR),
                'message' => 'Povoleno',
                'errorMessage' => 'Není možné zapisovat do složky s protokoly.',
                'description' => 'Povolte <code>zápis</code> do složky /log/. Tato složka slouží k ukládání ladicích informací a protokolů o chybách aplikace.',
            ),
            array(
                'title' => 'Zápis do složky dokumentů',
                'required' => TRUE,
                'passed' => is_writable(CLIENT_DIR . '/files/dokumenty/'),
                'message' => 'Povoleno',
                'errorMessage' => 'Není možné zapisovat do složky dokumentů.',
                'description' => 'Povolte zápis do složky /client/files/dokumenty/. Tato složka slouží jako úložiště dokumentů.',
            ),
            array(
                'title' => 'Zápis do složky e-podatelny',
                'required' => TRUE,
                'passed' => is_writable(CLIENT_DIR . '/files/epodatelna/'),
                'message' => 'Povoleno',
                'errorMessage' => 'Není možné zapisovat do složky e-podatelny.',
                'description' => 'Povolte zápis do složky /client/files/epodatelna/. Tato složka slouží jako úložiště e-podatelny.',
            ),
        ));

        //$reflection = class_exists('ReflectionFunction') && !$this->iniFlag('zend.ze1_compatibility_mode') ? new ReflectionFunction('paint') : NULL;
        $requirements_nette = $this->paint(array(
            array(
                'title' => 'Web server',
                'message' => $_SERVER['SERVER_SOFTWARE'],
            ),
            array(
                'title' => 'PHP version',
                'required' => TRUE,
                'passed' => version_compare(PHP_VERSION, '5.2.0', '>='),
                'message' => PHP_VERSION,
                'description' => 'Your PHP version is too old. Nette Framework requires at least PHP 5.2.0 or higher.',
            ),
            array(
                'title' => 'Memory limit',
                'message' => ini_get('memory_limit'),
            ),
            array(
                'title' => 'Function ini_set',
                'required' => FALSE,
                'passed' => function_exists('ini_set'),
                'description' => 'Function <code>ini_set()</code> is disabled. Some parts of Nette Framework may not work properly.',
            ),
            array(
                'title' => 'Magic quotes',
                'required' => FALSE,
                'passed' => !$this->iniFlag('magic_quotes_gpc') && !$this->iniFlag('magic_quotes_runtime'),
                'message' => 'Disabled',
                'errorMessage' => 'Enabled',
                'description' => 'Magic quotes <code>magic_quotes_gpc</code> and <code>magic_quotes_runtime</code> are enabled and should be turned off. Nette Framework disables <code>magic_quotes_runtime</code> automatically.',
            ),
            array(
                'title' => 'Register_globals',
                'required' => TRUE,
                'passed' => !$this->iniFlag('register_globals'),
                'message' => 'Disabled',
                'errorMessage' => 'Enabled',
                'description' => 'Configuration directive <code>register_globals</code> is enabled. Nette Framework requires this to be disabled.',
            ),
            array(
                'title' => 'Zend.ze1_compatibility_mode',
                'required' => TRUE,
                'passed' => !$this->iniFlag('zend.ze1_compatibility_mode'),
                'message' => 'Disabled',
                'errorMessage' => 'Enabled',
                'description' => 'Configuration directive <code>zend.ze1_compatibility_mode</code> is enabled. Nette Framework requires this to be disabled.',
            ),
            array(
                'title' => 'Variables_order',
                'required' => TRUE,
                'passed' => strpos(ini_get('variables_order'), 'G') !== FALSE && strpos(ini_get('variables_order'),
                        'P') !== FALSE && strpos(ini_get('variables_order'), 'C') !== FALSE,
                'description' => 'Configuration directive <code>variables_order</code> is missing. Nette Framework requires this to be set.',
            ),
            /*    array(
              'title' => 'Reflection extension',
              'required' => TRUE,
              'passed' => (bool) $reflection,
              'description' => 'Reflection extension is required.',
              ), */

            /* array(
              'title' => 'Reflection phpDoc',
              'required' => FALSE,
              'passed' => $reflection ? strpos($reflection->getDocComment(), 'Paints') !== FALSE : FALSE,
              'description' => 'Reflection phpDoc are not available (probably due to an eAccelerator bug). Persistent parameters must be declared using static function.',
              ), */
            array(
                'title' => 'SPL extension',
                'required' => TRUE,
                'passed' => extension_loaded('SPL'),
                'description' => 'SPL extension is required.',
            ),
            array(
                'title' => 'PCRE extension',
                'required' => TRUE,
                'passed' => extension_loaded('pcre'),
                'description' => 'PCRE extension is required.',
            ),
            array(
                'title' => 'ICONV extension',
                'required' => TRUE,
                'passed' => extension_loaded('iconv') && (ICONV_IMPL !== 'unknown') && @iconv('UTF-16',
                        'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', 'test')) === 'test',
                'message' => 'Enabled and works properly',
                'errorMessage' => 'Disabled or works not properly',
                'description' => 'ICONV extension is required and must work properly.',
            ),
            array(
                'title' => 'Multibyte String extension',
                'required' => TRUE,
                'passed' => extension_loaded('mbstring'),
                'description' => 'Multibyte String extension is absent. Some internationalization components may not work properly.',
            ),
            array(
                'title' => 'PHP tokenizer',
                'required' => TRUE,
                'passed' => extension_loaded('tokenizer'),
                'description' => 'PHP tokenizer is required.',
            ),
            array(
                'title' => 'Multibyte String function overloading',
                'required' => TRUE,
                'passed' => !extension_loaded('mbstring') || !(mb_get_info('func_overload') & 2),
                'message' => 'Disabled',
                'errorMessage' => 'Enabled',
                'description' => 'Multibyte String function overloading is enabled. Nette Framework requires this to be disabled. If it is enabled, some string function may not work properly.',
            ),
            /*  array(
              'title' => 'SQLite extension',
              'required' => FALSE,
              'passed' => extension_loaded('sqlite'),
              'description' => 'SQLite extension is absent. You will not be able to use tags and priorities with <code>Nette\Caching\FileStorage</code>.',
              ),

              array(
              'title' => 'Memcache extension',
              'required' => FALSE,
              'passed' => extension_loaded('memcache'),
              'description' => 'Memcache extension is absent. You will not be able to use <code>Nette\Caching\MemcachedStorage</code>.',
              ),

              array(
              'title' => 'GD extension',
              'required' => FALSE,
              'passed' => extension_loaded('gd'),
              'description' => 'GD extension is absent. You will not be able to use <code>Nette\Image</code>.',
              ),

              array(
              'title' => 'Bundled GD extension',
              'required' => FALSE,
              'passed' => extension_loaded('gd') && GD_BUNDLED,
              'description' => 'Bundled GD extension is absent. You will not be able to use some function as <code>Nette\Image::filter()</code> or <code>Nette\Image::rotate()</code>.',
              ),

              array(
              'title' => 'ImageMagick library',
              'required' => FALSE,
              'passed' => @exec('identify -format "%w,%h,%m" ' . addcslashes(dirname(__FILE__) . '/assets/logo.gif', ' ')) === '176,104,GIF', // intentionally @
              'description' => 'ImageMagick server library is absent. You will not be able to use <code>Nette\ImageMagick</code>.',
              ), */
            array(
                'title' => 'HTTP extension',
                'required' => FALSE,
                'passed' => !extension_loaded('http'),
                'message' => 'Disabled',
                'errorMessage' => 'Enabled',
                'description' => 'HTTP extension has naming conflict with Nette Framework. You have to disable this extension or use „prefixed“ version.',
            ),
            array(
                'title' => 'HTTP_HOST or SERVER_NAME',
                'required' => TRUE,
                'passed' => isset($_SERVER["HTTP_HOST"]) || isset($_SERVER["SERVER_NAME"]),
                'message' => 'Present',
                'errorMessage' => 'Absent',
                'description' => 'Either <code>$_SERVER["HTTP_HOST"]</code> or <code>$_SERVER["SERVER_NAME"]</code> must be available for resolving host name.',
            ),
            array(
                'title' => 'REQUEST_URI or ORIG_PATH_INFO',
                'required' => TRUE,
                'passed' => isset($_SERVER["REQUEST_URI"]) || isset($_SERVER["ORIG_PATH_INFO"]),
                'message' => 'Present',
                'errorMessage' => 'Absent',
                'description' => 'Either <code>$_SERVER["REQUEST_URI"]</code> or <code>$_SERVER["ORIG_PATH_INFO"]</code> must be available for resolving request URL.',
            ),
            array(
                'title' => 'SCRIPT_FILENAME, SCRIPT_NAME, PHP_SELF',
                'required' => TRUE,
                'passed' => isset($_SERVER["SCRIPT_FILENAME"], $_SERVER["SCRIPT_NAME"],
                        $_SERVER["PHP_SELF"]),
                'message' => 'Present',
                'errorMessage' => 'Absent',
                'description' => '<code>$_SERVER["SCRIPT_FILENAME"]</code> and <code>$_SERVER["SCRIPT_NAME"]</code> and <code>$_SERVER["PHP_SELF"]</code> must be available for resolving script file path.',
            ),
            array(
                'title' => 'SERVER_ADDR or LOCAL_ADDR',
                'required' => TRUE,
                'passed' => isset($_SERVER["SERVER_ADDR"]) || isset($_SERVER["LOCAL_ADDR"]),
                'message' => 'Present',
                'errorMessage' => 'Absent',
                'description' => '<code>$_SERVER["SERVER_ADDR"]</code> or <code>$_SERVER["LOCAL_ADDR"]</code> must be available for detecting development / production mode.',
            ),
        ));



        $this->template->requirements = $requirements_nette;
        $this->template->requirements_ess = $requirements_ess;

        if (!$installed) {
            if (!$this->template->errors) {
                @$session->step['kontrola'] = 1;
            }
        }
    }

    public function renderDatabaze()
    {

        $session = $this->getSession('s3_install');
        if (!isset($session->step)) {
            $session->step = array();
        }
        if (@$session->step['databaze'] == 1) {
            $this->template->provedeno = 1;
        }

        $this->template->error = false;
        $this->template->tabulka_jiz_existuje = false;

        try {
            $db_config = GlobalVariables::get('database');
            dibi::connect($db_config);

            $db_tables = dibi::getDatabaseInfo()->getTableNames();

            $sql_template_source = file_get_contents(__DIR__ . '/mysql.sql');
            $sql_queries = explode(";", $sql_template_source);
            array_pop($sql_queries); // prázdný prvek za posledním středníkem

            // pridej SQL prikazy z aktualizaci
            Updates::init();
            $res = Updates::find_updates();
            $revisions = $res['revisions'];
            $alter_scripts = $res['alter_scripts'];
            foreach ($revisions as $revision)
                if ($revision >= 680 && isset($alter_scripts[$revision])) {
                    $sql_queries = array_merge($sql_queries, $alter_scripts[$revision]);
                }
            $latest_revision = $revision;

            $database_a = array(
                array(
                    'title' => 'DB driver',
                    'message' => $db_config->driver
                ),
                array(
                    'title' => 'DB server',
                    'message' => $db_config->host
                ),
                array(
                    'title' => 'DB přihlašovací jméno',
                    'message' => $db_config->username,
                ),
                array(
                    'title' => 'DB databáze',
                    'message' => $db_config->database,
                ),
            );

            foreach ($sql_queries as $query) {

                $query = str_replace("{tbls3}", '', $query);
                
                if ($this->getParameter('install', null)) {
                    // provedeni SQL skriptu
                    $this->template->db_install = 1;
                    try {
                        dibi::query($query);
                    } catch (DibiException $e) {
                        $this->template->error = true;
                        $sql_error = $e->getMessage();

                        if (strpos($query, "CREATE TABLE") !== false) {
                            // $message = "Tabulka byla úspěšně vytvořena";
                            $error_message = "Tabulku se nepodařilo vytvořit!";
                        } else if (strpos($query, "INSERT INTO") !== false) {
                            // $message = "Data do tabulky byla úspěšně nahrána.";
                            $error_message = "Data do tabulky se nepodařilo nahrát!";
                        } else if (strpos($query, "ALTER TABLE") !== false) {
                            // $message = "Struktura tabulky byla úspěšně upravena.";
                            $error_message = "Tabulku se nepodařilo změnit!";
                        } else {
                            // $message = "Databázový příkaz byl úspěšně proveden.";
                            $error_message = "Databázový příkaz nebyl správně proveden!";
                        }
                        $query_parts = explode("`", $query);
                        $database_a[] = array(
                            'title' => @$query_parts[1],
                            'required' => TRUE,
                            'passed' => false,
                            'message' => '',
                            'errorMessage' => $error_message,
                            'description' => "<p>SQL Chyba: " . $sql_error . " </p><p>QUERY: $query</p>",
                        );
                    }
                } else {
                    // predkontrola
                    $query_part = explode("`", $query);
                    if (( strpos($query, "CREATE") !== false ) && isset($query_part[1])) {
                        if (in_array($query_part[1], $db_tables)) {
                            $this->template->tabulka_jiz_existuje = true;
                            $database_a[] = array(
                                'title' => @$query_part[1],
                                'required' => TRUE,
                                'passed' => false,
                                'message' => ' ',
                                'errorMessage' => 'Tabulka již v databázi existuje.',
                                'description' => '',
                            );
                        }
                    }
                }
            }

            if ($this->getParameter('install', false)) {
                $this_installation = new Client_To_Update(CLIENT_DIR);
                $this_installation->update_revision_number($latest_revision);
            }

            $database = $this->paint($database_a);
            $this->template->database = $database;

            if (!($this->template->error) && isset($this->template->db_install)) {
                @$session->step['databaze'] = 1;
            }
        } catch (DibiDriverException $e) {
            $this->template->error = $e->getMessage();
        }
    }

    public function renderUrad()
    {
        $session = $this->getSession('s3_install');
        if (!isset($session->step)) {
            $session->step = array();
        }

        $client_config = (new Spisovka\ConfigClient())->get();
        $this->template->Urad = $client_config->urad;
    }

    public function renderEvidence()
    {
        $session = $this->getSession('s3_install');
        if (!isset($session->step)) {
            $session->step = array();
        }
        @$session->step['evidence'] = 0;

        $client_config = (new Spisovka\ConfigClient())->get();
        $this->template->CisloJednaci = $client_config->cislo_jednaci;
    }

    public function renderSpravce()
    {
        $session = $this->getSession('s3_install');
        if (!isset($session->step)) {
            $session->step = array();
        }
        if (@$session->step['spravce'] == 1) {
            $this->flashMessage('Správce již byl vytvořen.', 'warning');
            $this->template->provedeno = 1;
        }
    }

    public function renderKonec()
    {

        $session = $this->getSession('s3_install');

        $dokonceno = 1;
        $errors = array();

        if (!isset($session->step)) {
            $errors[] = "Nebyly provedeny žádné kroky ke správné instalaci. Proveďte instalaci podle od začátku a postupně!";
            $dokonceno = 0;
        }
        if (@$session->step['kontrola'] != 1) {
            $errors[] = "Instalace neprošla vstupní kontrolou na minimální požadavky aplikace!";
            $dokonceno = 0;
        }
        if (@$session->step['databaze'] != 1) {
            $errors[] = "Instalace neprošla procesem nahrání tabulek a dat do databáze!";
            $dokonceno = 0;
        }
        if (@$session->step['urad'] != 1) {
            $errors[] = "Instalace neprošla procesem uložení informace o úřadu/firmě!";
            $dokonceno = 0;
        }
        if (@$session->step['evidence'] != 1) {
            $errors[] = "Instalace neprošla procesem nastavení evidence!";
            $dokonceno = 0;
        }
        if (@$session->step['spravce'] != 1) {
            $errors[] = "Instalace neprošla procesem přidání správce systému!";
            $dokonceno = 0;
        }

        if (@$session->step['konec'] == 1) {
            $dokonceno = 1;
        }

        if ($dokonceno == 1) {

            // $client_config = (new Spisovka\ConfigClient())->get();
            $zerotime = mktime(0, 0, 0, 8, 20, 2008);
            $diff = time() - $zerotime;
            $diff = round($diff / 3600);
            $unique_signature = $diff . "#" . time();

            if ($fp = fopen(CLIENT_DIR . '/configs/install', 'wb')) {
                if (fwrite($fp, $unique_signature, strlen($unique_signature))) {
                    $dokonceno = 2;
                    if (!isset($session->step)) {
                        $session->step = array();
                    }
                    @$session->step['konec'] = 1;
                }
                @fclose($fp);
            }
        }

        $this->template->dokonceno = $dokonceno;
        $this->template->errors = $errors;
    }

    /*     * */

    protected function createComponentNastaveniUraduForm()
    {
        $client_config = GlobalVariables::get('client_config');
        $Urad = $client_config->urad;
        $stat_select = Subjekt::stat();

        $form1 = new Spisovka\Form();
        $form1->addText('nazev', 'Název:', 50, 100)
                ->setValue($Urad->nazev)
                ->addRule(Nette\Forms\Form::FILLED, 'Název úřadu musí být vyplněn.');
        $form1->addText('plny_nazev', 'Plný název:', 50, 200)
                ->setValue($Urad->plny_nazev);
        $form1->addText('zkratka', 'Zkratka:', 15, 30)
                ->setValue($Urad->zkratka)
                ->addRule(Nette\Forms\Form::FILLED, 'Zkratka úřadu musí být vyplněna.');

        $form1->addText('ulice', 'Ulice:', 50, 100)
                ->setValue($Urad->adresa->ulice);
        $form1->addText('mesto', 'Město:', 50, 100)
                ->setValue($Urad->adresa->mesto);
        $form1->addText('psc', 'PSČ:', 12, 50)
                ->setValue($Urad->adresa->psc);
        $form1->addSelect('stat', 'Stát:', $stat_select)
                ->setValue($Urad->adresa->stat);

        $form1->addText('ic', 'IČ:', 20, 50)
                ->setValue($Urad->firma->ico);
        $form1->addText('dic', 'DIČ:', 20, 50)
                ->setValue($Urad->firma->dic);

        $form1->addText('telefon', 'Telefon:', 50, 100)
                ->setValue($Urad->kontakt->telefon);
        $form1->addText('email', 'E-mail:', 50, 100)
                ->setValue($Urad->kontakt->email);
        $form1->addText('www', 'URL:', 50, 150)
                ->setValue($Urad->kontakt->www);


        $form1->addSubmit('upravit', 'Uložit a pokračovat v instalaci')
                ->onClick[] = array($this, 'nastavitUradClicked');

        return $form1;
    }

    public function nastavitUradClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $config_data = (new Spisovka\ConfigClient())->get();

        $config_data['urad']['nazev'] = $data['nazev'];
        $config_data['urad']['plny_nazev'] = $data['plny_nazev'];
        $config_data['urad']['zkratka'] = $data['zkratka'];

        $config_data['urad']['adresa']['ulice'] = $data['ulice'];
        $config_data['urad']['adresa']['mesto'] = $data['mesto'];
        $config_data['urad']['adresa']['psc'] = $data['psc'];
        $config_data['urad']['adresa']['stat'] = $data['stat'];

        $config_data['urad']['firma']['ico'] = $data['ic'];
        $config_data['urad']['firma']['dic'] = $data['dic'];

        $config_data['urad']['kontakt']['telefon'] = $data['telefon'];
        $config_data['urad']['kontakt']['email'] = $data['email'];
        $config_data['urad']['kontakt']['www'] = $data['www'];

        try {
            (new Spisovka\ConfigClient())->save($config_data);

            $session = $this->getSession('s3_install');
            if (!isset($session->step)) {
                $session->step = array();
            }
            @$session->step['urad'] = 1;
            $this->redirect('evidence');
        } catch (Nette\IOException $e) {

            $this->flashMessage('Informace o sobě se nepodařilo uložit!', 'warning');
            $this->flashMessage('Zkuste pokus o uložení provést znovu. V případě, že to nepomáhá, zkontrolujte existenci konfiguračního souboru a možnost zápisu do něj.',
                    'warning');
            $this->flashMessage('Exception: ' . $e->getMessage(), 'warning');
        }
    }

    protected function createComponentNastaveniCJForm()
    {

        $client_config = GlobalVariables::get('client_config');
        $CJ = $client_config->cislo_jednaci;

        $evidence = array("priorace" => "Priorace", "sberny_arch" => "Sběrný arch");

        $form1 = new Spisovka\Form();
        $form1->addRadioList('typ_evidence', 'Typ evidence:', $evidence)
                ->setValue($CJ->typ_evidence)
                ->addRule(Nette\Forms\Form::FILLED, 'Volba evidence musí být vybrána.');
        $form1->addText('maska', 'Maska:', 50, 100)
                ->setValue($CJ->maska)
                ->addRule(Nette\Forms\Form::FILLED, 'Maska čísla jednacího musí být vyplněna.');
        $form1->addText('pocatek_cisla', 'Nastavit počáteční pořadové číslo:', 10, 15)
                ->setValue($CJ->pocatek_cisla);

        $form1->addSubmit('upravit', 'Uložit a pokračovat v instalaci')
                ->onClick[] = array($this, 'nastavitCJClicked');

        return $form1;
    }

    public function nastavitCJClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        $config_data = (new Spisovka\ConfigClient())->get();

        $config_data['cislo_jednaci']['maska'] = $data['maska'];
        $config_data['cislo_jednaci']['typ_evidence'] = $data['typ_evidence'];
        $config_data['cislo_jednaci']['pocatek_cisla'] = $data['pocatek_cisla'];

        try {
            (new Spisovka\ConfigClient())->save($config_data);

            $session = $this->getSession('s3_install');
            if (!isset($session->step)) {
                $session->step = array();
            }
            @$session->step['evidence'] = 1;
            $this->redirect('spravce');
        } catch (Nette\IOException $e) {
            $this->flashMessage('Nastavení evidence se nepodařilo uložit!', 'warning');
            $this->flashMessage('Zkuste pokus o uložení provést znovu. V případě, že to nepomáhá, zkontrolujte existenci konfiguračního souboru a možnost zápisu do něj.',
                    'warning');
            $this->flashMessage('Exception: ' . $e->getMessage(), 'warning');
        }
    }

    protected function createComponentSpravceForm()
    {

        $form1 = new Spisovka\Form();
        $form1->addText('jmeno', 'Jméno:', 50, 150);
        $form1->addText('prijmeni', 'Příjmení:', 50, 150)
                ->addRule(Nette\Forms\Form::FILLED,
                        'Alespoň příjmení správce musí být vyplněno.');
        $form1->addText('titul_pred', 'Titul před:', 50, 150);
        $form1->addText('titul_za', 'Titul za:', 50, 150);
        $form1->addText('email', 'E-mail:', 50, 150);
        $form1->addText('telefon', 'Telefon:', 50, 150);
        $form1->addText('pozice', 'Funkce:', 50, 150);

        $form1->addText('username', 'Uživatelské jméno:', 30, 150)
                ->addRule(Nette\Forms\Form::FILLED,
                        'Uživatelské jméno správce musí být vyplněno.');
        $form1->addPassword('heslo', 'Heslo:', 30, 30)
                ->addRule(Nette\Forms\Form::FILLED, 'Heslo musí být vyplněné.');
        $form1->addPassword('heslo_potvrzeni', 'Heslo znovu:', 30, 30)
                ->addRule(Nette\Forms\Form::FILLED,
                        'Kontrolní heslo musí být vyplněné pro vyloučení překlepu hesla.')
                ->addConditionOn($form1["heslo"], Nette\Forms\Form::FILLED)
                ->addRule(Nette\Forms\Form::EQUAL, "Hesla se musí shodovat !", $form1["heslo"]);

        $form1->addSubmit('novy', 'Vytvořit správce')
                ->onClick[] = array($this, 'spravceClicked');

        return $form1;
    }

    public function spravceClicked(Nette\Forms\Controls\SubmitButton $button)
    {
        $data = $button->getForm()->getValues();

        dibi::query("SET sql_mode = ''");
        dibi::query("SET foreign_key_checks = 0");

        $data['stav'] = 0;
        $data['user_created'] = 1;
        $data['date_created'] = new DateTime();
        $data['user_modified'] = 1;
        $data['date_modified'] = new DateTime();

        $user_data = array(
            'username' => $data['username'],
            'heslo' => $data['heslo'],
            'role' => 1
        );

        unset($data['username'], $data['heslo'], $data['heslo_potvrzeni']);

        $auth = $this->context->createService('authenticatorUI');

        if (!$auth->vytvoritUcet((array) $data, $user_data, true)) {
            $this->flashMessage('Správce se nepodařilo vytvořit.', 'warning');
        } else {
            $session = $this->getSession('s3_install');
            if (!isset($session->step)) {
                $session->step = array();
            }
            @$session->step['spravce'] = 1;

            $this->redirect('konec');
        }
    }

    /*     * */

    private function iniFlag($var)
    {
        $status = strtolower(ini_get($var));
        return $status === 'on' || $status === 'true' || $status === 'yes' || $status % 256;
    }

    private function paint($requirements)
    {
        $this->template->redirect = round(time(), -1);
        if (!isset($_GET) || (isset($_GET['r']) && $_GET['r'] == $this->template->redirect)) {
            $this->template->redirect = NULL;
        }

        //$this->template->errors = FALSE;
        //$this->template->warnings = FALSE;

        foreach ($requirements as $id => $requirement) {
            $requirements[$id] = $requirement = (object) $requirement;
            if (isset($requirement->passed) && !$requirement->passed) {
                if ($requirement->required) {
                    $this->template->errors = TRUE;
                } else {
                    $this->template->warnings = TRUE;
                }
            }
        }

        return $requirements;
    }

    private function phpinfo_array($return = false)
    {

        ob_start();
        phpinfo(-1);

        $pi = preg_replace(
                array('#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
            '#<h1>Configuration</h1>#', "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
            "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
            '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
            . '<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
            '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
            '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
            "# +#", '#<tr>#', '#</tr>#'),
                array('$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
            '<h2>PHP Configuration</h2>' . "\n" . '<tr><td>PHP Version</td><td>$2</td></tr>' .
            "\n" . '<tr><td>PHP Egg</td><td>$1</td></tr>',
            '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
            '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
            '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'), ob_get_clean());

        $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
        unset($sections[0]);

        $pi = array();
        foreach ($sections as $section) {
            $n = substr($section, 0, strpos($section, '</h2>'));
            $askapache = [];
            preg_match_all(
                    '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
                    $section, $askapache, PREG_SET_ORDER);
            foreach ($askapache as $m)
                @$pi[$n][$m[1]] = (!isset($m[3]) || $m[2] == $m[3]) ? $m[2] : array_slice($m, 2);
        }

        return ($return === false) ? print_r($pi) : $pi;
    }

}
