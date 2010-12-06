<?php //netteCache[01]000211a:2:{s:4:"time";s:21:"0.63167700 1291383022";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:56:"C:\xampp\htdocs\spisovka1\trunk/app/../help/hlavni.phtml";i:2;i:1291364722;}}}?><?php
// file …/../help/hlavni.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, 'b1f131b8fb'); unset($_extends);

if (SnippetHelper::$outputAllowed) {
?>
    <h2>Spisová služba</h2>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($Napovedy['spisovka']) as $presenters_i => $presenters): ?>
    <h3><?php echo TemplateHelpers::escapeHtml(isset($HelpName['spisovka/'.$presenters_i])?$HelpName['spisovka/'.$presenters_i]:$presenters_i) ?></h3>
<?php if (count($presenters)>0): ?>
        <ul>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($presenters) as $actions): if (isset($HelpName[$actions->code])): ?>
            <li><a href="<?php echo TemplateHelpers::escapeHtml($klientUri) ?>napoveda/<?php echo TemplateHelpers::escapeHtml($actions->url) ?>"><?php echo TemplateHelpers::escapeHtml($HelpName[$actions->code]) ?></a></li>
<?php else: ?>
            <li><?php echo TemplateHelpers::escapeHtml($actions->name) ?></li>
<?php endif ;endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
        </ul>
<?php endif ;endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>

    <h2 class="seznam">E-podatelna</h2>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($Napovedy['epodatelna']) as $presenters_i => $presenters): ?>
    <h3><?php echo TemplateHelpers::escapeHtml(isset($HelpName['epodatelna/'.$presenters_i])?$HelpName['epodatelna/'.$presenters_i]:$presenters_i) ?></h3>
<?php if (count($presenters)>0): ?>
        <ul>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($presenters) as $actions): if (isset($HelpName[$actions->url])): ?>
            <li><a href="<?php echo TemplateHelpers::escapeHtml($klientUri) ?>napoveda/<?php echo TemplateHelpers::escapeHtml($actions->url) ?>"><?php echo TemplateHelpers::escapeHtml($HelpName[$actions->url]) ?></a></li>
<?php else: ?>
            <li><?php echo TemplateHelpers::escapeHtml($actions->name) ?></li>
<?php endif ;endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
        </ul>
<?php endif ;endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>

    <h2 class="seznam">Administrace</h2>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($Napovedy['admin']) as $presenters_i => $presenters): ?>
    <h3><?php echo TemplateHelpers::escapeHtml(isset($HelpName['admin/'.$presenters_i])?$HelpName['admin/'.$presenters_i]:$presenters_i) ?></h3>
<?php if (count($presenters)>0): ?>
        <ul>
<?php foreach ($iterator = $_cb->its[] = new SmartCachingIterator($presenters) as $actions): if (isset($HelpName[$actions->url])): ?>
            <li><a href="<?php echo TemplateHelpers::escapeHtml($klientUri) ?>napoveda/<?php echo TemplateHelpers::escapeHtml($actions->url) ?>"><?php echo TemplateHelpers::escapeHtml($HelpName[$actions->url]) ?></a></li>
<?php else: ?>
            <li><?php echo TemplateHelpers::escapeHtml($actions->name) ?></li>
<?php endif ;endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ?>
        </ul>
<?php endif ?>
    <?php endforeach; array_pop($_cb->its); $iterator = end($_cb->its) ;
}
