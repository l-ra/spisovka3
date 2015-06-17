<?php

use Tracy\Debugger;

if (!defined('KLIENT')) {
    echo "<h1>Chyba aplikace. Nebyl zjisten klient!</h1>";
    exit;
}

if (file_exists(APP_DIR . "/configs/servicemode")) {
    readfile(APP_DIR . "/configs/servicemode");
    exit;
}

try {

// setting memory_limit for PDF generation
    define('PDF_MEMORY_LIMIT', '512M');


// Step 1: Configure automatic loading
    if (!defined('LIBS_DIR'))
        define('LIBS_DIR', dirname(APP_DIR) . '/libs');
    define('VENDOR_DIR', dirname(APP_DIR) . '/vendor');

// prikaz nastavi loading pouze pro balicky instalovane Composerem
    require VENDOR_DIR . '/autoload.php';

    define('TEMP_DIR', CLIENT_DIR . '/temp');

// check if temp directory is writable
    if (file_put_contents(TEMP_DIR . '/_check', '') === FALSE) {
        throw new Exception("Nelze zapisovat do adresare '" . TEMP_DIR . "'");
    }

// Toto kontroluje standardni umisteni, prestoze uzivatel muze nadefinovat
// ukladani session do jineho adresare
    $session_dir = CLIENT_DIR . '/sessions';
    if (file_put_contents("$session_dir/_check", '') === FALSE) {
        throw new Exception("Nelze zapisovat do adresare '$session_dir'");
    }

// enable RobotLoader - this allows load all classes automatically
    $loader = new Nette\Loaders\RobotLoader();
    $loader->addDirectory(APP_DIR);
    $loader->addDirectory(LIBS_DIR);
    $cacheDir = TEMP_DIR . '/cache';
    if (!is_dir($cacheDir))
        mkdir($cacheDir);
    $loader->setCacheStorage(new Nette\Caching\Storages\FileStorage($cacheDir));
// mPDF nelze nacitat pres RobotLoader, protoze PHP by dosla pamet
//$loader->addClass('mPDF', LIBS_DIR . '/mpdf/mpdf.php');
    $loader->register();

    spl_autoload_register('mPDFautoloader');


// Step 2: Configure environment

    register_shutdown_function(array('ShutdownHandler', '_handler'));

// 2a) enable Nette\Debug for better exception and error visualisation

    define('LOG_DIR', dirname(APP_DIR) . '/log');

    if (!defined('DEBUG_ENABLE'))
        define('DEBUG_ENABLE', 0);
    Debugger::enable(DEBUG_ENABLE ? Debugger::DEVELOPMENT : Debugger::PRODUCTION, LOG_DIR);
// '%logDir%/php_error_'.date('Ym').'.log' - stary nazev souboru s logy
    Nette\Bridges\Framework\TracyBridge::initialize();

// 2b) vytvor DI kontejner

    createIniFiles();

    $cookie_path = str_replace('index.php', '', $_SERVER['PHP_SELF']);

    $configurator = new Nette\Configurator;
    $configurator
            ->setDebugMode((bool) DEBUG_ENABLE)
            ->setTempDirectory(TEMP_DIR)
            ->addParameters(['clientDir' => CLIENT_DIR,
                'cookiePath' => $cookie_path])
            ->addConfig(APP_DIR . '/configs/system.neon')
            ->addConfig(CLIENT_DIR . '/configs/database.neon');
    if (is_file(CLIENT_DIR . '/configs/system.neon'))
        $configurator->addConfig(CLIENT_DIR . '/configs/system.neon');

    $container = $configurator->createContainer();

// dynamicky uprav protokol v nastaveni PUBLIC_URL
    $publicUrl = $public_url;
    $httpRequest = $container->getByType('Nette\Http\IRequest');
    if ($httpRequest->isSecured())
        $publicUrl = str_replace('http:', 'https:', $publicUrl);
    Nette\Environment::setVariable('publicUrl', $publicUrl);
    unset($publicUrl);


// konfigurace spisovky

    Nette\Environment::setVariable('user_config', (new Spisovka\ConfigClient())->get());

// version information
    $app_info = file_get_contents(APP_DIR . '/configs/version');
    $app_info = trim($app_info); // trim the EOL character
    Nette\Environment::setVariable('app_info', $app_info);
    unset($app_info);

    $unique_info = @file_get_contents(CLIENT_DIR . '/configs/install');
    if ($unique_info === FALSE) {
        define('APPLICATION_INSTALL', 1);
        @ini_set('memory_limit', '128M');
    } else {
        Nette\Environment::setVariable('unique_info', $unique_info);
    }
    unset($unique_info);


// 3b) Load database
    try {
        $db_config = $container->parameters['database'];

        if (empty($db_config['driver']) || $db_config['driver'] == 'mysql')
            $db_config['driver'] = 'mysqli';

        // oprava chybne konfigurace na hostingu
        // profiler je bez DEBUG modu k nicemu, jen plytva pameti (memory leak)
        if (!$configurator->isDebugMode())
            $db_config['profiler'] = false;
        else if ($db_config['profiler']) {
            $db_config['profiler'] = array(
                'run' => true,
                'file' => LOG_DIR . '/mysql_' . KLIENT . '_' . date('Ymd') . '.log');
        }

        $connection = dibi::connect($db_config);
        if ($configurator->isDebugMode()) {
            // false - Neni treba explain SELECT dotazu
            $panel = new Dibi\Bridges\Tracy\Panel(false, DibiEvent::ALL);
            $panel->register($connection);
            unset($panel);
        }

        if (!$db_config['prefix'])
            $db_config['prefix'] = '';  // nahrad pripadnou null hodnotu za prazdny retezec
        dibi::getSubstitutes()->{'PREFIX'} = $db_config['prefix'];
    } catch (DibiDriverException $e) {
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
    } catch (DibiException $e) {
        // ignoruj
    }

    if ($force_https || $httpRequest->isSecured())
        Nette\Application\Routers\Route::$defaultFlags |= Nette\Application\Routers\Route::SECURED;

// Get router
    $application = $container->getByType('Nette\Application\Application');
    $router = $application->getRouter();

    $router[] = new Nette\Application\Routers\Route('index.php',
            array(
        'module' => 'Spisovka',
        'presenter' => 'Default',
        'action' => 'default',
            ), Nette\Application\Routers\Route::ONE_WAY);

// Uzivatel
    $router[] = new Nette\Application\Routers\Route('uzivatel/<action>/<id>',
            array(
        'module' => 'Spisovka',
        'presenter' => 'Uzivatel',
        'action' => 'default',
        'id' => NULL,
    ));
// Help
    $router[] = new Nette\Application\Routers\Route('napoveda/<param1>/<param2>/<param3>',
            array(
        'module' => 'Spisovka',
        'presenter' => 'Napoveda',
        'action' => 'default',
        'param1' => 'obsah',
        'param2' => 'param2',
        'param3' => 'param3'
    ));

// Admin module
    $router[] = new Nette\Application\Routers\Route('admin/<presenter>/<action>/<id>/<params>',
            array(
        'module' => 'Admin',
        'presenter' => 'Default',
        'action' => 'default',
        'id' => null,
        'params' => null
    ));

// E-podatelna module
    $router[] = new Nette\Application\Routers\Route('epodatelna/<presenter>/<action>/<id>',
            array(
        'module' => 'Epodatelna',
        'presenter' => 'Default',
        'action' => 'default',
        'id' => null
    ));
// Spisovna module
    $router[] = new Nette\Application\Routers\Route('spisovna/<presenter>/<action>',
            array(
        'module' => 'Spisovna',
        'presenter' => 'Default',
        'action' => 'default',
        'id' => NULL,
    ));
    $router[] = new Nette\Application\Routers\Route('spisovna/<presenter>/<id>/<action>',
            array(
        'module' => 'Spisovna',
        'presenter' => 'Default',
        'action' => 'detail',
        'id' => null
    ));
// Install module
    $router[] = new Nette\Application\Routers\Route('install/<action>/<id>/<params>',
            array(
        'module' => 'Install',
        'presenter' => 'Default',
        'action' => 'default',
        'id' => null,
        'params' => null
    ));

    $router[] = new Nette\Application\Routers\Route('zpravy/<action>/<id>',
            array(
        'module' => 'Spisovka',
        'presenter' => 'Zpravy',
        'action' => 'default',
        'id' => NULL,
    ));

    $router[] = new Nette\Application\Routers\Route('test/<action>', 'Test:Default:');

    $router[] = new Nette\Application\Routers\Route('<presenter>/<action>',
            array(
        'module' => 'Spisovka',
        'presenter' => 'Default',
        'action' => 'default',
        'id' => NULL,
    ));

    $router[] = new Nette\Application\Routers\Route('<presenter>/<id>/<action>',
            array(
        'module' => 'Spisovka',
        'presenter' => 'Default',
        'action' => 'detail',
        'id' => NULL,
    ));
} catch (Exception $e) {
    echo 'Behem inicializace aplikace doslo k vyjimce. Podrobnejsi informace lze nalezt v aplikacnim logu.<br>'
    . 'Podrobnosti: ' . $e->getMessage();
    throw $e;
}

