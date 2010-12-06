<?php //netteCache[01]000239a:2:{s:4:"time";s:21:"0.59254600 1291371144";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:84:"C:\xampp\htdocs\spisovka1\trunk/app/templates/SpisovkaModule/Dokumenty/default.phtml";i:2;i:1291364699;}}}?><?php
// file …/templates/SpisovkaModule/Dokumenty/default.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'ba29e6d648'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbba5b7f44729_title')) { function _cbba5b7f44729_title() { extract(func_get_arg(0))
;if (SnippetHelper::$outputAllowed) { ?>Seznam dokumentů<?php
}}


//
// block debug
//
if (!function_exists($_cb->blocks['debug'][] = '_cbb08de19beef_debug')) { function _cbb08de19beef_debug() { extract(func_get_arg(0))
;if (SnippetHelper::$outputAllowed) { print_r($seznam); } 
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbbc7401e992d_content')) { function _cbbc7401e992d_content() { extract(func_get_arg(0))
;if (SnippetHelper::$outputAllowed) { ?>
    <h2>Seznam dokumentů</h2>

    <div id="filtr">
<?php $control->getWidget("filtrForm")->render() ?>
    </div>
    <div id="search">
<?php $control->getWidget("searchForm")->render() ?>
    </div>
    
    <div style="clear:both;">&nbsp;</div>

    <div style="margin: 3px 0px;">
    seřadit podle:
        <a href="<?php echo TemplateHelpers::escapeHtml($control->link("this", array('seradit'=>'stav'))) ?>" title="Seřadit podle stavu dokumentu (nový, předaný, vyřizuje se, vyřízený, ...)">stavu</a> &nbsp;
        <a href="<?php echo TemplateHelpers::escapeHtml($control->link("this", array('seradit'=>'cj'))) ?>" title="Seřadit podle čísla jednacího">čísla jednacího</a> &nbsp;
        <a href="<?php echo TemplateHelpers::escapeHtml($control->link("this", array('seradit'=>'dvzniku'))) ?>" title="Seřadit podle data přijetí/vzniku">data přijeti/vzniku</a> &nbsp;
        <a href="<?php echo TemplateHelpers::escapeHtml($control->link("this", array('seradit'=>'vec'))) ?>" title="Seřadit podle věci">věci</a> &nbsp;
        <a href="<?php echo TemplateHelpers::escapeHtml($control->link("this", array('seradit'=>'prideleno'))) ?>" title="Seřadit podle přijmení osoby přidělené k dokumentu">přidělené osoby</a> &nbsp;
    </div>

    <div id="dokumenty">
<?php if (count($seznam)>0): ?>
    <table>
        <tr>
            <th class="typ">Typ dokumentu</th>
            <th class="prijato">Přijato</th>
            <th class="cislo_jadnaci">Číslo jednací<br />JID<br />Spisová značka</th>
            <th class="vec">Věc<br />Adresáti/odesilatelé<br />počet listů a příloh</th>
            <th class="prideleno">Přiděleno</th>
            <th class="stav">Stav</th>
        </tr>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($seznam) as $dok): ?>
        <tr<?php if ($dok->lhuta_stav==2): ?> class="red"<?php elseif ($dok->lhuta_stav==1): ?> class="yellow"<?php endif ?>">
            <td class="typ">
                <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/smer<?php echo TemplateHelpers::escapeHtml($dok->typ_dokumentu->smer) ?>.png" alt="<?php echo TemplateHelpers::escapeHtml($dok->typ_dokumentu->nazev) ?>" title="<?php echo TemplateHelpers::escapeHtml($dok->typ_dokumentu->nazev) ?>" width="16" height="16" />
                <img src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>images/icons/typdok<?php echo TemplateHelpers::escapeHtml($dok->typ_dokumentu->typ) ?>.png" alt="<?php echo TemplateHelpers::escapeHtml($dok->typ_dokumentu->nazev) ?>" title="<?php echo TemplateHelpers::escapeHtml($dok->typ_dokumentu->nazev) ?>" width="24" height="16" />
            </td>
            <td class="prijato" title="<?php echo TemplateHelpers::escapeHtml($template->edatetime($dok->datum_vzniku)) ?>">
                <?php echo TemplateHelpers::escapeHtml($template->edate($dok->datum_vzniku)) ?>

            </td>
            <td class="cislo_jadnaci">
                &nbsp;<strong><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Dokumenty:detail", array('id'=>$dok->id))) ?>">
                   <?php echo TemplateHelpers::escapeHtml($dok->cislo_jednaci) ;if ($Typ_evidence=='sberny_arch'): ?> / <?php echo TemplateHelpers::escapeHtml($dok->poradi) ;endif ?>

                </a></strong>
                <div class="small">&nbsp;<?php echo TemplateHelpers::escapeHtml($dok->jid) ?></div>
<?php if (count($dok->spisy)>0): foreach ($iterator = $_cb->its[] = new SmartCachingIterator($dok->spisy) as $spis): ?>
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Spisy:detail", array('id'=>$spis->id))) ?>"><?php echo TemplateHelpers::escapeHtml($spis->nazev) ?></a><br />
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ;endif ?>
            </td>
            <td class="vec">
                <strong title="<?php echo TemplateHelpers::escapeHtml($dok->popis) ?>"><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Dokumenty:detail", array('id'=>$dok->id))) ?>">
                    <?php echo TemplateHelpers::escapeHtml($dok->nazev) ?>

                </a></strong>
                <br />
                <div class="mezera">
<?php if (count($dok->subjekty)>0): ?>

<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($dok->subjekty) as $subjekt): ?>
                <?php echo TemplateHelpers::escapeHtml(Subjekt::displayName($subjekt,'plna_adresa')) ?><br />
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ;else: ?>
                nejsou přiděleny žádné subjekty!
                <br />
<?php endif ?>
                </div>
                <span class="small">přílohy:
                <?php echo TemplateHelpers::escapeHtml(($dok->pocet_listu)+0) ?> listů,
                <?php echo TemplateHelpers::escapeHtml(($dok->pocet_priloh)+0) ?> list. příloh,
                <?php echo TemplateHelpers::escapeHtml((count($dok->prilohy)+0)) ?> příloh</span>
            </td>



<?php if ($dok->stav_dokumentu == 1): ?>
            <td class="prideleno">
                <?php echo TemplateHelpers::escapeHtml($dok->prideleno->prideleno_jmeno) ?>

            </td>
            <td class="stav <?php if ($dok->lhuta_stav==2): ?>stav_red" title="Vypršela lhůta k vyřízení! Vyříďte neprodleně tento dokument."<?php elseif ($dok->lhuta_stav==1): ?>stav_yellow" title="Za pár dní vyprší lhůta k vyřízení! Vyříďte co nejrychleji tento dokument."<?php else: ?>stav"<?php endif ?>>
                nový
                <br />
                <span title="<?php echo TemplateHelpers::escapeHtml($template->edatetime($dok->prideleno->date)) ?>"><?php echo TemplateHelpers::escapeHtml($template->edate($dok->prideleno->date)) ?></span>
            </td>
<?php elseif ($dok->stav_dokumentu == 2 && !empty($dok->predano)): ?>
            <td class="prideleno">
<?php if (empty($dok->prideleno->prideleno)): ?>
                <?php echo TemplateHelpers::escapeHtml($dok->prideleno->orgjednotka_info->zkraceny_nazev) ?>

<?php else: ?>
                <?php echo TemplateHelpers::escapeHtml($dok->prideleno->prideleno_jmeno) ?>

                <br />
                <?php echo TemplateHelpers::escapeHtml(@$dok->prideleno->orgjednotka_info->zkraceny_nazev) ?>

<?php endif ?>
            </td>
            <td class="stav <?php if ($dok->lhuta_stav==2): ?>stav_red<?php elseif ($dok->lhuta_stav==1): ?>stav_yellow<?php else: ?>stav<?php endif ?>">
                předáno
                <br />
                <span title="<?php echo TemplateHelpers::escapeHtml($template->edatetime($dok->predano->date_predani)) ?>"><?php echo TemplateHelpers::escapeHtml($template->edate($dok->predano->date_predani)) ?></span>
            </td>
<?php elseif ($dok->stav_dokumentu == 2 && !empty($dok->prideleno)): ?>
            <td class="prideleno">
<?php if (empty($dok->prideleno->prideleno)): ?>
                <?php echo TemplateHelpers::escapeHtml($dok->prideleno->orgjednotka_info->zkraceny_nazev) ?>

<?php else: ?>
                <?php echo TemplateHelpers::escapeHtml($dok->prideleno->prideleno_jmeno) ?>

                <br />
                <?php echo TemplateHelpers::escapeHtml(@$dok->prideleno->orgjednotka_info->zkraceny_nazev) ?>

<?php endif ?>
            </td>
            <td class="stav <?php if ($dok->lhuta_stav==2): ?>stav_red<?php elseif ($dok->lhuta_stav==1): ?>stav_yellow<?php else: ?>stav<?php endif ?>">
                přiděleno
                <br />
                <span title="<?php echo TemplateHelpers::escapeHtml($template->edatetime($dok->prideleno->date)) ?>"><?php echo TemplateHelpers::escapeHtml($template->edate($dok->prideleno->date)) ?></span>
            </td>
<?php elseif ($dok->stav_dokumentu == 3): ?>
            <td class="prideleno">
                <?php echo TemplateHelpers::escapeHtml($dok->prideleno->prideleno_jmeno) ?>

                <br />
                <?php echo TemplateHelpers::escapeHtml(@$dok->prideleno->orgjednotka_info->zkraceny_nazev) ?>

            </td>
            <td class="stav <?php if ($dok->lhuta_stav==2): ?>stav_red<?php elseif ($dok->lhuta_stav==1): ?>stav_yellow<?php else: ?>stav<?php endif ?>">
                vyřizuje se
                <br />
                <span title="<?php echo TemplateHelpers::escapeHtml($template->edatetime($dok->prideleno->date)) ?>"><?php echo TemplateHelpers::escapeHtml($template->edate($dok->prideleno->date)) ?></span>
            </td>
<?php elseif ($dok->stav_dokumentu == 4): ?>
            <td class="prideleno">
                <?php echo TemplateHelpers::escapeHtml($dok->prideleno->prideleno_jmeno) ?>

                <br />
                <?php echo TemplateHelpers::escapeHtml(@$dok->prideleno->orgjednotka_info->zkraceny_nazev) ?>

            </td>
            <td class="stav">
                vyřízeno
                <br />
                <span title="<?php echo TemplateHelpers::escapeHtml($template->edatetime($dok->datum_vyrizeni)) ?>"><?php echo TemplateHelpers::escapeHtml($template->edate($dok->datum_vyrizeni)) ?></span>
            </td>
<?php elseif ($dok->stav_dokumentu == 5): ?>
            <td class="prideleno">
                <?php echo TemplateHelpers::escapeHtml($dok->prideleno->prideleno_jmeno) ?>

                <br />
                <?php echo TemplateHelpers::escapeHtml(@$dok->prideleno->orgjednotka_info->zkraceny_nazev) ?>

            </td>
            <td class="stav">
                vyřízeno
                <br />
                <span title="<?php echo TemplateHelpers::escapeHtml($template->edatetime($dok->datum_vyrizeni)) ?>"><?php echo TemplateHelpers::escapeHtml($template->edate($dok->datum_vyrizeni)) ?></span>
            </td>

<?php else: ?>
            <td class="prideleno">
                &nbsp;
            </td>
            <td class="stav">
                nepřiřazeno!
                <br />
                lhůta <?php echo TemplateHelpers::escapeHtml($dok->lhuta) ?> dní
            </td>
<?php endif ?>



        </tr>
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>


    </table>
<?php $control->getWidget("vp")->render() ;else: ?>
        <div>&nbsp;</div>
<?php if (isset($no_items)): if ($no_items==1): ?>
        <div class="prazdno">Nemáte k dispozici žádné dokumenty.</div>
<?php elseif ($no_items==2): ?>
        <div class="prazdno">K danému filtru nemáte k dispozici žádné dokumenty.</div>
<?php elseif ($no_items==3): ?>
        <div class="prazdno">Dokumenty odpovidající hledanému výrazu nebyly nalezeny.</div>
<?php elseif ($no_items==4): ?>
        <div class="prazdno">Dokumenty odpovidající daným požadavkům nebyly nalezeny.</div>
<?php endif ;else: ?>
    <div class="prazdno">Nebyly zjištěny žádné dokumenty.</div>
<?php endif ;endif ?>
    </div>

<?php
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
