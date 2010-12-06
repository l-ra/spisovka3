<?php //netteCache[01]000218a:2:{s:4:"time";s:21:"0.94775700 1291371183";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:63:"C:\xampp\htdocs\spisovka1\trunk/app/templates/@epodatelna.phtml";i:2;i:1291364709;}}}?><?php
// file …/templates/@epodatelna.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '045cf9509a'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb96d357afb9_title')) { function _cbb96d357afb9_title() { extract(func_get_arg(0))
;
}}


//
// block javascript
//
if (!function_exists($_cb->blocks['javascript'][] = '_cbb15ba5732f8_javascript')) { function _cbb15ba5732f8_javascript() { extract(func_get_arg(0))
;
}}


//
// block debug
//
if (!function_exists($_cb->blocks['debug'][] = '_cbba21d28981b_debug')) { function _cbba21d28981b_debug() { extract(func_get_arg(0))
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
    <title>Spisová služba - E-podatelna - <?php if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?></title>
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/site.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/epodatelna_site.css" />
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
    <script type="text/javascript" src="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>js/script_epodatelna.js"></script>
</head>
<body>
<div id="layout_top">
    <div id="top">
        <h1><a href="<?php echo MyMacros::vlink(":Epodatelna:Default:",$control->link(":Epodatelna:Default:")) ?>">E-podatelna</a> <span id="top_urad"><?php echo TemplateHelpers::escapeHtml($Urad->nazev) ?></span></h1>
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
        <?php if ( @Environment::getUser()->isAllowed('Epodatelna_DefaultPresenter','zkontrolovat') ) { ?><a href="<?php echo MyMacros::vlink(":Epodatelna:Default:zkontrolovat",$control->link(":Epodatelna:Default:zkontrolovat")) ?>">Zkontrolovat schránku</a><?php } ?>

        <?php if ( @Environment::getUser()->isAllowed('Epodatelna_DefaultPresenter','nove') ) { ?><a href="<?php echo MyMacros::vlink(":Epodatelna:Default:nove",$control->link(":Epodatelna:Default:nove")) ?>">Nové zprávy</a><?php } ?>

        <?php if ( @Environment::getUser()->isAllowed('Epodatelna_DefaultPresenter','prichozi') ) { ?><a href="<?php echo MyMacros::vlink(":Epodatelna:Default:prichozi",$control->link(":Epodatelna:Default:prichozi")) ?>">Příchozí zprávy</a><?php } ?>

        <?php if ( @Environment::getUser()->isAllowed('Epodatelna_DefaultPresenter','odchozi') ) { ?><a href="<?php echo MyMacros::vlink(":Epodatelna:Default:odchozi",$control->link(":Epodatelna:Default:odchozi")) ?>">Odchozí zprávy</a><?php } ?>

    </div><?php if (count($flashes)>0): ?>
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
