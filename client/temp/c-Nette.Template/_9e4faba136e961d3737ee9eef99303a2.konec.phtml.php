<?php //netteCache[01]000234a:2:{s:4:"time";s:21:"0.06531700 1291371100";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:79:"C:\xampp\htdocs\spisovka1\trunk/app/templates/InstallModule/Default/konec.phtml";i:2;i:1291364703;}}}?><?php
// file …/templates/InstallModule/Default/konec.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'ab7a54568d'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb9e4384ef05_title')) { function _cbb9e4384ef05_title() { extract(func_get_arg(0))
?>Instalace - Konec<?php
}}


//
// block menu
//
if (!function_exists($_cb->blocks['menu'][] = '_cbba1b9e41bd7_menu')) { function _cbba1b9e41bd7_menu() { extract(func_get_arg(0))
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
if (!function_exists($_cb->blocks['content'][] = '_cbb9fc03a928a_content')) { function _cbb9fc03a928a_content() { extract(func_get_arg(0))
?>
<h1>Instalace - Dokončení</h1>

<div id="kontrola">
<?php if ($dokonceno): ?>
    <div class="passed result">
        <h2>Blahopřeji! Instalace spisové služby byla úspěšně dokončena.</h2>
        <p>Nyní můžete začít používat aplikaci.</p>
        <p>
            Další tipy po instalaci:
            <ul>
                <li>Vytvořte si organizační jednotky, které ve Vašém úřadu existují a budou využívat spisovou službu.</li>
                <li>Vytvořte si zaměstnance, kteří budou využívat spisovou službu.</li>
                <li>Všem nebo vybraným zaměstnancům vytvořte uživatelské účty a přiřaďte jim odpovídající role.</li>
                <li>Budete-li používat e-podatelnu, nastavte potřebné hodnoty pro přístup k datové schránce nebo pro příjem emailových zpráv.</li>
            </ul>
            
        </p>
        <p><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Default:default")) ?>">Přejít k aplikaci</a></p>
    </div>
<?php else: ?>
    <div class="failed result">
        <h2>Proces instalace spisové služby nebyl úspěšně dokončen!</h2>
        <p>Byly zjištěny tyto nesrovnalosti:
            <ul>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($errors) as $e): ?>
                <li><?php echo TemplateHelpers::escapeHtml($e) ?></li>
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
            </ul>
        </p>
    </div>
<?php endif ?>
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
