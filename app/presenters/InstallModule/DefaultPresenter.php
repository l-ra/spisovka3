<?php

namespace Spisovka;

use Nette;

class Install_DefaultPresenter extends BasePresenter
{

    public function startup()
    {
        if (APPLICATION_INSTALLED && $this->action != "kontrola")
            $this->setView('instalovano');

        parent::startup();
    }

    public function beforeRender()
    {
        parent::beforeRender();

        $menu = [
            'uvod' => 'Úvod',
            'kontrola' => 'Kontrola',
            'databaze' => 'Nahrání databáze',
            'urad' => 'Nastavení klienta',
            'evidence' => 'Nastavení evidence',
            'spravce' => 'Nastavení správce',
            'konec' => 'Konec',
        ];
        $this->template->menu = $menu;
    }

    public function renderDefault()
    {
        $this->redirect('uvod');
    }

    public function renderUvod()
    {
        
    }

    public function renderKontrola()
    {
        $this->template->installed = APPLICATION_INSTALLED;

        $this->template->errors = FALSE;
        $this->template->warnings = FALSE;

        foreach (array('function_exists', 'version_compare', 'extension_loaded', 'ini_get') as $function) {
            if (!function_exists($function)) {
                $this->template->errors = "Error: function '$function' is required by Nette Framework and this Requirements Checker.";
            }
        }

        $phpinfo = $this->phpinfo_array(1);

        // cURL support
        $curl_version = "";
        if (function_exists('curl_version')) {
            $curli = curl_version();
            if (isset($curli['version']))
                $curl_version .= " libcurl " . $curli['version'] . "";
            if (isset($curli['host']))
                $curl_version .= " (" . $curli['host'] . ")";
            if (isset($curli['ssl_version']))
                $curl_version .= "\nSSL implementace: " . $curli['ssl_version'];
        }

        // IMAP support
        $imap_version = "";
        if (function_exists('imap_open')) {
            if (isset($phpinfo['imap']['IMAP c-Client Version']))
                $imap_version = "IMAP " . $phpinfo['imap']['IMAP c-Client Version'];

            if (isset($phpinfo['imap']['SSL Support']))
                $imap_version .= ", SSL " . $phpinfo['imap']['SSL Support'];
            if (isset($phpinfo['imap']['Kerberos Support']))
                $imap_version .= ", Kerberos " . $phpinfo['imap']['Kerberos Support'];
        }

        $imap_open_works = false;
        $test_email = __DIR__ . '/test.eml';
        if (function_exists('imap_open') && $imap_stream = imap_open($test_email, '', '')) {
            $imap_open_works = true;
            imap_close($imap_stream);
        }

        // DB test
        // Nesmíme použít dibi::connect, protože bychom přepsali připojení z bootstrapu!
        // Testovat zde databázi je nesmysl, protože bez databáze se žádný presenter vůbec nespustí
        $db_info = GlobalVariables::get('database');
        $database_connected = true;
        $database_info = $db_info['driver'] . '://' . $db_info['username'] . '@' . $db_info['host'] . '/' . $db_info['database'];

        // Appliaction info
        $app_info = new VersionInformation();

        define('CHECKER_VERSION', '1.4');

        $requirements_application = array(
            array(
                'title' => 'Aplikace',
                'message' => ( $app_info->name )
            ),
            array(
                'title' => 'Web server',
                'message' => $_SERVER['SERVER_SOFTWARE'],
            ),
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
                'passed' => $database_connected,
                'message' => $database_info,
                'errorMessage' => 'Nelze se připojit k databázi.',
                'description' => 'Databáze je nutná pro běh aplikace. Zkontrolujte správnost nastavení nebo dostupnost databázového serveru.<br />SQL chyba: ' . $database_info,
            ),
            array(
                'title' => 'Funkce mail()',
                'required' => FALSE,
                'passed' => function_exists('mail'),
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
                'passed' => extension_loaded('curl'),
                'message' => $curl_version,
                'errorMessage' => 'Chybí. Aplikace pro komunikaci s jinými servery potřebuje buď knihovnu cURL nebo povolené nastavení "allow_url_fopen".',
            ),
            array(
                'title' => 'Rozšíření Fileinfo',
                'required' => FALSE,
                'passed' => extension_loaded('fileinfo'),
                'message' => 'Ano',
                'errorMessage' => 'Chybí. Detekce MIME typů souborů bude omezena, jen podle přípony souboru.',
            ),
            array(
                'title' => 'Rozšíření GD',
                'required' => FALSE,
                'passed' => extension_loaded('gd'),
                'message' => 'Ano',
                'errorMessage' => 'Chybí. Je potřeba k tomu, aby vygenerované PDF soubory obsahovaly obrázky.',
            ),
            array(
                'title' => 'Rozšíření IMAP',
                'required' => FALSE,
                'passed' => extension_loaded('imap'),
                'message' => $imap_version,
                'errorMessage' => 'Chybí. Je potřeba pro příjem e-mailových zpráv.',
            )
        );

