<?php //netteCache[01]000234a:2:{s:4:"time";s:21:"0.40160400 1291371191";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:79:"C:\xampp\htdocs\spisovka1\trunk/app/templates/AdminModule/Default/default.phtml";i:2;i:1291364707;}}}?><?php
// file …/templates/AdminModule/Default/default.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '7fcd56544d'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb4837e644da_title')) { function _cbb4837e644da_title() { extract(func_get_arg(0))
?>Administrace<?php
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbb2fddf02d18_content')) { function _cbb2fddf02d18_content() { extract(func_get_arg(0))
?>
<h2>Vítejte v administraci</h2><?php
}}

//
// end of blocks
//

if ($_cb->extends) { ob_start(); }

if (SnippetHelper::$outputAllowed) {
if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?>

<?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['content']), get_defined_vars()); }  
}

if ($_cb->extends) { ob_end_clean(); LatteMacros::includeTemplate($_cb->extends, get_defined_vars(), $template)->render(); }