// Step 5: Run the application!
$application->run();

function mPDFautoloader($class)
{
    if ($class == 'mPDF')
        require LIBS_DIR . '/mpdf/mpdf.php';
}

function createIniFiles()
{
    $dir = CLIENT_DIR . '/configs';
    createIniFile("$dir/klient.ini");
    createIniFile("$dir/epodatelna.ini");

    if (!is_file("$dir/database.neon") && is_file("$dir/system.ini"))
        migrateSystemIni();
}

function createIniFile($filename)
{
    if (file_exists($filename))
        return;

    $template = substr($filename, 0, -1);
    if (!copy($template, $filename))
        throw new Exception("Nemohu vytvorit soubor $filename.");

    $perm = 0640;
    @chmod($filename, $perm);
}

// Migrace na 3.5.0
// Prenese se to nejdulezitejsi - prihlasovaci udaje do databaze
// Ostatni pripadne upravy musi uzivatel provest rucne,
// konfigurace autentizace se tak jako tak zmenila
function migrateSystemIni()
{
    $dir = CLIENT_DIR . '/configs';
    $loader = new \Nette\DI\Config\Loader();
    $old_config = $loader->load("$dir/system.ini");

    $new_config = [ 'parameters' => [ 'database' => $old_config['common']['database']]];
    $loader->save($new_config, "$dir/database.neon");

    // Uklid. Prejmenovani pojisti, ze se konfigurace zmigruje jen jednou.
    unlink("$dir/system.in");
    rename("$dir/system.ini", "$dir/system.old");
}
