<?php //netteCache[01]000235a:2:{s:4:"time";s:21:"0.63281800 1291371235";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:80:"C:\xampp\htdocs\spisovka1\trunk/app/templates/SpisovkaModule/Spisy/default.phtml";i:2;i:1291364701;}}}?><?php
// file …/templates/SpisovkaModule/Spisy/default.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '34bd2d7729'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb3f8ab192da_title')) { function _cbb3f8ab192da_title() { extract(func_get_arg(0))
;if (SnippetHelper::$outputAllowed) { ?>Seznam spisů<?php
}}


//
// block debug
//
if (!function_exists($_cb->blocks['debug'][] = '_cbb20b8b8b975_debug')) { function _cbb20b8b8b975_debug() { extract(func_get_arg(0))
;if (SnippetHelper::$outputAllowed) { print_r($seznam); } 
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbb3a19b11ab7_content')) { function _cbb3a19b11ab7_content() { extract(func_get_arg(0))
;if (SnippetHelper::$outputAllowed) { ?>

    <h2>Seznam spisů</h2>

<?php if (count($seznam)>0): ?>
    <table>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($seznam) as $spis): ?>
        <tr class="<?php echo TemplateHelpers::escapeHtml($spis->class) ?>" id="sitem<?php echo TemplateHelpers::escapeHtml($spis->spis_id) ?>">
            <td>
                <?php echo str_repeat("&nbsp;", 5*$spis->uroven) ?>

<?php if ($spis->typ=='VS'): ?>
                <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/folder_yellow.png" alt="Věcná skupina" title="Věcná skupna" width="16" height="16" onclick="return toggle('item<?php echo $spis->id ?>');" />
                &nbsp;&nbsp;
                <?php echo TemplateHelpers::escapeHtml($spis->nazev) ?>

<?php else: ?>
                <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/spisy.png" alt="Spis" title="Spis" width="16" height="16" />
                &nbsp;&nbsp;
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Spisy:detail", array('id'=>$spis->id))) ?>"><?php echo TemplateHelpers::escapeHtml($spis->nazev) ?></a>
<?php endif ?>
            </td>
        </tr>
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>

    </table>
<?php else: ?>
    <div class="prazdno">Nebyly zjištěny žádné spisy.</div>
<?php endif ;
}}

//
// end of blocks
//

if ($_cb->extends) { ob_start(); }

if (SnippetHelper::$outputAllowed) {
} if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?>

<?php } if (!$_cb->extends) { call_user_func(reset($_cb->blocks['debug']), get_defined_vars()); }  if (SnippetHelper::$outputAllowed) { } if (!$_cb->extends) { call_user_func(reset($_cb->blocks['content']), get_defined_vars()); }  
}

if ($_cb->extends) { ob_end_clean(); LatteMacros::includeTemplate($_cb->extends, get_defined_vars(), $template)->render(); }
