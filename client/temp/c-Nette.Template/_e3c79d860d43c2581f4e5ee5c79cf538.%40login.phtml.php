<?php //netteCache[01]000213a:2:{s:4:"time";s:21:"0.54487500 1291371120";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:58:"C:\xampp\htdocs\spisovka1\trunk/app/templates/@login.phtml";i:2;i:1291364710;}}}?><?php
// file …/templates/@login.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '9a3bc4021b'); unset($_extends);

if (SnippetHelper::$outputAllowed) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <title>Spisová služba</title>
    <link rel="stylesheet" type="text/css" media="screen" href="<?php echo TemplateHelpers::escapeHtml($baseUri) ?>css/site.css" />
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
</head>
<body>
<div id="layout_top">
    <div id="top">
        <h1><a href="<?php echo TemplateHelpers::escapeHtml($control->link(":Spisovka:Default:")) ?>">Spisová služba</a> <span id="top_urad"><?php echo TemplateHelpers::escapeHtml($Urad->nazev) ?></span></h1>
        <div id="top_menu">
            &nbsp;
        </div>
        <div id="top_jmeno">
            &nbsp;
        </div>
    </div>
</div>
<div id="layout">
    <div id="menu">
    &nbsp;
    </div><?php if (count($flashes)>0): ?>
    <div id="flash">

        <?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($flashes) as $flash): ?><div class="flash_message flash_<?php echo TemplateHelpers::escapeHtml($flash->type) ?>"><?php echo TemplateHelpers::escapeHtml($flash->message) ?></div><?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>

    </div>
<?php endif ?>
    <div id="content">
<?php LatteMacros::callBlock($_cb->blocks, 'content', get_defined_vars()) ?>
    </div>
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
