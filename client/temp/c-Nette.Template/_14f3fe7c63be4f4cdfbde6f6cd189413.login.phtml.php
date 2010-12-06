<?php //netteCache[01]000236a:2:{s:4:"time";s:21:"0.46978300 1291371120";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:81:"C:\xampp\htdocs\spisovka1\trunk/app/templates/SpisovkaModule/Uzivatel/login.phtml";i:2;i:1291364700;}}}?><?php
// file …/templates/SpisovkaModule/Uzivatel/login.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '97689c09db'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb3886ae374c_title')) { function _cbb3886ae374c_title() { extract(func_get_arg(0))
?>Přihlášení do systému<?php
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbb294949a58d_content')) { function _cbb294949a58d_content() { extract(func_get_arg(0))
?>
    <h2>Přihlášení do systému</h2>
        <div id="form_login">
            <?php $form->render('begin') ?>
            <label>Uživatelské jméno:</label> <?php echo TemplateHelpers::escapeHtml($form['username']->control) ?>

            <br />
            <label>Heslo:</label> <?php echo TemplateHelpers::escapeHtml($form['password']->control) ?>

            <br />
            <label>&nbsp;</label> <?php echo TemplateHelpers::escapeHtml($form['login']->control) ?>

            <?php $form->render('end') ?>
        </div>

<?php
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
