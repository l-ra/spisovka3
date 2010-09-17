<?php

// Step 1: Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';

// Step 2: Configure environment
// 2a) enable Nette\Debug for better exception and error visualisation
//if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
//    Environment::setMode(Environment::DEVELOPMENT);
//    Debug::enable(Debug::DEVELOPMENT, '%logDir%/php_error.log');
//} else {
//    Environment::setMode(Environment::PRODUCTION);
//    Debug::enable(Debug::PRODUCTION, '%logDir%/php_error.log');
//}
Environment::setVariable('logDir',APP_DIR .'/../log/');
Debug::enable(Debug::DETECT, '%logDir%/php_error.log');

// 2b) load configuration from config.ini file
$basePath = Environment::getHttpRequest()->getUri()->basePath;


if ( !defined('KLIENT') ) {
    echo "<h1>Chyba aplikace. Nebyl zjisten klient!</h1>";
    exit;
}

Environment::setVariable('klientUri', $basePath );
Environment::setVariable('baseUri', BASE_URI);
Environment::setVariable('baseApp', BASE_APP);


$unique_info = @file_get_contents(CLIENT_DIR .'/configs/install');
if ( $unique_info === FALSE ) {
    define('APPLICATION_INSTALL',1);
} else {
    Environment::setVariable('unique_info', $unique_info);
}

Environment::loadConfig(CLIENT_DIR .'/configs/system.ini');
$user_config = Config::fromFile(CLIENT_DIR .'/configs/klient.ini');
$epodatelna_config = Config::fromFile(CLIENT_DIR .'/configs/epodatelna.ini');
Environment::setVariable('user_config', $user_config);
Environment::setVariable('epodatelna_config', $epodatelna_config);

//Environment::setMode(Environment::DEVELOPMENT);
//Environment::setMode(Environment::PRODUCTION);

// app info
$app_info = @file_get_contents(APP_DIR .'/configs/version');
Environment::setVariable('app_info', $app_info);

// 2c) check if directory /app/temp is writable
Environment::setVariable('tempDir',CLIENT_DIR .'/temp/');
if (@file_put_contents(Environment::expand('%tempDir%/_check'), '') === FALSE) {
	throw new Exception("Make directory '" . Environment::getVariable('tempDir') . "' writable!");
}

// 2d) enable RobotLoader - this allows load all classes automatically
$loader = new RobotLoader();
$loader->addDirectory(APP_DIR);
$loader->addDirectory(LIBS_DIR);
$loader->register();

// 2e) setup sessions
$session = Environment::getSession();
$session->setSavePath(CLIENT_DIR . '/sessions/');

// Step 3: Configure application
$application = Environment::getApplication();
$application->errorPresenter = 'Error';
$application->catchExceptions = Environment::isProduction();

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

Mail::$defaultMailer = 'ESSMailer'; // nebo new MyMailer

// 3b) Load database
try {
    dibi::connect(Environment::getConfig('database'));
    dibi::addSubst('PREFIX', Environment::getConfig('database')->prefix);
    if ( !Environment::isProduction() ) {
        dibi::getProfiler()->setFile(APP_DIR .'/../log/mysql_'. KLIENT .'.log');
    }
    define('DB_PREFIX', Environment::getConfig('database')->prefix);
} catch (DibiDriverException $e) {
    if ( !Environment::isProduction() ) {
        define('DB_ERROR', $e->getMessage());
    } else {
        define('DB_ERROR', 1);
    }
}

// Step 4: Setup application router
$router = $application->getRouter();

// mod_rewrite detection
if (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) {
	$router[] = new Route('index.php', array(
                'module'    => 'Spisovka',
                'presenter' => 'Default',
                'action' => 'default',
	), Route::ONE_WAY);

	// Uzivatel
        $router[] = new Route('uzivatel/<action>/<id>', array(
                'module'    => 'Spisovka',
                'presenter' => 'Uzivatel',
		'action' => 'default',
		'id' => NULL,
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
        // Install module
        $router[] = new Route('install/<action>/<id>/<params>', array(
                'module'    => 'Install',
                'presenter' => 'Default',
                'action'    => 'default',
                'id'        => null,
                'params'    => null
        ));

        $router[] = new Route('<presenter>/<action novy|nova|upravit|seznam|vyber|pridat|odeslat|odpoved>', array(
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
	$router[] = new SimpleRouter('Spisovka:default');
}

// Step 5: Run the application!
$application->run();