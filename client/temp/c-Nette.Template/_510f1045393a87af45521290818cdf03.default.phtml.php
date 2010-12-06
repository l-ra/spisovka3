<?php //netteCache[01]000238a:2:{s:4:"time";s:21:"0.98143300 1291371227";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:83:"C:\xampp\htdocs\spisovka1\trunk/app/templates/SpisovkaModule/Uzivatel/default.phtml";i:2;i:1291364700;}}}?><?php
// file …/templates/SpisovkaModule/Uzivatel/default.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'a1c5ca22e1'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb4504ca1730_title')) { function _cbb4504ca1730_title() { extract(func_get_arg(0))
?>Informace o uživateli<?php
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbbc1b9779e94_content')) { function _cbbc1b9779e94_content() { extract(func_get_arg(0))
?>
    <h2><?php echo TemplateHelpers::escapeHtml(Osoba::displayName($Osoba)) ?></h2>

    <div class="detail_blok">
        <div class="detail_hlavicka">Informace o uživateli</div>
<?php if (($FormUpravit=='info')): $control->getWidget("upravitForm")->render() ;else: ?>
        <dl class="detail_item">
            <dt>Jméno:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml(Osoba::displayName($Osoba)) ?>&nbsp;</dd>
        </dl>
        <dl class="detail_item">
            <dt>email:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($Osoba->email) ?>&nbsp;</dd>
        </dl>
        <dl class="detail_item">
            <dt>telefon:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($Osoba->telefon) ?>&nbsp;</dd>
        </dl>
        <dl class="detail_item">
            <dt>funkce:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($Osoba->pozice) ?>&nbsp;</dd>
        </dl>
        <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Uzivatel:default", array('id'=>$Osoba->id, 'upravit'=>'info'))) ?>">Upravit osobní údaje</a>
<?php endif ?>
    </div>

    <div class="detail_blok">
        <div class="detail_hlavicka">Uživatelský účet</div>
        <dl class="detail_item">
            <dt>Uživatelské jméno:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($Uzivatel->username) ?>&nbsp;</dd>
        </dl>
<?php if (($ZmenaHesla==1)): $control->getWidget("userForm")->render() ;endif ?>
        <dl class="detail_item">
            <dt>Poslední přihlášení:</dt>
            <dd><?php if ($Uzivatel->last_login): echo TemplateHelpers::escapeHtml($template->edatetime($Uzivatel->last_login)) ?>  z  <?php echo TemplateHelpers::escapeHtml($Uzivatel->last_ip) ;endif ?>&nbsp;</dd>
        </dl>
        <dl class="detail_item">
            <dt>Role:</dt>
            <dd>
<?php if (count($Role)>0): foreach ($iterator = $_cb->its[] = new SmartCachingIterator($Role) as $r): ?>
                        <?php echo TemplateHelpers::escapeHtml($r->name) ?><br />
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ;else: ?>
                Tento uživatel není přiřazen k žádné roli
<?php endif ?>
            </dd>
        </dl>
        <dl class="detail_item">
            <dt>&nbsp;</dt>
            <dd>
                <a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Uzivatel:default", array('zmenitheslo'=>1))) ?>">Změnit heslo</a>
            </dd>
        </dl>
    </div><?php
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
