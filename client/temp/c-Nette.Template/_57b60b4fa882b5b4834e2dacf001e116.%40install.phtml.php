<?php //netteCache[01]000215a:2:{s:4:"time";s:21:"0.37407400 1291369641";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:60:"C:\xampp\htdocs\spisovka1\trunk/app/templates/@install.phtml";i:2;i:1291364710;}}}?><?php
// file …/templates/@install.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '943f3a5eb7'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbbc577e3d0aa_title')) { function _cbbc577e3d0aa_title() { extract(func_get_arg(0))
;
}}


//
// block javascript
//
if (!function_exists($_cb->blocks['javascript'][] = '_cbb01786bc884_javascript')) { function _cbb01786bc884_javascript() { extract(func_get_arg(0))
;
}}


//
// block menu
//
if (!function_exists($_cb->blocks['menu'][] = '_cbba81297bd32_menu')) { function _cbba81297bd32_menu() { extract(func_get_arg(0))
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
    <title>Spisová služba - <?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?></title>
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/site.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/install_site.css" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <script type="text/javascript">
        var baseUri = '<?php echo $klientUri ?>';
    </script>
    <script src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>/js/denied/checker.js" type="text/javascript"></script>
    <?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['javascript']), get_defined_vars()); } ?>

</head>
<body>
<div id="layout_top">
    <div id="top">
        <h1><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Default:")) ?>">Spisová služba</a> <span id="top_urad">Instalace</span></h1>
        <div id="top_menu">
            &nbsp;
        </div>
        <div id="top_jmeno">
            &nbsp;
        </div>
    </div>
</div>
<div id="layout">
    <div id="menu">
    &nbsp;<?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['menu']), get_defined_vars()); } ?>

    </div><?php if (count($flashes)>0): ?>
    <div id="flash">

        <?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($flashes) as $flash): ?><div class="flash_message flash_<?php echo TemplateHelpers::escapeHtml($flash->type) ?>"><?php echo TemplateHelpers::escapeHtml($flash->message) ?></div><?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>

    </div>
<?php endif ?>
    <div id="content">
<?php LatteMacros::callBlock($_cb->blocks, 'content', get_defined_vars()) ?>
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
