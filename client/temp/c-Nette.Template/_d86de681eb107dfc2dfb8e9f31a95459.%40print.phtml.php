<?php //netteCache[01]000213a:2:{s:4:"time";s:21:"0.26764300 1291371174";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:58:"C:\xampp\htdocs\spisovka1\trunk/app/templates/@print.phtml";i:2;i:1291364709;}}}?><?php
// file …/templates/@print.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '21b4e59c92'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbbd1989aae61_title')) { function _cbbd1989aae61_title() { extract(func_get_arg(0))
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
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <title>Spisová služba - <?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?></title>
    <style type="text/css" media="screen, print">
        <?php echo file_get_contents(APP_DIR .'/../public/css/print_site.css') ?>
    </style>
</head>
<body<?php if ($view=='tisk'): ?> onload="window.print();"<?php endif ?>>
<?php LatteMacros::callBlock($_cb->blocks, 'content', get_defined_vars()) ?>
</body>
</html><?php
}

if ($_cb->extends) { ob_end_clean(); LatteMacros::includeTemplate($_cb->extends, get_defined_vars(), $template)->render(); }
