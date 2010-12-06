<?php //netteCache[01]000237a:2:{s:4:"time";s:21:"0.11869200 1291371168";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:82:"C:\xampp\htdocs\spisovka1\trunk/app/templates/SpisovkaModule/Sestavy/default.phtml";i:2;i:1291364702;}}}?><?php
// file …/templates/SpisovkaModule/Sestavy/default.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'ae04490ca9'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb7bc30da08e_title')) { function _cbb7bc30da08e_title() { extract(func_get_arg(0))
?>Sestavy<?php
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbbbde37a5f97_content')) { function _cbbbde37a5f97_content() { extract(func_get_arg(0))
?>

    <h2>Pevné sestavy</h2>
    <div class="detail_blok">
<?php if (count($sestavy_pevne)>0): ?>
    <table class="seznam">
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($sestavy_pevne) as $sestava): ?>
        <tr>
            <td class="icon">
                <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/book.png" alt="book.png" width="32" height="32" />
            </td>
            <td class="meta">
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:detail", array('id' => $sestava->id))) ?>" onclick="return filtrSestavy(this);"><?php echo TemplateHelpers::escapeHtml($sestava->nazev) ?></a>&nbsp;
                <div class="info">
                    <?php echo TemplateHelpers::escapeHtml($sestava->popis) ?>

                </div>
            </td>
            <td class="icon-view">
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:detail", array('id' => $sestava->id))) ?>" onclick="return filtrSestavy(this);" title="Zobrazit sestavu">
                    <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/list.png" alt="list.png" width="32" height="32" />
                </a>
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:tisk", array('id' => $sestava->id))) ?>" onclick="return filtrSestavy(this);" title="Zobrazit a vytisknout sestavu">
                    <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/printer.png" alt="printer.png" width="32" height="32" />
                </a>
<?php if (0): ?>
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:pdf", array('id' => $sestava->id))) ?>" onclick="return filtrSestavy(this);" title="Vytisknout sestavu do PDF" >
                    <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/mimetypes/application-pdf.png" alt="application-pdf.png" width="32" height="32" />
                </a>
<?php endif ?>
            </td>
        </tr>
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
    </table>
<?php else: ?>
    <div class="prazdno">Nebyly zjištěny žádné pevné sestavy.</div>
<?php endif ?>
    </div>

    <h2>Volitelné sestavy</h2>
    
    <div class="blok_menu">
        <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:nova")) ?>">Nová sestava</a>
    </div>

    <div class="detail_blok">
<?php if (count($sestavy_volitelne)>0): ?>
    <table class="seznam">
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($sestavy_volitelne) as $sestava): ?>
        <?php $filtr = ($sestava->filtr == 1)?'onclick="return filtrSestavy(this);"':"" ?>
        <tr>
            <td class="icon">
                <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/book.png" alt="book.png" width="32" height="32" />
            </td>
            <td class="meta">
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:detail", array('id' => $sestava->id))) ?>" <?php echo $filtr ?>><?php echo TemplateHelpers::escapeHtml($sestava->nazev) ?></a>&nbsp;
                <div class="info">
                    <?php echo TemplateHelpers::escapeHtml($sestava->popis) ?>

                </div>
            </td>
            <td class="icon-view">
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:detail", array('id' => $sestava->id))) ?>" <?php echo $filtr ?> title="Zobrazit sestavu">
                    <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/list.png" alt="list.png" width="32" height="32" />
                </a>
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:tisk", array('id' => $sestava->id))) ?>" <?php echo $filtr ?> title="Zobrazit a vytisknout sestavu">
                    <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/printer.png" alt="printer.png" width="32" height="32" />
                </a>
<?php if (0): ?>
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:pdf", array('id' => $sestava->id))) ?>" <?php echo $filtr ?> title="Vytisknout sestavu do PDF">
                    <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/mimetypes/application-pdf.png" alt="application-pdf.png" width="32" height="32" />
                </a><?php endif ?>

                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:upravit", array('id' => $sestava->id))) ?>" title="Upravit vlastnosti sestavy">
                    <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/edit.png" alt="edit.png" width="32" height="32" />
                </a>
            </td>
        </tr>
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
    </table>
<?php else: ?>
    <div class="prazdno">Nebyly zjištěny žádné volitelné sestavy.</div>
<?php endif ?>
    </div>

    <div id="dialog"></div><?php
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
