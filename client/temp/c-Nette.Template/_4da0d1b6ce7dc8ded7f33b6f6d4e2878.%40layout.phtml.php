<?php //netteCache[01]000214a:2:{s:4:"time";s:21:"0.85794200 1291371144";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:59:"C:\xampp\htdocs\spisovka1\trunk/app/templates/@layout.phtml";i:2;i:1291364710;}}}?><?php
// file …/templates/@layout.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'ea4c994844'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb20bc23a1ec_title')) { function _cbb20bc23a1ec_title() { extract(func_get_arg(0))
;
}}


//
// block javascript
//
if (!function_exists($_cb->blocks['javascript'][] = '_cbbb464a6d6b3_javascript')) { function _cbbb464a6d6b3_javascript() { extract(func_get_arg(0))
;
}}


//
// block debug
//
if (!function_exists($_cb->blocks['debug'][] = '_cbbe12a4b213d_debug')) { function _cbbe12a4b213d_debug() { extract(func_get_arg(0))
;
}}

//
// end of blocks
//

if ($_cb->extends) { ob_start(); }

if (SnippetHelper::$outputAllowed) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>Spisová služba - <?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?></title>
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/site.css" />
    <link type="text/css" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/redmond/jquery-ui-custom.css" rel="stylesheet" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
    <script type="text/javascript" src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>js/jquery-min.js"></script>
    <script type="text/javascript" src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>js/jquery-ui-custom.min.js"></script>
    <script type="text/javascript">
        var baseUri = '<?php echo $klientUri ?>';
    </script>
    <?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['javascript']), get_defined_vars()); } ?>

    <script type="text/javascript" src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>js/jquery.nette.js"></script>
    <script type="text/javascript" src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>js/script.js"></script>
</head>
<body>
<div id="layout_top">
    <div id="top">
        <h1><a href="<?php echo MyMacros::vlink(":Spisovka:Default:",$control->link(":Spisovka:Default:")) ?>">Spisová služba</a> <span id="top_urad"><?php echo TemplateHelpers::escapeHtml($Urad->nazev) ?></span></h1>
        <div id="top_menu">
            <?php if ( @Environment::getUser()->isAllowed('Spisovka_DefaultPresenter') ) { ?><a href="<?php echo MyMacros::vlink(":Spisovka:Default:",$control->link(":Spisovka:Default:")) ?>">Spisová služba</a>&nbsp;&nbsp;<?php } ?>

            <?php if ( @Environment::getUser()->isAllowed('Epodatelna_DefaultPresenter') ) { ?><a href="<?php echo MyMacros::vlink(":Epodatelna:Default:",$control->link(":Epodatelna:Default:")) ?>">E-podatelna</a>&nbsp;&nbsp;<?php } ?>

            <?php if ( @Environment::getUser()->isAllowed('Admin_DefaultPresenter') ) { ?><a href="<?php echo MyMacros::vlink(":Admin:Default:",$control->link(":Admin:Default:")) ?>">Administrace</a>&nbsp;&nbsp;<?php } ?>

            <?php if ( @Environment::getUser()->isAllowed('Spisovka_UzivatelPresenter') ) { ?><a href="<?php echo MyMacros::vlink(":Spisovka:Uzivatel:",$control->link(":Spisovka:Uzivatel:")) ?>">Nastavení</a>&nbsp;&nbsp;<?php } ?>

            <a href="<?php echo TemplateHelpers::escapeHtml($klientUri) ;echo TemplateHelpers::escapeHtml($currentUri) ?>">Nápověda</a>&nbsp;&nbsp;
            <?php if ( @Environment::getUser()->isAllowed('Spisovka_UzivatelPresenter','logout') ) { ?><a href="<?php echo MyMacros::vlink(":Spisovka:Uzivatel:logout",$control->link(":Spisovka:Uzivatel:logout")) ?>">Odhlásit</a><?php } ?>

        </div>
        <div id="top_jmeno">
            <strong><?php echo TemplateHelpers::escapeHtml($user->name) ?></strong>
            <br />
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($user->user_roles) as $role): ?>
                <?php echo TemplateHelpers::escapeHtml($role->name) ?><br />
<?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
        </div>
    </div>
</div>
<div id="layout">
    <div id="menu">
        <?php if ( @Environment::getUser()->isAllowed('Spisovka_DokumentyPresenter','default') ) { ?><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Dokumenty:default")) ?>">Seznam dokumentů</a><?php } ?>

        <?php if ( @Environment::getUser()->isAllowed('Spisovka_DokumentyPresenter','novy') ) { ?><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Dokumenty:novy")) ?>">Nový dokument</a><?php } ?>

        <?php if ( @Environment::getUser()->isAllowed('Spisovka_SpisyPresenter','default') ) { ?><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Spisy:default")) ?>">Spisy</a><?php } ?>

        <?php if ( @Environment::getUser()->isAllowed('Spisovka_SestavyPresenter','default') ) { ?><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Sestavy:default")) ?>">Sestavy</a><?php } ?>

        <?php if ( @Environment::getUser()->isAllowed('Spisovka_VyhledatPresenter','default') ) { ?><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Vyhledat:default")) ?>">Vyhledat</a><?php } ?>

    </div><?php if (count($flashes)>0): ?>
    <div id="flash">

        <?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($flashes) as $flash): ?><div class="flash_message flash_<?php echo TemplateHelpers::escapeHtml($flash->type) ?>"><?php echo TemplateHelpers::escapeHtml($flash->message) ?></div><?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>

    </div>
<?php endif ?>
    <div id="content">
<?php } if ($_cb->foo = SnippetHelper::create($control, "main")) { $_cb->snippets[] = $_cb->foo ?>
        <?php } LatteMacros::callBlock($_cb->blocks, 'content', get_defined_vars()) ;if (SnippetHelper::$outputAllowed) { array_pop($_cb->snippets)->finish(); } if (SnippetHelper::$outputAllowed) { ?>
    </div>
<?php if ($debuger): ?>
    <div id="debug">
        Module:    <?php echo TemplateHelpers::escapeHtml($module) ?>

        Presenter: <?php echo TemplateHelpers::escapeHtml($presenter) ?>

        View:      <?php echo TemplateHelpers::escapeHtml($view) ?>

        SQL:       <?php echo dibi::$numOfQueries ." : ". dibi::$totalTime ?>
        
        Memory:    <?php echo TemplateHelpers::bytes(memory_get_usage()) . ' | ' . TemplateHelpers::bytes(memory_get_peak_usage()) . ' [peak]' ?>

        <?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['debug']), get_defined_vars()); } ?>


    </div>
<?php endif ?>
</div>
<div id="layout_bottom">
    <div id="bottom">
        <strong><?php echo TemplateHelpers::escapeHtml($AppInfo[2]) ?></strong><br/>
        Na toto dílo se vztahuje <a href="http://ec.europa.eu/idabc/eupl">licence EUPL V.1.1</a>
    </div>
</div>


</body>
</html><?php
}

if ($_cb->extends) { ob_end_clean(); LatteMacros::includeTemplate($_cb->extends, get_defined_vars(), $template)->render(); }
