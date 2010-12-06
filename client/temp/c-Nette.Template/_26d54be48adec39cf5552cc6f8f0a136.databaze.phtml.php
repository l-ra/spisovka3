<?php //netteCache[01]000237a:2:{s:4:"time";s:21:"0.22589300 1291370614";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:82:"C:\xampp\htdocs\spisovka1\trunk/app/templates/InstallModule/Default/databaze.phtml";i:2;i:1291364704;}}}?><?php
// file …/templates/InstallModule/Default/databaze.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '57154ce144'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb97b55b6fb1_title')) { function _cbb97b55b6fb1_title() { extract(func_get_arg(0))
?>Instalace - databáze<?php
}}


//
// block menu
//
if (!function_exists($_cb->blocks['menu'][] = '_cbb7d5c297b3f_menu')) { function _cbb7d5c297b3f_menu() { extract(func_get_arg(0))
?>
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:uvod")) ?>">Úvod</a> >
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:kontrola")) ?>">Kontrola</a> >
<strong>Nahrání databáze</strong> >
<span>Nastavení klienta</span> >
<span>Nastavení evidence</span> >
<span>Nastavení správce</span> >
<span>Konec</span>
<?php
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbbee998dc12d_content')) { function _cbbee998dc12d_content() { extract(func_get_arg(0))
?>
<h1>Instalace - Databáze</h1>

<p>V tomto kroku se nejdříve zkontroluje, zda je databáze dostupná a zda již dané tabulky v databázi neexistuji. Pokud je vše v pořádku, provedete nahrání potřebných tabulek a dat do databáze.</p>

<div id="kontrola">
<?php if (isset($db_install)): ?>
    <?php if ($errors): ?>
    <div class="failed result">
        <h2>Při nahrání tabulek do databáze nastala chyba!</h2>
        <p>V detailu vyhledejte označenou položku a zjistěte příčinu chyby.</p>
        <p>Máte tyto možnosti:
            <ul>
                <li>Označené chyby ručně opravit a <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:urad")) ?>">pokračovat v instalaci</a>. Pouze na vlastní riziko!</li>
                <li>Odstranit všechny tabulky určené aplikaci a <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:databaze", array('install'=>'1'))) ?>">provést nahrání znovu</a></li>
                <li>Kontaktovat pověřenou osobu nebo technickou podporu aplikace.</li>
            </ul>
        </p>
        <p>V této fázi není možné pokračovat v instalaci. Pro pokračování instalace je potřeba provést nápravu.</p>
    </div>
    <?php else: ?>
    <div class="passed result">
        <h2>Blahopřeji! Nahrání tabulek a dat proběhlo v pořádku.</h2>
        <p>Můžete pokračovat v instalaci.</p>
        <p><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:urad")) ?>">Pokračovat v instalaci</a></p>
    </div>
    <?php endif ;else: ?>
    <?php if (isset($provedeno)): ?>
    <div class="passed result">
        <h2>Tabulky a data jsou již nahrané.</h2>
        <p>Nahrání tabulek a dat proběhlo v pořádku. Můžete pokračovat v instalaci.</p>
        <p><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:urad")) ?>">Pokračovat v instalaci</a></p>
    </div>
    <?php elseif ($errors): ?>
    <div class="failed result">
        <h2>Nelze nahrát tabulky a data do databáze!</h2>
        <p>V uvedené databázi již existují tabulky určené pro tuto aplikaci. </p>
        <p>V případě, že chcete ponechat existující tabulky, je potřeba v nastavení změnit prefix tabulek nebo jméno databáze. Jinak bude potřeba uvedené tabulky smazat z databáze.</p>
        <p>V této fázi není možné pokračovat v instalaci. Pro pokračování instalace je potřeba provést nápravu. Po dokončení úprav proveďte <a href="?">novou kontrolu</a>. </p>
    </div>
    <?php else: ?>
    <div class="passed result">
        <h2>Před kontrola databáze proběhla v pořádku.</h2>
        <p>Můžete pokračovat v nahrání tabulek do databáze.</p>
        <p><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:databaze", array('install'=>'1'))) ?>">Nahrát tabulky a data</a></p>

    </div>
    <?php endif ;endif ?>



    <h2>Detail kontroly:</h2>

    <table>
    <?php foreach ($database as $id => $requirement):?>
    <?php $class = isset($requirement->passed) ? ($requirement->passed ? 'passed' : ($requirement->required ? 'failed' : 'warning')) : 'info' ?>
    <tr id="res<?php echo $id ?>" class="<?php echo $class ?>">
    	<td class="th"><?php echo htmlSpecialChars($requirement->title) ?></td>

        <?php if (empty($requirement->passed) && isset($requirement->errorMessage)): ?>
	<td><?php echo htmlSpecialChars($requirement->errorMessage) ?></td>
	<?php elseif (isset($requirement->message)): ?>
	<td><?php echo htmlSpecialChars($requirement->message) ?></td>
	<?php elseif (isset($requirement->passed)): ?>
	<td><?php echo $requirement->passed ? 'Enabled' : 'Disabled' ?></td>
	<?php else: ?>
	<td></td>
	<?php endif ?>
    </tr>

    <?php if (isset($requirement->passed) && !$requirement->passed): ?>
    <tr class="<?php echo $class ?> description">
        <td colspan="2"><?php echo $requirement->description ?></td>
    </tr>
    <?php endif ?>

    <?php endforeach ?>
    </table>
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
