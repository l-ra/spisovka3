<?php

// Step 1: Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';

// Step 2: Configure environment
// 2a) enable Nette\Debug for better exception and error visualisation

//if ($_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    Debug::enable(Debug::DEVELOPMENT, '%logDir%/php_error.log');
//} else {
//    Debug::enable(Debug::PRODUCTION, '%logDir%/php_error.log');
//}

// 2b) load configuration from config.ini file
$basePath = Environment::getHttpRequest()->getUri()->basePath;


if ( defined('KLIENT') ) {
    Environment::setVariable('klientUri', $basePath );
    Environment::setVariable('baseUri', '/spisovka3/public/');
    Environment::setVariable('baseApp', '/spisovka3/');
} else {
    Environment::setVariable('klientUri', $basePath );
    Environment::setVariable('baseUri', '/spisovka3/public/');
    Environment::setVariable('baseApp', '/spisovka3/');
    define('KLIENT', 'default');
}

Environment::loadConfig(APP_DIR .'/configs/'. KLIENT .'_system.ini');
$user_config = Config::fromFile(APP_DIR .'/configs/'. KLIENT .'.ini');
$epodatelna_config = Config::fromFile(APP_DIR .'/configs/'. KLIENT .'_epodatelna.ini');
Environment::setVariable('user_config', $user_config);
Environment::setVariable('epodatelna_config', $epodatelna_config);

$unique_info = @file_get_contents(APP_DIR .'/configs/'. KLIENT .'_install');
Environment::setVariable('unique_info', $unique_info);

//Environment::setMode(Environment::DEVELOPMENT);
//Environment::setMode(Environment::PRODUCTION);

// app info
$app_info = @file_get_contents(APP_DIR .'/configs/version');
Environment::setVariable('app_info', $app_info);

// 2c) check if directory /app/temp is writable
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
$session->setSavePath(APP_DIR . '/sessions/');

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
dibi::connect(Environment::getConfig('database'));
dibi::addSubst('PREFIX', Environment::getConfig('database')->prefix);
if ( !Environment::isProduction() ) {
    dibi::getProfiler()->setFile(APP_DIR .'/log/mysql.log');
}
define('DB_PREFIX', Environment::getConfig('database')->prefix);

$autoLogin = Environment::getConfig('autoLogin');
if ( $autoLogin['enable'] == 1 ) {
    $user = Environment::getUser();
    $user->authenticate($autoLogin['username'], $autoLogin['password']);
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
                'module'    => 'Spisovka',
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
