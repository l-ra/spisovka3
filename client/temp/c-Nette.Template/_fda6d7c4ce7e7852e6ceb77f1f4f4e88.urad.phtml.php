<?php //netteCache[01]000233a:2:{s:4:"time";s:21:"0.24350500 1291370650";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:78:"C:\xampp\htdocs\spisovka1\trunk/app/templates/InstallModule/Default/urad.phtml";i:2;i:1291364704;}}}?><?php
// file …/templates/InstallModule/Default/urad.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'b4549948a5'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbbfe8d953a8e_title')) { function _cbbfe8d953a8e_title() { extract(func_get_arg(0))
?>Instalace - nastavení informace o úřadu<?php
}}


//
// block menu
//
if (!function_exists($_cb->blocks['menu'][] = '_cbb6584e64fde_menu')) { function _cbb6584e64fde_menu() { extract(func_get_arg(0))
?>
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:uvod")) ?>">Úvod</a> >
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:kontrola")) ?>">Kontrola</a> >
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:databaze")) ?>">Nahrání databáze</a> >
<strong>Nastavení klienta</strong> >
<span>Nastavení evidence</span> >
<span>Nastavení správce</span> >
<span>Konec</span>
<?php
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbbbfca2670c8_content')) { function _cbbbfca2670c8_content() { extract(func_get_arg(0))
?>
<h1>Instalace - Nastavení informace o úřadu</h1>

<p>
Zde vyplňujete informace o sobě. Povinné jsou pouze položky název a zkratka.
Název slouží jako identifikace klienta. Zobrazuje se v záhlaví pod nadpisem Spisová služba a jako
součást záhlaví při tisku sestavy. Zkratka se uvádí jakou součást čísla jednacího.
Ostatní položky vyplňte dle vlastního uvážení.
</p>

    <div class="detail_blok">
<?php $control->getWidget("nastaveniUraduForm")->render() ?>
    </div><?php
}}

//
// end of blocks
//

if ($_cb->extends) { ob_start(); }

if (SnippetHelper::$outputAllowed) {
if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?>

<?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['menu']), get_defined_vars()); }  if (!$_cb->extends) { call_user_func(reset($_cb->blocks['content']), get_defined_vars()); }  
}

if ($_cb->extends) { ob_end_clean(); LatteMacros::includeTemplate($_cb->extends, get_defined_vars(), $template)->render(); }