        if (extension_loaded('imap'))
            $requirements_application[] = array(
                'title' => 'Rozšíření IMAP umožňuje otevřít lokálně uložené e-maily',
                'required' => FALSE,
                'passed' => $imap_open_works,
                'message' => 'Ano',
                'errorMessage' => 'Ne. Bude použito náhradní řešení, které je pomalejší.',
            );
        $requirements_application[] = array(
            'title' => 'Rozšíření OpenSSL',
            'required' => FALSE,
            'passed' => extension_loaded('openssl'),
            'message' => 'Ano',
            'errorMessage' => 'Chybí. Je potřeba pro elektronické podpisy u e-mailů a pro datovou schránku.',
        );
        $requirements_application[] = array(
            'title' => 'Rozšíření SOAP',
            'required' => FALSE,
            'passed' => extension_loaded('soap'),
            'message' => 'Ano',
            'errorMessage' => 'Chybí. Je potřeba pro komunikaci s datovou schránkou.',
        );
        $requirements_application[] = array(
            'title' => 'Rozšíření ZIP',
            'required' => TRUE,
            'passed' => extension_loaded('zip'),
            'message' => 'Ano',
            'errorMessage' => 'Chybí. Aplikaci není možné nainstalovat.',
        );
        $requirements_application[] = array(
            'title' => 'Rozšíření Zlib',
            'required' => FALSE,
            'passed' => extension_loaded('zlib'),
            'message' => 'Ano',
            'errorMessage' => 'Chybí. Je nutné pro export do PDF.',
        );
        $requirements_application[] = array(
            'title' => 'Zápis do složky temp',
            'required' => TRUE,
            'passed' => is_writable(TEMP_DIR),
            'message' => 'Povoleno',
            'errorMessage' => 'Není možné zapisovat do složky s dočasnými soubory.',
            'description' => 'Povolte zápis do složky /client/temp/',
        );
        $requirements_application[] = array(
            'title' => 'Zápis do složky configs',
            'required' => TRUE,
            'passed' => is_writable(CLIENT_DIR . '/configs/') && is_writable(CLIENT_DIR . '/configs/klient.ini'),
            'message' => 'Povoleno',
            'errorMessage' => 'Není možné zapisovat do složky s konfiguračními soubory.',
            'description' => 'Povolte zápis do složky /client/configs/ a do souboru klient.ini, který se v ní nachází. Tato složka slouží k uložení některých nastavení aplikace.',
        );
        $requirements_application[] = array(
            'title' => 'Zápis do složky sessions',
            'required' => TRUE,
            'passed' => is_writable(CLIENT_DIR . '/sessions/'),
            'message' => 'Povoleno',
            'errorMessage' => 'Není možné zapisovat do složky sessions.',
            'description' => 'Povolte zápis do složky /client/sessions/. Tato složka slouží k ukládání různých stavů aplikace.',
        );
        $requirements_application[] = array(
            'title' => 'Zápis do složky s protokoly',
            'required' => FALSE,
            'passed' => is_writable(LOG_DIR),
            'message' => 'Povoleno',
            'errorMessage' => 'Není možné zapisovat do složky s protokoly.',
            'description' => 'Povolte <code>zápis</code> do složky /log/. Tato složka slouží k ukládání ladicích informací a protokolů o chybách aplikace.',
        );
        $requirements_application[] = array(
            'title' => 'Zápis do složky dokumentů',
            'required' => TRUE,
            'passed' => is_writable(CLIENT_DIR . '/files/dokumenty/'),
            'message' => 'Povoleno',
            'errorMessage' => 'Není možné zapisovat do složky dokumentů.',
            'description' => 'Povolte zápis do složky /client/files/dokumenty/. Tato složka slouží jako úložiště dokumentů.',
        );
        $requirements_application[] = array(
            'title' => 'Zápis do složky e-podatelny',
            'required' => TRUE,
            'passed' => is_writable(CLIENT_DIR . '/files/epodatelna/'),
            'message' => 'Povoleno',
            'errorMessage' => 'Není možné zapisovat do složky e-podatelny.',
            'description' => 'Povolte zápis do složky /client/files/epodatelna/. Tato složka slouží jako úložiště e-podatelny.',
        );


