<?php //netteCache[01]000236a:2:{s:4:"time";s:21:"0.40938200 1291371022";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:81:"C:\xampp\htdocs\spisovka1\trunk/app/templates/InstallModule/Default/spravce.phtml";i:2;i:1291364704;}}}?><?php
// file …/templates/InstallModule/Default/spravce.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '3ea5ba2400'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbbdc02d2a6e1_title')) { function _cbbdc02d2a6e1_title() { extract(func_get_arg(0))
?>Instalace - nastavení správce systému<?php
}}


//
// block menu
//
if (!function_exists($_cb->blocks['menu'][] = '_cbb73403fed38_menu')) { function _cbb73403fed38_menu() { extract(func_get_arg(0))
?>
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:uvod")) ?>">Úvod</a> >
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:kontrola")) ?>">Kontrola</a> >
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:databaze")) ?>">Nahrání databáze</a> >
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:urad")) ?>">Nastavení klienta</a> >
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:evidence")) ?>">Nastavení evidence</a> >
<strong>Nastavení správce</strong> >
<span>Konec</span>
<?php
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbb1021ebd75b_content')) { function _cbb1021ebd75b_content() { extract(func_get_arg(0))
?>
<h1>Instalace - Vytvoření správce systému</h1>

<p>
V posledním kroku vytvořte správce (administrátor), který bude dohlížet na chod spisové služby.
Dobře zvažte, koho správou spisové služby pověříte. Tato osoba bude mít na starost přidávání a správu
uživatelů, přidání a správu organizační jednotky, možnost měnit oprávnění, možnost nastavení e-podatelny,
informace o klientovi, případně změnu čísla jednacího.
</p>

    <div class="detail_blok">
<?php $control->getWidget("spravceForm")->render() ?>
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
