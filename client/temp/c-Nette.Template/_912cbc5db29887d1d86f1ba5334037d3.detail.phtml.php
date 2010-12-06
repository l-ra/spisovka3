<?php //netteCache[01]000236a:2:{s:4:"time";s:21:"0.14407500 1291371174";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:81:"C:\xampp\htdocs\spisovka1\trunk/app/templates/SpisovkaModule/Sestavy/detail.phtml";i:2;i:1291364702;}}}?><?php
// file …/templates/SpisovkaModule/Sestavy/detail.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '16f11b21ee'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbbee5550b055_title')) { function _cbbee5550b055_title() { extract(func_get_arg(0))
;echo TemplateHelpers::escapeHtml($Sestava->nazev) ;
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbbb63e2dab37_content')) { function _cbbb63e2dab37_content() { extract(func_get_arg(0))
?>

    <div id="top">
        <div class="nazev"><?php echo TemplateHelpers::escapeHtml($Sestava->nazev) ?></div>
        <div class="urad">
            <?php echo TemplateHelpers::escapeHtml($Urad->nazev) ?>, <?php echo TemplateHelpers::escapeHtml($rok) ?>

        </div>
    </div>
    <br style="clear: both;" />
<?php if (count($seznam)>0): ?>
    <table>
        <tr>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($sloupce) as $sl): ?>
            <th><?php echo TemplateHelpers::escapeHtml($sloupce_nazvy[$sl]) ?></th>
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
        </tr>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($seznam) as $dok): ?>
        <tr>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($sloupce) as $sl): ?>
            <td>
<?php if ($sl=='spis'): if (count($dok->spisy)>0): foreach ($iterator = $_cb->its[] = new SmartCachingIterator($dok->spisy) as $spis): ?>
            <?php echo TemplateHelpers::escapeHtml($spis->nazev) ?>

<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ;else: ?>
            &nbsp;
<?php endif ;elseif ($sl=='subjekty'): if (count($dok->subjekty)>0): foreach ($iterator = $_cb->its[] = new SmartCachingIterator($dok->subjekty) as $subjekt): ?>
            <?php echo TemplateHelpers::escapeHtml(Subjekt::displayName($subjekt,'plna_adresa')) ?><br />
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ;else: ?>
            &nbsp;
<?php endif ;elseif ($sl=='pocet_nelistu'): ?>
                <?php echo TemplateHelpers::escapeHtml(count($dok->prilohy)) ?>

<?php elseif ($sl=='vyridil'): if (empty($dok->prideleno->prideleno_jmeno)): ?>
            &nbsp;
<?php else: ?>
            <?php echo TemplateHelpers::escapeHtml($dok->prideleno->prideleno_jmeno) ?>

<?php endif ;elseif ($sl=='datum_odeslani'): if (count($dok->odeslani)): foreach ($iterator = $_cb->its[] = new SmartCachingIterator($dok->odeslani) as $odes): ?>
            <?php echo TemplateHelpers::escapeHtml($template->edate($odes->datum_odeslani)) ?><br />
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ;else: ?>
            &nbsp;
<?php endif ;elseif ($sl=='zaznam_vyrazeni'): ?>
            &nbsp;
<?php elseif ($sl=='datum_vzniku'): ?>
            <?php echo TemplateHelpers::escapeHtml($template->edate($dok->datum_vzniku)) ?>




                
<?php else: ?>
            <?php echo TemplateHelpers::escapeHtml($dok->$sl) ?>

<?php endif ?>
            </td>
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
        </tr>
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>


    </table>
<?php else: ?>
    <div style="text-align: center; margin-top: 15px;">
        Tato sestava neobsahuje žádné dokumenty.
    </div>
<?php endif ?>

<pre>
<?php //print_r($seznam) ?>
</pre><?php
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