        /* ------------------------------------------------------------------------
         *  Nette requirements checker
         */
        $tests = array();
        $tests[] = array(
            'title' => 'Web server',
            'message' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'unknown',
        );
        $tests[] = array(
            'title' => 'PHP version',
            'required' => TRUE,
            'passed' => version_compare(PHP_VERSION, '5.3.1', '>='),
            'message' => PHP_VERSION,
            'description' => 'Your PHP version is too old. Nette Framework requires at least PHP 5.3.1 or higher.',
        );
        $tests[] = array(
            'title' => 'Memory limit',
            'message' => ini_get('memory_limit'),
        );
        if (!isset($_SERVER['SERVER_SOFTWARE']) || strpos($_SERVER['SERVER_SOFTWARE'], 'Apache')
                !== FALSE) {
            $tests['hf'] = array(
                'title' => '.htaccess file protection',
                'required' => FALSE,
                'description' => 'File protection by <code>.htaccess</code> is not present. You must be careful to put files into document_root folder.',
                'script' => '<script src="assets/denied/checker.js"></script> <script>displayResult("hf", typeof fileProtectionChecker == "undefined")</script>',
            );
            $tests['hr'] = array(
                'title' => '.htaccess mod_rewrite',
                'required' => FALSE,
                'description' => 'Mod_rewrite is probably not present. You will not be able to use Cool URL.',
                'script' => '<script src="assets/rewrite/checker"></script> <script>displayResult("hr", typeof modRewriteChecker == "boolean")</script>',
            );
        }
        $tests[] = array(
            'title' => 'Function ini_set()',
            'required' => FALSE,
            'passed' => function_exists('ini_set'),
            'description' => 'Function <code>ini_set()</code> is disabled. Some parts of Nette Framework may not work properly.',
        );
        $tests[] = array(
            'title' => 'Function error_reporting()',
            'required' => TRUE,
            'passed' => function_exists('error_reporting'),
            'description' => 'Function <code>error_reporting()</code> is disabled. Nette Framework requires this to be enabled.',
        );
        $tests[] = array(
            'title' => 'Function flock()',
            'required' => TRUE,
            'passed' => flock(fopen(__FILE__, 'r'), LOCK_SH),
            'description' => 'Function <code>flock()</code> is not supported on this filesystem. Nette Framework requires this to process atomic file operations.',
        );
        $tests[] = array(
            'title' => 'Register_globals',
            'required' => TRUE,
            'passed' => !$this->iniFlag('register_globals'),
            'message' => 'Disabled',
            'errorMessage' => 'Enabled',
            'description' => 'Configuration directive <code>register_globals</code> is enabled. Nette Framework requires this to be disabled.',
        );
        $tests[] = array(
            'title' => 'Variables_order',
            'required' => TRUE,
            'passed' => strpos(ini_get('variables_order'), 'G') !== FALSE && strpos(ini_get('variables_order'),
                    'P') !== FALSE && strpos(ini_get('variables_order'), 'C') !== FALSE,
            'description' => 'Configuration directive <code>variables_order</code> is missing. Nette Framework requires this to be set.',
        );
        $tests[] = array(
            'title' => 'Session auto-start',
            'required' => FALSE,
            'passed' => ini_get('session.auto_start') === "0",
            'description' => 'Session auto-start is enabled. Nette Framework recommends not to use this directive for security reasons.',
        );
        $tests[] = array(
            'title' => 'PCRE with UTF-8 support',
            'required' => TRUE,
            'passed' => @preg_match('/pcre/u', 'pcre'),
            'description' => 'PCRE extension must support UTF-8.',
        );
        $reflection = new \ReflectionMethod(__CLASS__, 'iniFlag');
        $tests[] = array(
            'title' => 'Reflection phpDoc',
            'required' => TRUE,
            'passed' => strpos($reflection->getDocComment(), 'Gets') !== FALSE,
            'description' => 'Reflection phpDoc are not available (probably due to an eAccelerator bug). You cannot use @annotations.',
        );
        $tests[] = array(
            'title' => 'ICONV extension',
            'required' => TRUE,
            'passed' => extension_loaded('iconv') && (ICONV_IMPL !== 'unknown') && @iconv('UTF-16',
                    'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', 'test')) === 'test',
            'message' => 'Enabled and works properly',
            'errorMessage' => 'Disabled or does not work properly',
            'description' => 'ICONV extension is required and must work properly.',
        );
        $tests[] = array(
            'title' => 'JSON extension',
            'required' => TRUE,
            'passed' => extension_loaded('json'),
        );
        $tests[] = array(
            'title' => 'Fileinfo extension',
            'required' => FALSE,
            'passed' => extension_loaded('fileinfo'),
            'description' => 'Fileinfo extension is absent. You will not be able to detect content-type of uploaded files.',
        );
        $tests[] = array(
            'title' => 'PHP tokenizer',
            'required' => TRUE,
            'passed' => extension_loaded('tokenizer'),
            'description' => 'PHP tokenizer is required.',
        );
