<?php

//echo "<pre>"; print_r($_SERVER); echo "</pre>"; exit;

function url() {

    if ( strpos($_SERVER['SERVER_PROTOCOL'],"https") !== false || isset($_SERVER['HTTPS'])) {
        $server_http = "https://";
    } else {
        $server_http = "http://";
    }

    if ($_SERVER['SERVER_PORT'] == "443" && $server_http == "https://" ) {
        $server_port = "";
    } else if ($_SERVER['SERVER_PORT'] != "80"  ) {
        $server_port = ":". $_SERVER['SERVER_PORT'];
    } else {
        $server_port = "";
    }

    return $server_http . $_SERVER['SERVER_NAME'] . $server_port . $_SERVER['SCRIPT_NAME'];
}

$URL_LINK = url();
//$ROOT_DIR = str_replace("/public/auth/index.php","", $_SERVER['SCRIPT_FILENAME']);
//require $ROOT_DIR . '/libs/Nette/loader.php';

if ( strpos($URL_LINK,"public/auth") !== false ) {
    // index.php in root
    $URL_LINK = str_replace("/public/auth/index.php","",$URL_LINK);
} else {
    // index.php in public
    $URL_LINK = str_replace("/auth/index.php","",$URL_LINK);
}


//header("Location ../", 302);
//exit;
session_start();
if ( isset($_SERVER['PHP_AUTH_USER']) ) $_SESSION['s3_auth_username'] = $_SERVER['PHP_AUTH_USER'];
if ( isset($_SERVER['PHP_AUTH_PW']) )   $_SESSION['s3_auth_password'] = $_SERVER['PHP_AUTH_PW'];
if ( isset($_SERVER['REMOTE_USER']) )   $_SESSION['s3_auth_remoteuser'] = $_SERVER['REMOTE_USER'];
$is_logged = ( isset($_SERVER['PHP_AUTH_USER']) || isset($_SERVER['REMOTE_USER']) )?true:false;

// session fix
$aSESSION = array(
    'time' => time(),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    's3_auth_username' => $_SERVER['PHP_AUTH_USER'],
    's3_auth_password' => $_SERVER['PHP_AUTH_PW'],
    's3_auth_remoteuser' => $_SERVER['REMOTE_USER'],
    'is_logged' => $is_logged
);
$uuid = uniqid();
file_put_contents("../../log/asession_".$uuid, serialize($aSESSION));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>Spisová služba</title>
    <style type="text/css">
    
body {
    background: #f0f0f0 url() repeat-x;
    margin: 0px;
    padding: 0px;
    text-align: left;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 9pt;
}

#layout_top {
    background: #8FBAD9 url() repeat-x;
    margin: 0px;
    padding-top: 20px;
}

#top {
    width: 980px;
    margin: 0 auto 0 auto;
    padding: 0px;
    /*height: 120px;*/
    position: relative;
    background: #3E94D1 url() repeat-x;
}

#top h1 {
    color: #03406A;
    font-size: 25px;
    padding: 20px 20px 5px 20px;
    margin: 0px;
}

#layout {
    width: 980px;
    margin: 0 auto 0 auto;
    padding: 0px;
}

#menu {
    background: #03406A url() repeat-x;
    padding: 10px 10px;
    color: #DDDDDD;
    font-weight: bold;
}

#content {
    position: relative;
    background: #ffffff url() repeat-x;
    min-height: 500px;
    padding: 30px 50px;
    margin: 0px;
}

#layout_bottom {
    margin: 0px;
    padding: 0px;
}

#bottom {
    width: 970px;
    margin: 0 auto 0 auto;
    padding: 5px 5px 5px 5px;
    text-align: right;
    color: #03406A;
    font-size: 8pt;
    background: #3E94D1 url() repeat-x;
}

#bottom a {
    color: #03406A;
    text-decoration: none;
}

#bottom a:hover {
    color: #FFC273;
    text-decoration: underline;
}
        
    </style>
</head>
<body>
<div id="layout_top">
    <div id="top">
        <h1>Spisová služba</h1>
        &nbsp;
    </div>
</div>
<div id="layout">
    <div id="menu">
    &nbsp;
    </div>    
    <div id="content">

<?php if ( $is_logged ) { ?>        
        
        <h1>Byl jste úspěšně přihlášen.</h1>

        <p style="text-align:center; margin-top: 30px;">
           Pokračujte kliknutím na následující odkaz:
           <br />
           <a style="display:block; margin:10px; font-size: 12pt;" href="<?php echo $URL_LINK ."?_backlink=". $uuid; ?>">přejít na hlavní stránku aplikace</a>
        </p>        
        
<?php } else { ?>        
        
        <h1>Chyba při přihlášení!</h1>

        <p>
            Došlo k chybě při přihlašování. Buď nedošlo k autentizaci uživatele, nebo se nepodařilo získat přístupové údaje.
            <br />
            Zkuste to znovu nebo kontaktujte správce, který má na starost tuto spisovou službu.
            
        </p>
        
        <p style="text-align:center; margin-top: 30px;">
           <a style="display:block; margin:10px; font-size: 12pt;" href="<?php echo $URL_LINK; ?>">přejít na hlavní stránku aplikace</a>
        </p>        
        
        
<?php } ?>        
        
    </div>
</div>
<div id="layout_bottom">
    <div id="bottom">
        <strong>OSS Spisová služba</strong><br/>
        Na toto dílo se vztahuje <a href="http://ec.europa.eu/idabc/eupl">licence EUPL V.1.1</a>
    </div>
</div>


</body>
</html>