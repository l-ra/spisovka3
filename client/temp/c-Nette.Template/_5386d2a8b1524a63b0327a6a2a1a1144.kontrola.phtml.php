<?php //netteCache[01]000237a:2:{s:4:"time";s:21:"0.16723400 1291370369";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:82:"C:\xampp\htdocs\spisovka1\trunk/app/templates/InstallModule/Default/kontrola.phtml";i:2;i:1291364704;}}}?><?php
// file …/templates/InstallModule/Default/kontrola.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '51306a6855'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbbe9ea4b14ee_title')) { function _cbbe9ea4b14ee_title() { extract(func_get_arg(0))
?>Instalace - kontrola<?php
}}


//
// block menu
//
if (!function_exists($_cb->blocks['menu'][] = '_cbbf2b0c25a17_menu')) { function _cbbf2b0c25a17_menu() { extract(func_get_arg(0))
?>
<a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:uvod")) ?>">Úvod</a> >
<strong>Kontrola</strong> >
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
if (!function_exists($_cb->blocks['content'][] = '_cbb9ea8704a17_content')) { function _cbb9ea8704a17_content() { extract(func_get_arg(0))
?>
<h1>Instalace - Kontrola serveru na minimální požadavek aplikace</h1>

<p>
V tomto kroku se zkontroluji minimální požadavky na provoz spisové služby. Jedná se především o kontrolu
provozuschopnosti jádra aplikace, která je založena na Nette Framework. Dále kontrola na dostupnost
potřebných komponent pro použití určitých funkcí, jako třeba obsluha datových schránek,
příjem a odesíláni emailů, ověření pravosti apod. V neposlední řadě také kontrola na zapisovatelnost
pro některé konfigurační soubory a zápisu pro dočasné soubory.</p>

<div id="kontrola">
<?php if ($errors): ?>
    <div class="failed result">
        <h2>Omlováme se, ale konfigurace serveru nesplňuje požadavky pro použití aplikace!</h2>
        <p>Není možné pokračovat v instalaci.</p>
        <p>Podívejte se do detailu a u vyznačených bodech sjednejte nápravu. Poté <a href="?">znovu ověřte</a>.</p>
    </div>
<?php else: ?>
    <div class="passed result">
        <h2>Blahopřeji! Konfigurace serveru splňuje minimální požadavky pro použití aplikace.</h2>
        <p>Můžete pokračovat v instalaci.</p>
	<?php if ($warnings):?><p>Byly zjištěny nesrovnalosti, které mohou ovlivnit chod aplikace. Podívejte se do detailu a u vyznačených bodech se rozhodněte,
            zda je daná položka nutná pro běh aplikace nebo ne.</p><?php endif ?>

        <p><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Install:Default:databaze")) ?>">Pokračovat v instalaci</a></p>

    </div>
<?php endif ?>

    <h2>Detail kontroly:</h2>

    <table>
    <?php foreach ($requirements_ess as $id => $requirement):?>
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

    <?php if (isset($requirement->script)): ?>
    <script type="text/javascript"><?php echo $requirement->script ?></script>
    <?php endif ?>

    <?php endforeach ?>
    </table>

    <br />
    <h2>Detaily jádra aplikace (Nette Framework)</h2>

    <table>
    <?php foreach ($requirements as $id => $requirement):?>
    <?php $class = isset($requirement->passed) ? ($requirement->passed ? 'passed' : ($requirement->required ? 'failed' : 'warning')) : 'info';

            if( $class == "passed" || $class == "info" ) continue ?>
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

    <?php if (isset($requirement->script)): ?>
    <script type="text/javascript"><?php echo $requirement->script ?></script>
    <?php endif ?>

    <?php endforeach ?>
    </table>

</div>
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