//        $tests[] = array(
//            'title' => 'PDO extension',
//            'required' => FALSE,
//            'passed' => $pdo = extension_loaded('pdo') && PDO::getAvailableDrivers(),
//            'message' => $pdo ? 'Available drivers: ' . implode(' ', PDO::getAvailableDrivers())
//                : NULL,
//            'description' => 'PDO extension or PDO drivers are absent. You will not be able to use <code>Nette\Database</code>.',
//        );
        $tests[] = array(
            'title' => 'Multibyte String extension',
            'required' => TRUE,
            'passed' => extension_loaded('mbstring'),
            'description' => 'Multibyte String extension is absent. Some internationalization components may not work properly.',
        );
        $tests[] = array(
            'title' => 'Multibyte String function overloading',
            'required' => TRUE,
            'passed' => !extension_loaded('mbstring') || !(mb_get_info('func_overload') & 2),
            'message' => 'Disabled',
            'errorMessage' => 'Enabled',
            'description' => 'Multibyte String function overloading is enabled. Nette Framework requires this to be disabled. If it is enabled, some string function may not work properly.',
        );
//        $tests[] = array(
//            'title' => 'Memcache extension',
//            'required' => FALSE,
//            'passed' => extension_loaded('memcache'),
//            'description' => 'Memcache extension is absent. You will not be able to use <code>Nette\Caching\Storages\MemcachedStorage</code>.',
//        );
        $tests[] = array(
            'title' => 'GD extension',
            'required' => FALSE,
            'passed' => extension_loaded('gd'),
            'description' => 'GD extension is absent. You will not be able to use <code>Nette\Image</code>.',
        );
