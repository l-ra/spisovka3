<?php //netteCache[01]000238a:2:{s:4:"time";s:21:"0.32594400 1291371327";s:9:"callbacks";a:1:{i:0;a:3:{i:0;a:2:{i:0;s:5:"Cache";i:1;s:9:"checkFile";}i:1;s:83:"C:\xampp\htdocs\spisovka1\trunk/app/templates/SpisovkaModule/Vyhledat/default.phtml";i:2;i:1291364700;}}}?><?php
// file …/templates/SpisovkaModule/Vyhledat/default.phtml
//

$_cb = LatteMacros::initRuntime($template, NULL, '75bf080e0d'); unset($_extends);


//
// block title
//
if (!function_exists($_cb->blocks['title'][] = '_cbb42ebf69060_title')) { function _cbb42ebf69060_title() { extract(func_get_arg(0))
;if (SnippetHelper::$outputAllowed) { ?>Vyhledat dokumenty<?php
}}


//
// block content
//
if (!function_exists($_cb->blocks['content'][] = '_cbb269a67006f_content')) { function _cbb269a67006f_content() { extract(func_get_arg(0))
;if (SnippetHelper::$outputAllowed) { ?>

    <h2>Vyhledat dokumenty</h2>

    <?php echo TemplateHelpers::escapeHtml($searchForm->render('begin')) ?>

    <div id="dokument_blok_vyrizeni">
        <div class="h2">
            Dokument
        </div>
        <dl class="detail_item">
            <dt>Typ dokumentu:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['typ_dokumentu_id']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Stav dokumentu:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['stav_dokumentu']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Číslo jednací:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['cislo_jednaci']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Spisová značka:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['spisova_znacka']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Věc:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['nazev']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Popis:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['popis']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Datum doručení/vzniku</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['datum_vzniku']->control) ?> <?php echo TemplateHelpers::escapeHtml($searchForm['datum_vzniku_cas']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Číslo jednací odesilatele:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['cislo_jednaci_odesilatele']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Poznámka:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['poznamka']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Počet listů:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['pocet_listu']->control) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-weight: normal;">Počet listů příloh: </span><?php echo TemplateHelpers::escapeHtml($searchForm['pocet_priloh']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Způsob vyžízení:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['zpusob_vyrizeni']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Datum vyžízení:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['datum_vyrizeni']->control) ?> <?php echo TemplateHelpers::escapeHtml($searchForm['datum_vyrizeni_cas']->control) ?>&nbsp;</dd>
        </dl>
        <dl class="detail_item">
            <dt>Datum odeslání:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['datum_odeslani']->control) ?> <?php echo TemplateHelpers::escapeHtml($searchForm['datum_odeslani_cas']->control) ?>&nbsp;</dd>
        </dl>
        <dl class="detail_item">
            <dt>Spisový znak:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['spisovy_znak_id']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Skartační znak:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['skartacni_znak']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Skartační lhůta:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['skartacni_lhuta']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Spouštěcí událost:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['spousteci_udalost']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Počet listů:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['vyrizeni_pocet_listu']->control) ?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span style="font-weight: normal;">Počet listů příloh: </span><?php echo TemplateHelpers::escapeHtml($searchForm['vyrizeni_pocet_priloh']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Uložení dokumentu:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['ulozeni_dokumentu']->control) ?>&nbsp;</dd>
        </dl>
        <dl class="detail_item">
            <dt>Poznámka k vyřízení:</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['poznamka_vyrizeni']->control) ?>&nbsp;</dd>
        </dl>
    </div>

    <div id="dokument_blok_poznamka">
        <div class="h2">
            Zaměstnanci
        </div>
        <dl class="detail_item">
            <dt>Přiděleno:</dt>
            <dd>
            <?php echo TemplateHelpers::escapeHtml($searchForm['prideleno']->control) ?>

            <script type="text/javascript">
            <!--
		$('#frmsearchForm-prideleno').focus().keyup(function(event) {
			$.getJSON(<?php echo TemplateHelpers::escapeJs($control->link("autoComplete!")) ?>, { typ: 1, text: $('#frmsearchForm-prideleno').val()}, function(payload) {
				$('#uprideleno').remove();

				var list = $('<ul id="uprideleno"></ul>').insertAfter('#frmsearchForm-prideleno');

				for (var i in payload.autoComplete) {
					$('<li></li>').html(payload.autoComplete[i]).appendTo(list);
				}
			});
		});
            -->
            </script>
            </dd>
        </dl>
        <dl class="detail_item">
            <dt>Předáno:</dt>
            <dd>
            <?php echo TemplateHelpers::escapeHtml($searchForm['predano']->control) ?>

            <script type="text/javascript">
            <!--
		$('#frmsearchForm-predano').focus().keyup(function(event) {
			$.getJSON(<?php echo TemplateHelpers::escapeJs($control->link("autoComplete!")) ?>, { typ: 2, text: $('#frmsearchForm-predano').val()}, function(payload) {
				$('#upredano').remove();

				var list = $('<ul id="upredano"></ul>').insertAfter('#frmsearchForm-predano');

				for (var i in payload.autoComplete) {
					$('<li></li>').html(payload.autoComplete[i]).appendTo(list);
				}
			});
		});
            -->
            </script>
            </dd>
        </dl>
    </div>

    <div id="dokument_blok_poznamka">
        <div class="h2">
            Organizační jednotka
        </div>
        <dl class="detail_item">
            <dt>Přiděleno:</dt>
            <dd>
            <?php echo TemplateHelpers::escapeHtml($searchForm['prideleno_org']->control) ?>

            <script type="text/javascript">
            <!--
		$('#frmsearchForm-prideleno_org').focus().keyup(function(event) {
			$.getJSON(<?php echo TemplateHelpers::escapeJs($control->link("autoCompleteOrg!")) ?>, { typ: 1, text: $('#frmsearchForm-prideleno_org').val()}, function(payload) {
				$('#oprideleno').remove();

				var list = $('<ul id="oprideleno"></ul>').insertAfter('#frmsearchForm-prideleno_org');

				for (var i in payload.autoComplete) {
					$('<li></li>').html(payload.autoComplete[i]).appendTo(list);
				}
			});
		});
            -->
            </script>
            </dd>
        </dl>
        <dl class="detail_item">
            <dt>Předáno:</dt>
            <dd>
            <?php echo TemplateHelpers::escapeHtml($searchForm['predano_org']->control) ?>

            <script type="text/javascript">
            <!--
		$('#frmsearchForm-predano_org').focus().keyup(function(event) {
			$.getJSON(<?php echo TemplateHelpers::escapeJs($control->link("autoCompleteOrg!")) ?>, { typ: 2, text: $('#frmsearchForm-predano_org').val()}, function(payload) {
				$('#opredano').remove();

				var list = $('<ul id="opredano"></ul>').insertAfter('#frmsearchForm-predano_org');

				for (var i in payload.autoComplete) {
					$('<li></li>').html(payload.autoComplete[i]).appendTo(list);
				}
			});
		});
            -->
            </script>
            </dd>
        </dl>
    </div>


    <div id="dokument_blok_poznamka">
        <div class="h2">
            Adresáti / odesílatelé
        </div>
        <dl class="detail_item">
            <dt>Typ subjektu</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['subjekt_type']->control) ?></dd>
        </dl>

        <dl class="detail_item">
            <dt>Název subjektu</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['subjekt_nazev']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>IČ</dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['subjekt_ic']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt>Ulice</dt>
            <dd>
                <?php echo TemplateHelpers::escapeHtml($searchForm['adresa_ulice']->control) ?>

                č.p. <?php echo TemplateHelpers::escapeHtml($searchForm['adresa_cp']->control) ?>

                č.o. <?php echo TemplateHelpers::escapeHtml($searchForm['adresa_co']->control) ?>

            </dd>
        </dl>
        <dl class="detail_item">
            <dt>PSČ a město</dt>
            <dd>
                <?php echo TemplateHelpers::escapeHtml($searchForm['adresa_psc']->control) ?>

                <?php echo TemplateHelpers::escapeHtml($searchForm['adresa_mesto']->control) ?>

            </dd>
        </dl>
        <dl class="detail_item">
            <dt><?php echo TemplateHelpers::escapeHtml($searchForm['adresa_stat']->label) ?></dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['adresa_stat']->control) ?></dd>
        </dl>

        <dl class="detail_item">
            <dt><?php echo TemplateHelpers::escapeHtml($searchForm['subjekt_email']->label) ?></dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['subjekt_email']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt><?php echo TemplateHelpers::escapeHtml($searchForm['subjekt_telefon']->label) ?></dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['subjekt_telefon']->control) ?></dd>
        </dl>
        <dl class="detail_item">
            <dt><?php echo TemplateHelpers::escapeHtml($searchForm['subjekt_isds']->label) ?></dt>
            <dd><?php echo TemplateHelpers::escapeHtml($searchForm['subjekt_isds']->control) ?></dd>
        </dl>

    </div>

    <div id="dokument_blok_akce">
        <?php echo TemplateHelpers::escapeHtml($searchForm['vyhledat']->control) ?>

    </div>
    <?php echo TemplateHelpers::escapeHtml($searchForm->render('end')) ?>


    <div id="dialog"></div><?php
}}

//
// end of blocks
//

if ($_cb->extends) { ob_start(); }

if (SnippetHelper::$outputAllowed) {
} if (!$_cb->extends) { call_user_func(reset($_cb->blocks['title']), get_defined_vars()); } ?>

<?php } if (!$_cb->extends) { call_user_func(reset($_cb->blocks['content']), get_defined_vars()); }  
}

if ($_cb->extends) { ob_end_clean(); LatteMacros::includeTemplate($_cb->extends, get_defined_vars(), $template)->render(); }
