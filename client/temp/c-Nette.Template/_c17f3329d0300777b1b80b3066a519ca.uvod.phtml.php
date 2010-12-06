<?php //netteCache[01]000233a:2:{s:4:"time";s:21:"0.30162700 1291369641";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:78:"C:\xampp\htdocs\spisovka1\trunk/app/templates/InstallModule/Default/uvod.phtml";i:2;i:1291364703;}}}?><?php
// file …/templates/InstallModule/Default/uvod.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '09a4dd5759'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb23741593d5_title')) { function _cbb23741593d5_title() { extract(func_get_arg(0))
?>Instalace - úvod<?php
}}


//
// block menu
//
if (!function_exists($_cb->blocks['menu'][] = '_cbb9acdaccdc6_menu')) { function _cbb9acdaccdc6_menu() { extract(func_get_arg(0))
?>

<strong>Úvod</strong> >
<span>Kontrola</span> >
<span>Nahrání databáze</span> >
<span>Nastavení klienta</span> >
<span>Nastavení evidence</span> >
<span>Nastavení správce</span> >
<span>Konec</span>


<?php
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbb3f7695c1d4_content')) { function _cbb3f7695c1d4_content() { extract(func_get_arg(0))
?>
<h1>Vítejte v instalaci spisové služby.</h1>

<p>
Instalace bude probíhat v několika krocích. V prvním kroku se zkontroluji minimální požadavky serveru
na provoz spisové služby. V druhém kroku dojde k nahrání potřebných tabulek a dat pro databázi.
Ve třetím kroku nastavíte informace o sobě. Ve čtvrtém kroku nastavíte možnosti evidence,
jako typ evidence, masku čísla jednacího. V posledním kroku si vytvoříte správce, který bude mít
nad spisovou službu dohled.
</p>
<p>
Před započetím instalace si zkontrolujte, zda máte správně nastavený systémový konfigurační soubor
(nastavení databáze, úložiště, apod) a potřebné informace pro vyplnění údajů o sobě, možnostech evidence a
pověřenou osobu pro správu spisové služby.</p>


<p><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:kontrola")) ?>">Pokračovat v instalaci</a></p>

<?php
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
