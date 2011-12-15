<?php

// absolute filesystem path to the web root
define('WWW_DIR', dirname(__FILE__) );
// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/../app');
// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/../libs');

// client identificator
define('KLIENT', 'default');
// absolute filesystem path to the client files
define('CLIENT_DIR', WWW_DIR . '/../client');

// Step 1: Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';

// Step 2: Configure environment
Environment::loadConfig(CLIENT_DIR .'/configs/system.ini');
$user_config = Config::fromFile(CLIENT_DIR .'/configs/klient.ini');
Environment::setVariable('user_config', $user_config);

// 2c) check if directory /app/temp is writable
Environment::setVariable('tempDir',CLIENT_DIR .'/temp');
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

// 3b) Load database
try {
    $db_config = (array) Environment::getConfig('database');
    $db_config['profiler'] = FALSE;
    dibi::connect($db_config);
    dibi::addSubst('PREFIX', Environment::getConfig('database')->prefix);
    define('DB_PREFIX', Environment::getConfig('database')->prefix);
    define('DB_CACHE', 0);

} catch (DibiDriverException $e) {
    if ( !Environment::isProduction() ) {
        define('DB_ERROR', $e->getMessage());
    } else {
        define('DB_ERROR', 1);
    }
}

/////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////

$SZnak = new  SpisovyZnak();
$seznam = $SZnak->fetchAll()->fetchAll();

echo "<pre>";

foreach ( $seznam as $znak ) {
    
    echo $znak->sekvence_string ."\n";
    
    $part = explode("#",$znak->sekvence_string);
    if ( count($part)>0 ) {
        foreach( $part as $pi=>$pa ) {
            $sekv = explode(".",$pa);
            if ( count($sekv)>0 ) {
                $end = $sekv[ count($sekv)-1 ];
                unset($sekv[ count($sekv)-1 ]);
                
                foreach( $sekv as $si=>$sa ) {
                    if ( is_numeric($sa) ) {
                        $sekv[$si] = sprintf("%04d",(int)$sa);
                    }
                }
            }
            $part[$pi] = implode(".", $sekv) .".". $end;
        }
    }
    $sekvence_string = implode("#",$part);
    $SZnak->update( 
               array("sekvence_string"=>$sekvence_string) , 
               array( array("id=%i",$znak->id) ) 
            );
    
    echo "<span style='color:red'>". $sekvence_string ."</span>\n";
    
    
}

//2.6.7.6
//109.165.180.186
//0002.109#0002.0006.165#0002.0006.0007.180#0002.0006.0007.0006.186


echo "<pre>";