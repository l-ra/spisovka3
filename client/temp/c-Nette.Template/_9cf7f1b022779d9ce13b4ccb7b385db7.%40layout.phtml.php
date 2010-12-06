<?php //netteCache[01]000212a:2:{s:4:"time";s:21:"0.95297400 1291382987";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:57:"C:\xampp\htdocs\spisovka1\trunk/app/../help/@layout.phtml";i:2;i:1291364721;}}}?><?php
// file …/../help/@layout.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'adee5e0b82'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb5c8015e8a8_title')) { function _cbb5c8015e8a8_title() { extract(func_get_arg(0))
;
}}

//
// end of blocks
//

if ($_cb->extends) { ob_start(); }

if (SnippetHelper::$outputAllowed) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>Spisová služba - Nápověda - <?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?></title>
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/site.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/help_site.css" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
</head>
<body>
<div id="layout_top">
    <div id="top">
        <h1>Spisová služba - Nápověda</h1>
        <br />
    </div>
</div>
<div id="layout">
    <div id="menu">
        <a href="<?php echo TemplateHelpers::escapeHtml($klientUri) ;echo TemplateHelpers::escapeHtml($setUri) ?>">&lt;&lt; zpět na stránku</a>&nbsp;&nbsp;
        <a href="<?php echo TemplateHelpers::escapeHtml($klientUri) ?>napoveda">Hlavní stránka nápovědy</a>
    </div>
    <div id="help_content">
<?php if (file_exists($setFile)): LatteMacros::includeTemplate($setFile, $template->getParams(), $_cb->templates['adee5e0b82'])->render() ;else: ?>
    <h2>Bez nápovědy</h2>
    <p>
        Pro uvedenou stránku "<a href="<?php echo TemplateHelpers::escapeHtml($klientUri) ;echo TemplateHelpers::escapeHtml($setUri) ?>"><strong>http://<?php echo TemplateHelpers::escapeHtml($klientUri) ;echo TemplateHelpers::escapeHtml($setUri) ?></strong></a>" není k dispozici nápověda. Případně nápověda není potřeba.
    </p>
<?php endif ?>
    </div>
</div>
<div id="layout_bottom">
    <div id="bottom">
        <strong><?php echo TemplateHelpers::escapeHtml($AppInfo[2]) ?></strong><br/>
        Na toto dílo se vztahuje <a href="http://ec.europa.eu/idabc/eupl">licence EUPL V.1.1</a>
    </div>
</div>


</body>
</html><?php
}

if ($_cb->extends) { ob_end_clean(); LatteMacros::includeTemplate($_cb->extends, get_defined_vars(), $template)->render(); }
