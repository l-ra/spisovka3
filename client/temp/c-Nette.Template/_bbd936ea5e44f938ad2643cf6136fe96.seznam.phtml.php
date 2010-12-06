<?php //netteCache[01]000238a:2:{s:4:"time";s:21:"0.82409500 1291371183";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:83:"C:\xampp\htdocs\spisovka1\trunk/app/templates/EpodatelnaModule/Default/seznam.phtml";i:2;i:1291364697;}}}?><?php
// file …/templates/EpodatelnaModule/Default/seznam.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '47b13e9068'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb5fec8615da_title')) { function _cbb5fec8615da_title() { extract(func_get_arg(0))
?>Seznam zpráv<?php
}}


//
// block debug
//
if (!function_exists($_cb->blocks['debug'][] = '_cbb4dfd9721cc_debug')) { function _cbb4dfd9721cc_debug() { extract(func_get_arg(0))
;if (SnippetHelper::$outputAllowed) { print_r($seznam); } 
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbbc9b58cadd3_content')) { function _cbbc9b58cadd3_content() { extract(func_get_arg(0))
?>

<h2>Seznam příchozích zpráv</h2>

    <div id="dokumenty">
<?php if (count($seznam)>0): ?>
    <table>

        <tr>
            <th class="typ">Typ zprávy</th>
            <th class="prijato">Doručeno<br />Přijato EP</th>
            <th class="cislo_jadnaci">ID</th>
            <th class="vec">Věc<br />Odesilatel/-é</th>
            <th class="prideleno">Schránka</th>
            <th class="stav">Stav</th>
        </tr>

<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($seznam) as $ep): ?>
        <tr>
            <td class="typ">
<?php if (!empty($ep->email_signature)): ?>
                <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/typdok1.png" alt="Email" title="Email" width="24" height="16" />
<?php else: ?>
                <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/typdok2.png" alt="ISDS" title="ISDS" width="24" height="16" />
<?php endif ?>
            </td>
            <td class="prijato">
                <span title="<?php echo TemplateHelpers::escapeHtml($template->edatetime($ep->doruceno_dne)) ?>"><?php echo TemplateHelpers::escapeHtml($template->edate($ep->doruceno_dne)) ?></span>
                <br />
                <span title="<?php echo TemplateHelpers::escapeHtml($template->edatetime($ep->prijato_dne)) ?>"><?php echo TemplateHelpers::escapeHtml($template->edate($ep->prijato_dne)) ?></span>
            </td>
            <td class="cislo_jadnaci">
                &nbsp;<strong><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Epodatelna:Default:detail", array('id'=>$ep->id))) ?>">
                   OSS-EP-I-<?php echo TemplateHelpers::escapeHtml($ep->poradi) ?>-<?php echo TemplateHelpers::escapeHtml($ep->rok) ?>

                </a></strong>
            </td>
            <td class="vec">
                <strong title="<?php echo TemplateHelpers::escapeHtml($ep->popis) ?>"><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Epodatelna:Default:detail", array('id'=>$ep->id))) ?>">
                    <?php echo TemplateHelpers::escapeHtml($ep->predmet) ?>

                </a></strong>
                <br />
                <?php echo TemplateHelpers::escapeHtml($ep->odesilatel) ?>

            </td>
            <td class="prideleno">
                <?php echo TemplateHelpers::escapeHtml($ep->adresat) ?>

            </td>
            <td class="stav" title="<?php echo TemplateHelpers::escapeHtml($ep->stav_info) ?>">
<?php if (($ep->stav==0)): ?>
                nový
<?php elseif (($ep->stav==1)): ?>
                nový přijatý
<?php elseif (($ep->stav==10)): ?>
                evidovaný
<?php elseif (($ep->stav==11)): ?>
                jiná evidence:
                <br />
                <?php echo TemplateHelpers::escapeHtml($ep->evidence) ?>

<?php elseif (($ep->stav==100)): ?>
                zamítnutý
<?php else: ?>
                nelze zjístit stav
<?php endif ?>
            </td>
        </tr>
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
    </table>
<?php $control->getWidget("vp")->render() ;else: ?>
        <div class="prazdno">Nejsou žádné příchozí zprávy.</div>
<?php endif ?>
    </div>
<?php
}}

//
// end of blocks
//

if ($_cb->extends) { ob_start(); }

if (SnippetHelper::$outputAllowed) {
if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?>

<?php } if (!$_cb->extends) { call_user_func(reset($_cb->blocks['debug']), get_defined_vars()); }  if (SnippetHelper::$outputAllowed) { if (!$_cb->extends) { call_user_func(reset($_cb->blocks['content']), get_defined_vars()); }  
}

if ($_cb->extends) { ob_end_clean(); LatteMacros::includeTemplate($_cb->extends, get_defined_vars(), $template)->render(); }
