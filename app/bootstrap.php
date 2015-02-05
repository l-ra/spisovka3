<?php

if ( !defined('KLIENT') ) {
    echo "<h1>Chyba aplikace. Nebyl zjisten klient!</h1>";
    exit;
}

if (file_exists(APP_DIR ."/configs/servicemode")) {
    readfile(APP_DIR ."/configs/servicemode");
    exit;
}

// Step 1: Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';

try {

// Step 2: Configure environment

// 2a) enable Nette\Debug for better exception and error visualisation
Environment::setVariable('logDir',APP_DIR .'/../log');

if ( !defined('DEBUG_ENABLE') )
    define('DEBUG_ENABLE', 0);
if ( DEBUG_ENABLE ) {
    Environment::setMode(Environment::DEVELOPMENT);
    Environment::setMode(Environment::PRODUCTION, FALSE);
    Debug::enable(Debug::DEVELOPMENT, '%logDir%/php_error_'.date('Ym').'.log');
} else {
    Environment::setMode(Environment::PRODUCTION);
    Debug::enable(Debug::PRODUCTION, '%logDir%/php_error_'.date('Ym').'.log');
}

// 2b) load configuration from config.ini file
$basePath = Environment::getHttpRequest()->getUri()->basePath;

// dynamicky uprav protokol v nastaveni BASE_URI
$baseUri = BASE_URI;
if (Environment::getHttpRequest()->isSecured())
    $baseUri = str_replace('http:', 'https:', $baseUri);
Environment::setVariable('baseUri', $baseUri);
unset($baseUri);

Environment::setVariable('klientUri', $basePath );

$unique_info = @file_get_contents(CLIENT_DIR .'/configs/install');
if ( $unique_info === FALSE ) {
    define('APPLICATION_INSTALL',1);
    @ini_set('memory_limit','128M');
} else {
    Environment::setVariable('unique_info', $unique_info);
}
unset($unique_info);

Environment::loadConfig(CLIENT_DIR .'/configs/system.ini');
$user_config = Config::fromFile(CLIENT_DIR .'/configs/klient.ini');
Environment::setVariable('user_config', $user_config);

// setting memory_limit for PDF generate
define('PDF_MEMORY_LIMIT','512M');

// app info
$app_info = @file_get_contents(APP_DIR .'/configs/version');
// trim the EOL character
$app_info = trim($app_info);
Environment::setVariable('app_info', $app_info);
unset($app_info);

// 2c) check if directory /app/temp is writable
Environment::setVariable('tempDir',CLIENT_DIR .'/temp');
if (@file_put_contents(Environment::expand('%tempDir%/_check'), '') === FALSE) {
	throw new Exception("Nelze zapisovat do adresare '" . Environment::getVariable('tempDir') . "'");
}

// 2d) enable RobotLoader - this allows load all classes automatically
$loader = new RobotLoader();
$loader->addDirectory(APP_DIR);
$loader->addDirectory(LIBS_DIR);
// mPDF nelze nacitat pres RobotLoader, protoze PHP by dosla pamet
$loader->addClass('mPDF', LIBS_DIR . '/mpdf/mpdf.php');
$loader->register();

// 2e) setup sessions
$session = Environment::getSession();
$session->setName('SpisovkaSessionID');
$session->setSavePath(CLIENT_DIR . '/sessions/');

$cookie_path = str_replace('index.php', '', $_SERVER['PHP_SELF']);
$session->setCookieParams($cookie_path);

// Step 3: Configure application
$application = Environment::getApplication();
$application->errorPresenter = 'Error';
$application->catchExceptions = Environment::isProduction();

register_shutdown_function(array('ShutdownHandler', '_handler'));

// 3a) Load components
require_once APP_DIR . '/components/DatePicker/DatePicker.php';
function Form_addDatePicker(Form $_this, $name, $label, $cols = NULL, $maxLength = NULL)
{
    return $_this[$name] = new DatePicker($label, $cols, $maxLength);
}
require_once APP_DIR . '/components/DatePicker/DatePicker.php';
function Form_addDateTimePicker(Form $_this, $name, $label, $cols = NULL, $maxLength = NULL)
{
  return $_this[$name] = new DateTimePicker($label, $cols, $maxLength);
}
Form::extensionMethod('Form::addDatePicker', 'Form_addDatePicker');
Form::extensionMethod('Form::addDateTimePicker', 'Form_addDateTimePicker');

Mail::$defaultMailer = 'ESSMailer';

// 3b) Load database
try {
    $db_config = Environment::getConfig('database')->toArray();
    
    // oprava chybne konfigurace na hostingu
    // profiler je bez DEBUG modu k nicemu, jen plytva pameti (memory leak)
    if (!DEBUG_ENABLE)
        $db_config['profiler'] = false;
        
    dibi::connect($db_config);
    dibi::addSubst('PREFIX', $db_config['prefix']);
    if ( !Environment::isProduction() ) {
        $profiler = dibi::getProfiler();
        if ($profiler)
            $profiler->setFile(APP_DIR .'/../log/mysql_'. KLIENT .'_'. date('Ymd') .'.log');
    }
    define('DB_PREFIX', $db_config['prefix']);
}
catch (DibiDriverException $e) {
    echo 'Aplikaci se nepodarilo pripojit do databaze.<br>';
    throw $e;
}

// Step 4: Setup application router

// 
// Detect and set HTTP protocol => HTTP(80) or HTTPS(443)
// 
$force_https = false;
try {
    // Nasledujici prikaz funguje az pote, co je provedena instalace
    $force_https = Settings::get('router_force_https', false);
}
catch(DibiException $e) {
    // ignoruj
}

if ($force_https || Environment::getHttpRequest()->isSecured())
    Route::$defaultFlags |= Route::SECURED;

// Get router
$router = $application->getRouter();

// Cool URL detection
// Detekce je nespolehliva, bez mod_env nefunguje
// Proto je zde moznost specifikovat nastaveni primo v system.ini

$clean_url = Environment::getConfig('clean_url');

if ($clean_url === null)
    if ( isset($_SERVER['HTTP_MOD_REWRITE']) && $_SERVER['HTTP_MOD_REWRITE'] == 'On' )
        // Detect in $_SERVER['HTTP_MOD_REWRITE'] 
        // Apache => .htaccess directive SetEnv HTTP_MOD_REWRITE On
        // Nginx  => nginx.conf directive fastcgi_param HTTP_MOD_REWRITE On;
        $clean_url = true;
        
    else if ( isset($_SERVER['REDIRECT_HTTP_MOD_REWRITE']) 
                && $_SERVER['REDIRECT_HTTP_MOD_REWRITE'] == 'On' )
        $clean_url = true;
    else
        $clean_url = false;

if ( $clean_url ) {
    define('IS_SIMPLE_ROUTER',0);
    
    $router[] = new Route('index.php', array(
                'module'    => 'Spisovka',
                'presenter' => 'Default',
                'action' => 'default',
                ), Route::ONE_WAY);
        
	$router[] = new Route('instalace.php', array(
                'module'    => 'Install',
                'presenter' => 'Default',
                'action' => 'uvod',
                ), Route::ONE_WAY); 
	
    $router[] = new Route('kontrola.php', array(
                'module'    => 'Install',
                'presenter' => 'Default',
                'action' => 'kontrola',
                'no_install' => 1
                ), Route::ONE_WAY);        

	// Uzivatel
    $router[] = new Route('uzivatel/<action>/<id>', array(
                'module'    => 'Spisovka',
                'presenter' => 'Uzivatel',
                'action' => 'default',
                'id' => NULL,
                ));
	// Help
    $router[] = new Route('napoveda/<param1>/<param2>/<param3>', array(
                'module'    => 'Spisovka',
                'presenter' => 'Napoveda',
                'action' => 'default',
                'param1' => 'obsah',
                'param2' => 'param2',
                'param3' => 'param3'
                ));
	// Error
    $router[] = new Route('error/<action>/<id>', array(
                /*'module'    => 'Spisovka',*/
                'presenter' => 'Error',
                'action' => 'default',
                'id' => NULL,
                ));

    // Admin module
    $router[] = new Route('admin/<presenter>/<action>/<id>/<params>', array(
                'module'    => 'Admin',
                'presenter' => 'Default',
                'action'    => 'default',
                'id'        => null,
                'params'    => null
                ));
    // E-podatelna module
    $router[] = new Route('epodatelna/<presenter>/<action>/<id>', array(
                'module'    => 'Epodatelna',
                'presenter' => 'Default',
                'action'    => 'default',
                'id'        => null
                ));
    // Spisovna module
    $router[] = new Route('spisovna/<presenter>/<action novy|nova|upravit|seznam|vyber|pridat|odeslat|odpoved|prijem|keskartaciseznam|skartace|reset>', array(
                'module'    => 'Spisovna',
                'presenter' => 'Default',
                'action' => 'default',
                'id' => NULL,
                ));
    $router[] = new Route('spisovna/<presenter>/<id>/<action>', array(
                'module'    => 'Spisovna',
                'presenter' => 'Default',
                'action'    => 'detail',
                'id'        => null
                ));
    // Install module
    $router[] = new Route('install/<action>/<id>/<params>', array(
                'module'    => 'Install',
                'presenter' => 'Default',
                'action'    => 'default',
                'id'        => null,
                'params'    => null
                ));

    $router[] = new Route('zpravy/<action>/<id>', array(
                'module'    => 'Spisovka',
                'presenter' => 'Zpravy',
                'action' => 'default',
                'id' => NULL,
                ));
                
    $router[] = new Route('<presenter>/<action novy|nova|upravit|seznam|vyber|pridat|odeslat|odpoved|reset|filtrovat|spustit>', array(
                'module'    => 'Spisovka',
                'presenter' => 'Default',
                'action' => 'default',
                'id' => NULL,
                ));

    // Basic router
    $router[] = new Route('<presenter>/<id>/<action>', array(
                'module'    => 'Spisovka',
                'presenter' => 'Default',
                'action' => 'detail',
                'id' => NULL,
                ));
        
} else {
        define('IS_SIMPLE_ROUTER',1);
        
        $path = Environment::getHttpRequest()->getOriginalUri()->getPath();
        if ( strpos($path,"/instalace.php") !== false ) {
            $router[] = new SimpleRouter('Install:Default:uvod');
        } else if ( strpos($path,"/kontrola.php") !== false ) {
            Environment::setVariable('no_install', 1);
            $router[] = new SimpleRouter('Install:Default:kontrola');
        } else {
            $router[] = new SimpleRouter('Spisovka:Default:default');
        }
	
}

}
catch (Exception $e) {
    echo 'Behem inicializace aplikace doslo k vyjimce. Podrobnejsi informace lze nalezt v aplikacnim logu.<br>'
        .'Podrobnosti: ' . $e->getMessage();
    throw $e;
}

// Step 5: Run the application!
$application->run();