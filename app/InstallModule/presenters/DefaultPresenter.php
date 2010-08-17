<?php



/* use only DefaultPresenter in PHP 5.3 */

class Install_DefaultPresenter extends BasePresenter
{

    public function renderDefault()
    {

    }

    public function renderUvod()
    {

        if ( !$this->accepted() ) {
            $this->forward(':Install:Default:noaccess');
        }
        




    }

    public function renderKontrola()
    {

        //if ( !$this->accepted() ) {
        //    $this->forward(':Install:Default:noaccess');
        //}

        $this->template->errors = FALSE;
        $this->template->warnings = FALSE;

        foreach (array('function_exists', 'version_compare', 'extension_loaded', 'ini_get') as $function) {
            if (!function_exists($function)) {
                $this->template->errors = "Error: function '$function' is required by Nette Framework and this Requirements Checker.";
            }
        }

        $phpinfo = $this->phpinfo_array(1);

        // cURL supprot
        if(function_exists('curl_version')) {
            $curl_support = 1;
            $curli = curl_version();
            $user_agent = "";
            if(isset($curli['version'])) {
                $user_agent .= " libcurl ". $curli['version'] ."";
            }
            if(isset($curli['host'])) {
                $user_agent .= " (". $curli['host'] .")";
            }
            if(isset($curli['ssl_version'])) {
                $curl_ssl = 1;
                $curl_ssl_version = $curli['ssl_version'];
            } else {
                $curl_ssl = 0;
                $curl_ssl_version = "";
            }
        } else {
            $curl_support = 0;
        }

        // SOAP support
        if( class_exists('SoapClient') ) {
            $soap_support = 1;
        } else {
            $soap_support = 0;
        }

        // MAIL support
        if( function_exists('mail') ) {
            $mail_support = 1;
        } else {
            $mail_support = 0;
        }

        // IMAP support
        if( function_exists('imap_open') ) {
            $imap_support = 1;
            // test pripojeni
            $imap_server = '';
            if (@imap_open('{rebutia.i-dol.cz:110/pop3/novalidate-cert}INBOX','testpripojeni','test',OP_PROTOTYPE)) {
                $imap_pop3 = 1; $imap_pop3_text = '';
            } else {
                $imap_pop3 = 0; $imap_pop3_text = @imap_last_error();
                $imap_pop3_text = preg_replace('/\{(.*?)\}INBOX/', '', $imap_pop3_text);
            }
            if (@imap_open('{rebutia.i-dol.cz:995/pop3/ssl/novalidate-cert}INBOX','testpripojeni','test',OP_PROTOTYPE)) {
                $imap_pop3s = 1; $imap_pop3s_text = '';
            } else {
                $imap_pop3s = 0; $imap_pop3s_text = @imap_last_error();
                $imap_pop3s_text = preg_replace('/\{(.*?)\}INBOX/', '', $imap_pop3s_text);
            }
            if (@imap_open('{rebutia.i-dol.cz:143/imap/novalidate-cert}INBOX','testpripojeni','test',OP_PROTOTYPE)) {
                $imap_imap = 1; $imap_imap_text = '';
            } else {
                $imap_imap = 0; $imap_imap_text = @imap_last_error();
                $imap_imap_text = preg_replace('/\{(.*?)\}INBOX/', '', $imap_imap_text);
            }
            if (@imap_open('{rebutia.i-dol.cz:993/imap/ssl/novalidate-cert}INBOX','testpripojeni','test',OP_PROTOTYPE)) {
                $imap_imaps = 1; $imap_imaps_text = '';
            } else {
                $imap_imaps = 0; $imap_imaps_text = @imap_last_error();
                $imap_imaps_text = preg_replace('/\{(.*?)\}INBOX/', '', $imap_imaps_text);
            }

            if(isset($phpinfo['imap']['IMAP c-Client Version'])) {
                $imap_version = "IMAP ". $phpinfo['imap']['IMAP c-Client Version'];
            } else {
                $imap_version = "";
            }
            if(isset($phpinfo['imap']['SSL Support'])) {
                if( $phpinfo['imap']['SSL Support'] == "enabled" ) {
                    $imap_ssl = 1;
                } else if ( $phpinfo['imap']['SSL Support'] == "disabled" ) {
                    $imap_ssl = 0;
                } else {
                    $imap_ssl = -1;
                }
            } else {
                $imap_ssl = -1;
            }

        } else {
            $imap_support = 0;
            $imap_version = "";
            $imap_ssl = -1;
        }

        $imap_ssl_array = array('1'=>'Zapnuta','0'=>'Vypnuta','-1'=>'Nelze zjistit');

        // OpenSSL support
        if( function_exists('openssl_pkcs7_verify') ) {
            $openssl_support = 1;
        } else {
            $openssl_support = 0;
        }

        // DB test
        try {
            $db_info = Environment::getConfig('database');
            dibi::connect($db_info);
            $database_support = 1;
            $database_info = $db_info['driver'] .'://'. $db_info['username'] .'@'. $db_info['host'] .'/'. $db_info['database'];
        } catch (DibiDriverException $e) {
            $database_support = 0;
            $database_info = $e->getMessage();
        }

        // Appliaction info
        $app_info = Environment::getVariable('app_info');
        if ( !empty($app_info) ) {
            $app_info = explode("#",$app_info);
        } else {
            $app_info = array('3.x','rev.X','OSS Spisová služba v3','1270716764');
        }

        define('CHECKER_VERSION', '1.4');


        $requirements_ess = $this->paint( array(
            array(
		'title' => 'Aplikace',
		/*'message' => ( @$app_info[2] .' ('. @$app_info[1] .', vydáno '. date('j.n.Y',@$app_info[3]) .')')*/
                'message' => $app_info[2]
            ),
            array(
		'title' => 'Web server',
		'message' => $_SERVER['SERVER_SOFTWARE'],
            ),

            array(
		'title' => 'PHP verze',
		'required' => TRUE,
		'passed' => version_compare(PHP_VERSION, '5.2.0', '>='),
		'message' => PHP_VERSION,
		'description' => 'Your PHP version is too old. Nette Framework requires at least PHP 5.2.0 or higher.',
            ),

            array(
		'title' => 'Databáze',
		'required' => TRUE,
		'passed' => $database_support,
		'message' => $database_info,
                'errorMessage' => 'Nelze se připojit k databázi.',
		'description' => 'Databáze je nutná pro běh aplikace. Zkontrolujte správnost nastavení nebo dostupnost databázového serveru.<br />SQL chyba: '. $database_info,
            ),

            array(
		'title' => 'Podpora cURL',
		'required' => FALSE,
		'passed' => $curl_support,
		'message' => $user_agent,
		'errorMessage' => 'Není zapnuta podpora knihovny cURL.',
		'description' => 'Je nutná pro vzdálenou komunikaci. Používá se pro komunikaci s ISDS, CzechPoint a hledání v systému ARES.',
            ),

            array(
		'title' => 'Podpora cURL SSL',
		'required' => FALSE,
		'passed' => $curl_support,
		'message' => $curl_ssl_version,
		'errorMessage' => ($curl_support==1)?'Není možné použít cURL k zabezpečené komunikaci přes SSL':'Není zapnuta podpora cURL.',
		'description' => 'Pro vzdálenou komunikaci s ISDS a CzechPoint je potřeba šifrovaného spojení (SSL).',
            ),
            array(
		'title' => 'Podpora SOAP',
		'required' => FALSE,
		'passed' => $soap_support,
		'message' => '',
		'errorMessage' => 'Není zapnuta podpora knihovny SOAP (SoapClient)',
		'description' => 'Je potřeba pro komunikaci a práci s ISDS a CzechPoint.',
            ),
            array(
		'title' => 'Podpora OpenSSL',
		'required' => FALSE,
		'passed' => $openssl_support,
		'message' => 'povoleno',
		'errorMessage' => 'Není zapnuta plná podpora knihovny OpenSSL',
		'description' => 'Je potřeba pro ověřování a podepisování kvalifikovaných emailových zpráv.',
            ),
            array(
		'title' => 'Podpora mail()',
		'required' => FALSE,
		'passed' => $mail_support,
		'message' => 'povoleno',
		'errorMessage' => 'Není zapnuta podpora funkce mail()',
		'description' => 'Je potřeba pro odesilání emailových zpráv.',
            ),
            array(
		'title' => 'Podpora IMAP',
		'required' => FALSE,
		'passed' => $imap_support,
		'message' => $imap_version,
		'errorMessage' => 'Není zapnuta podpora knihovny IMAP',
		'description' => 'Je potřeba pro příjem emailových zpráv.',
            ),
            array(
		'title' => '  IMAP - příjem přes POP3',
		'required' => FALSE,
		'passed' => $imap_pop3,
		'message' => 'Povoleno',
		'errorMessage' => $imap_pop3_text,
		'description' => 'Je potřeba pro odesilání emailových zpráv.',
            ),
            array(
		'title' => '  IMAP - příjem přes POP3s',
		'required' => FALSE,
		'passed' => $imap_pop3s,
		'message' => 'Povoleno',
		'errorMessage' => $imap_pop3s_text,
		'description' => 'Je potřeba pro odesilání emailových zpráv.',
            ),
            array(
		'title' => '  IMAP - příjem přes IMAP',
		'required' => FALSE,
		'passed' => $imap_imap,
		'message' => 'Povoleno',
		'errorMessage' => $imap_imap_text,
		'description' => 'Je potřeba pro odesilání emailových zpráv.',
            ),
            array(
		'title' => '  IMAP - příjem přes IMAPs',
		'required' => FALSE,
		'passed' => $imap_imaps,
		'message' => 'Povoleno',
		'errorMessage' => $imap_imaps_text,
		'description' => 'Je potřeba pro odesilání emailových zpráv.',
            ),

            

            array(
		'title' => 'Zápis do dočasné složky',
		'required' => TRUE,
		'passed' => is_writable(APP_DIR .'/temp/'),
		'message' => 'Povoleno',
		'errorMessage' => 'Není možné zapisovat do dočasné složky.',
		'description' => 'Povolte zápis do složky /app/temp/',
            ),
            array(
		'title' => 'Zápis do konfigurační složky',
		'required' => FALSE,
		'passed' => is_writable(APP_DIR .'/configs/'),
		'message' => 'Povoleno',
		'errorMessage' => 'Není možné zapisovat do konfigurační složky.',
		'description' => 'Povolte zápis do složky /app/configs/. Tato složka slouží k uživateskému ukládání nastavení klienta, e-podatelny apod.',
            ),
            array(
		'title' => 'Zápis do složky sessions',
		'required' => TRUE,
		'passed' => is_writable(APP_DIR .'/sessions/'),
		'message' => 'Povoleno',
		'errorMessage' => 'Není možné zapisovat do složky sessions.',
		'description' => 'Povolte zápis do složky /app/sessions/. Tato složka slouží k ukládání různých stavů aplikace.',
            ),
            array(
		'title' => 'Zápis do logovací složky',
		'required' => FALSE,
		'passed' => is_writable(APP_DIR .'/log/'),
		'message' => 'Povoleno',
		'errorMessage' => 'Není možné zapisovat do logovací složky.',
		'description' => 'Povolte zápis do složky /app/log/. Tato složka slouží k ukládání různých logovacích a chybových hlášek.<br / >
                                  Není nutná. Pokud však chcete zaznamenávat chybové hlášky, je potřeba tuto složku k zápisu povolit.',
            ),

        ));

        //$reflection = class_exists('ReflectionFunction') && !$this->iniFlag('zend.ze1_compatibility_mode') ? new ReflectionFunction('paint') : NULL;
        $requirements_nette = $this->paint( array(
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

            'ha' => array(
		'title' => '.htaccess file protection',
		'required' => FALSE,
		'description' => 'File protection by <code>.htaccess</code> is optional. If it is absent, you must be careful to put files into document_root folder.',
		'script' => "var el = document.getElementById('resha');\nel.className = typeof checkerScript == 'undefined' ? 'passed' : 'warning';\nel.parentNode.removeChild(el.nextSibling.nodeType === 1 ? el.nextSibling : el.nextSibling.nextSibling);",
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
		'passed' => strpos(ini_get('variables_order'), 'G') !== FALSE && strpos(ini_get('variables_order'), 'P') !== FALSE && strpos(ini_get('variables_order'), 'C') !== FALSE,
		'description' => 'Configuration directive <code>variables_order</code> is missing. Nette Framework requires this to be set.',
            ),

            /*array(
		'title' => 'Reflection extension',
		'required' => TRUE,
		'passed' => (bool) $reflection,
		'description' => 'Reflection extension is required.',
            ),

            array(
		'title' => 'Reflection phpDoc',
		'required' => FALSE,
		'passed' => $reflection ? strpos($reflection->getDocComment(), 'Paints') !== FALSE : FALSE,
    		'description' => 'Reflection phpDoc are not available (probably due to an eAccelerator bug). Persistent parameters must be declared using static function.',
            ),*/

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
		'passed' => extension_loaded('iconv') && (ICONV_IMPL !== 'unknown') && @iconv('UTF-16', 'UTF-8//IGNORE', iconv('UTF-8', 'UTF-16//IGNORE', 'test')) === 'test',
		'message' => 'Enabled and works properly',
		'errorMessage' => 'Disabled or works not properly',
		'description' => 'ICONV extension is required and must work properly.',
            ),

            array(
		'title' => 'Multibyte String extension',
		'required' => FALSE,
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

            array(
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
            ),

            array(
		'title' => 'Fileinfo extension or mime_content_type()',
		'required' => FALSE,
		'passed' => extension_loaded('fileinfo') || function_exists('mime_content_type'),
		'description' => 'Fileinfo extension or function <code>mime_content_type()</code> are absent. You will not be able to determine mime type of uploaded files.',
            ),

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
		'passed' => isset($_SERVER["SCRIPT_FILENAME"], $_SERVER["SCRIPT_NAME"], $_SERVER["PHP_SELF"]),
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

    }

    public function renderDatabaze()
    {
        try {
            $db_info = Environment::getConfig('database');
            dibi::connect($db_info);
        } catch (DibiDriverException $e) {



            $database_info = $e->getMessage();
        }
    }

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

	foreach ($requirements as $id => $requirement)
	{
		$requirements[$id] = $requirement = (object) $requirement;
		if (isset($requirement->passed) && !$requirement->passed) {
			if ($requirement->required) {
				$this->template->errors = TRUE;
			} else {
				$this->template->warnings = TRUE;
			}
		}
	}

        return  $requirements;

    }

    private function accepted()
    {
        $code = $dokument_id = $this->getParam('code',null);
        $namespace = Environment::getSession('s3_install');
        if (isset($namespace->code)  ) {
            return true;
        } else if (is_null($code) ) {
            return false;
        } else {
            if ( $code != KLIENT ) {
                return false;
            } else {
                $namespace->code = KLIENT;
                return true;
            }
        }
    }

    private function phpinfo_array($return=false){
 
        ob_start();
        phpinfo(-1);

        $pi = preg_replace(
            array('#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
                '#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
                "#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
                .'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
                '#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
                '#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
                "# +#", '#<tr>#', '#</tr>#'),
            array('$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
                '<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
                "\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
                '<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
                '<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
                '<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'),
        ob_get_clean());

        $sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
        unset($sections[0]);

        $pi = array();
        foreach($sections as $section){
            $n = substr($section, 0, strpos($section, '</h2>'));
            preg_match_all(
                '#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
                $section, $askapache, PREG_SET_ORDER);
            foreach($askapache as $m)
                @$pi[$n][$m[1]]=(!isset($m[3])||$m[2]==$m[3])?$m[2]:array_slice($m,2);
        }

        return ($return === false) ? print_r($pi) : $pi;
    }

}