//        $tests[] = array(
//            'title' => 'Bundled GD extension',
//            'required' => FALSE,
//            'passed' => extension_loaded('gd') && GD_BUNDLED,
//            'description' => 'Bundled GD extension is absent. You will not be able to use some functions such as <code>Nette\Image::filter()</code> or <code>Nette\Image::rotate()</code>.',
//        );
        $tests[] = array(
            'title' => 'Fileinfo extension or mime_content_type()',
            'required' => FALSE,
            'passed' => extension_loaded('fileinfo') || function_exists('mime_content_type'),
            'description' => 'Fileinfo extension or function <code>mime_content_type()</code> are absent. You will not be able to determine mime type of uploaded files.',
        );
        $tests[] = array(
            'title' => 'Intl extension',
            'required' => TRUE,
            'passed' => class_exists('Transliterator', FALSE),
            'description' => 'Class Transliterator is absent, the output of Nette\Utils\Strings::webalize() and Nette\Utils\Strings::toAscii() may not be accurate for non-latin alphabets.',
        );
        $tests[] = array(
            'title' => 'HTTP_HOST or SERVER_NAME',
            'required' => TRUE,
            'passed' => isset($_SERVER['HTTP_HOST']) || isset($_SERVER['SERVER_NAME']),
            'message' => 'Present',
            'errorMessage' => 'Absent',
            'description' => 'Either <code>$_SERVER["HTTP_HOST"]</code> or <code>$_SERVER["SERVER_NAME"]</code> must be available for resolving host name.',
        );
        $tests[] = array(
            'title' => 'REQUEST_URI or ORIG_PATH_INFO',
            'required' => TRUE,
            'passed' => isset($_SERVER['REQUEST_URI']) || isset($_SERVER['ORIG_PATH_INFO']),
            'message' => 'Present',
            'errorMessage' => 'Absent',
            'description' => 'Either <code>$_SERVER["REQUEST_URI"]</code> or <code>$_SERVER["ORIG_PATH_INFO"]</code> must be available for resolving request URL.',
        );
        $tests[] = array(
            'title' => 'SCRIPT_NAME',
            'required' => TRUE,
            'passed' => isset($_SERVER['SCRIPT_NAME']),
            'message' => 'Present',
            'errorMessage' => 'Absent',
            'description' => '<code>$_SERVER["SCRIPT_NAME"]</code> must be available for resolving script file path.',
        );
        $tests[] = array(
            'title' => 'REMOTE_ADDR or php_uname("n")',
            'required' => TRUE,
            'passed' => isset($_SERVER['REMOTE_ADDR']) || function_exists('php_uname'),
            'message' => 'Present',
            'errorMessage' => 'Absent',
            'description' => '<code>$_SERVER["REMOTE_ADDR"]</code> or <code>php_uname("n")</code> must be available for detecting development / production mode.',
        );

        $this->template->requirements_nette = $this->paint($tests);
        $this->template->requirements_application = $this->paint($requirements_application);
    }

    /**
     * @param boolean $install   provest pouze kontrolu nebo nahrat data?
     */
    public function renderDatabaze($install = false)
    {
        if (!$install) {
            $db_config = GlobalVariables::get('database');
            $db_tables = dibi::getDatabaseInfo()->getTableNames();
            $output = [
                ['title' => 'Ovladač', 'message' => $db_config->driver],
                ['title' => 'Server', 'message' => $db_config->host],
                ['title' => 'Databázový uživatel', 'message' => $db_config->username],
                ['title' => 'Název databáze', 'message' => $db_config->database],
            ];
            $output[] = ['title' => 'Je databáze prázdná?',
                'message' => 'ano',
                'errorMessage' => 'ne',
                'description' => 'V databázi již existuje nějaká tabulka. Databáze musí být prázdná.',
                'required' => true,
                'passed' => !$db_tables,
            ];
            $this->template->error = !empty($db_tables);
            $this->template->output = $this->paint($output);
        } else {
            /* instalace */
            set_time_limit(0); // nutné, na Windows je MySQL velice pomalé
            $this->template->error = false;
            $install_script = file_get_contents(__DIR__ . '/mysql.sql');
            $initial_queries = explode(";", $install_script);
            array_pop($initial_queries); // prázdný prvek za posledním středníkem

            /* pridej SQL prikazy z aktualizaci */
            Updates::init();
            $res = Updates::find_updates();
            $revisions = $res['revisions'];
            $alter_scripts = $res['alter_scripts'];
            array_unshift($revisions, 1);

            foreach ($revisions as $revision) {
                $latest_revision = $revision;
                if ($revision == 1)
                    $queries = $initial_queries;
                else if ($revision <= 1450)
                    continue;
                else if (!isset($alter_scripts[$revision]))
                    continue;
                else
                    $queries = $alter_scripts[$revision];

                $query = ''; // potlac varovani
                try {
                    foreach ($queries as $query) {
                        dibi::query($query);
                    }
                } catch (Exception $e) {
                    $this->template->error = $e->getMessage();
                    $this->template->query = $query;
                    // pri chybe prerus instalaci databaze
                    break;
                }
            }

            $this_installation = new Client_To_Update(CLIENT_DIR);
            $this_installation->update_revision_number($latest_revision);
        }
    }

    public function renderUrad()
    {
        $client_config = (new ConfigClient())->get();
        $this->template->Urad = $client_config->urad;
    }

    public function renderEvidence()
    {
        $client_config = (new ConfigClient())->get();
        $this->template->CisloJednaci = $client_config->cislo_jednaci;
    }

    public function renderSpravce()
    {
        
    }

    public function renderKonec()
    {
        if (!Settings::get('installation_completed')) {
            Settings::set('installation_completed', true);
            Settings::set('installation_date', date(DATE_ATOM));
            $zerotime = mktime(0, 0, 0, 8, 20, 2008);
            $diff = time() - $zerotime;
            $diff = round($diff / 3600);
            Settings::set('app_id', $diff);
        }
    }

    protected function createComponentNastaveniUraduForm()
    {
        $client_config = GlobalVariables::get('client_config');
        $Urad = $client_config->urad;
        $stat_select = Subjekt::stat();

        $form1 = new Form();
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

        $config_data = (new ConfigClient())->get();

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
            (new ConfigClient())->save($config_data);

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

        $form1 = new Form();
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

        $config_data = (new ConfigClient())->get();

        $config_data['cislo_jednaci']['maska'] = $data['maska'];
        $config_data['cislo_jednaci']['typ_evidence'] = $data['typ_evidence'];
        $config_data['cislo_jednaci']['pocatek_cisla'] = $data['pocatek_cisla'];

        try {
            (new ConfigClient())->save($config_data);

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
        $form1 = new Form();
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

        $account_data = [
            'username' => $data['username'],
            'password' => $data['heslo'],
        ];

        unset($data['username'], $data['heslo'], $data['heslo_potvrzeni']);
        $osoba_data = (array) $data;

        $auth = $this->context->createService('authenticatorUI');
        // Komponentu je nutné připojit k presenteru, neboť volá flashMessage()
        $this->addComponent($auth, 'auth');
        if (!$auth->createUserAccount($osoba_data, $account_data, 1 /* role */)) {
            // nedelej nic, formular se zobrazi znovu i s chybovou zpravou
        } else {
            $this->redirect('konec');
        }
    }

    /**
     * Gets the boolean value of a configuration option.
     * @param  string  configuration option name
     * @return bool
     */
    private function iniFlag($var)
    {
        $status = strtolower(ini_get($var));
        return $status === 'on' || $status === 'true' || $status === 'yes' || (int) $status;
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
        phpinfo(INFO_MODULES);

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
