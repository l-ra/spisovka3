/* global BASE_URL, PUBLIC_URL, linkNovySubjekt, kopirovatEmailDoPoznamky */

$(function () {

    $('#dialog-evidence').click(function (event) {
        dialog(this, 'Evidovat');
        return false;
    });

    $('#dialog-odmitnout').click(function (event) {
        dialog(this, 'Odmítnout zprávu');
        return false;
    });

    $('#subjekt_epod_autocomplete').autocomplete({
        minLength: 3,
        source: BASE_URL + 'subjekty/seznam-ajax',
        focus: function (event, ui) {
            return false;
        },
        select: function (event, ui) {
            $('#subjekt_epod_autocomplete').val('');

            renderEpodSubjekty(ui.item.id);

            return false;
        }
    });

    $('#subjekt_novy').on('click', '#epod_evid_novysubjekt_click', function (event) {
        dialog(this, 'Nový subjekt');
        return false;
    });


});

evidNovySubjektOk = function (data) {
    alert('Subjekt byl úspěšně vytvořen.');
    $('#dialog').dialog('close');
    renderEpodSubjekty(data.id);
};

zpravyNovySubjektOk = function (data) {
    alert('Subjekt byl úspěšně vytvořen.');
    $('#dialog').dialog('close');

    var id = data.extra_data;

    var checkbox = '<input type="checkbox" name="subjekt[' + data.id + ']" />';
    checkbox += data.name + '<br/>';
    $('#subjekt_seznam_' + id).append(checkbox);
};

renderEpodSubjekty = function (subjekt_id) {

    var url = BASE_URL + 'epodatelna/subjekty/nacti/' + subjekt_id;

    $.get(url, function (subjekt) {
        if ($('#subjekty-table').length == 0) {
            var html = '<table class="seznam" id="subjekty-table">';
            html = html + '    <tr>';
            html = html + '        <td colspan="4">Použít</td>';
            html = html + '    </tr>';
            html = html + subjekt;
            html = html + '</table>';
            $('#dok-subjekty').html(html);
        } else {
            // Musime se ujistit, ze nevybirame HTML z Ajax odpovedi, 
            // ale ze zobrazene stranky
            var subjekt_tr = $('#subjekty-table #subjekt-' + subjekt_id);
            // Pokud subjekt neni v seznamu u vytvareneho dokumentu, pridej jej
            // (kontrola zabrani, aby tentyz subjekt byl v seznamu vicekrat,
            // coz by zpusobilo problem s formularem)
            if (subjekt_tr.length == 0)
                $('#subjekty-table').append(subjekt);
        }
    });

    return false;
};

epodSubjektVybran = function (subjekt_id) {

    $('#dialog').dialog('close');
    renderEpodSubjekty(subjekt_id);
};

zkontrolovatSchranku = function (nacist_nove_zpravy) {

    // pro jistotu. Ošetři případ, že by byl parametr neuveden.
    if (typeof nacist_nove_zpravy === 'undefined')
        nacist_nove_zpravy = true;

    $('#zkontrolovat_status').html('<img src="' + PUBLIC_URL + 'images/spinner.gif" width="14" height="14" /> Kontroluji schránky ...');

    var url = BASE_URL + 'epodatelna/default/zkontrolovat-ajax';

    $.get(url, function (data) {
        $('#zkontrolovat_status').html(data);
        // nacteni novych zprav z database se musi provest az po stazeni vsech zprav z emailove schranky
        if (nacist_nove_zpravy)
            nactiZpravy();
        else
            $('#zkontrolovat_status').append('<br /><input type="button" value="Načíst nové zprávy"\n\
                onclick="' + "nactiZpravy(); $('#zkontrolovat_status').hide()" + '" />');
    }).fail(function (data) {
        $('#zkontrolovat_status').html('Při kontrole zpráv došlo k chybě.');
    });
};


nactiZpravy = function () {

    var attachHandlers = function () {

        var loadAttachments = function () {
            var anchor = $(this);
            $.getJSON(this.href, function (attachments) {
                var id = anchor.attr('id');
                id = id.substring(3);
                var url = BASE_URL + 'epodatelna/prilohy/download/' + id + '?file=';
                var a = attachments;
                var s = '';

                for (var i = 0; i < a.length; i++) {
                    s = s + '<li><a href="' + url + a[i].id + '">' + a[i].name
                            + '</a>  (' + bytesToSize(a[i].size) + ')</li>';
                }
                anchor.replaceWith(s);
            });
            return false;
        };

        var submitHandler = function () {

            var id = $(this).data('id');
            var formdata = 'id=' + id + '&' + $('#form' + id).serialize();

            $.post($('#h_evidence').data('action'), formdata, function (text) {
                if (text[0] == "#") {
                    text = text.substr(3);
                    alert(text);
                } else {
                    text = '<div class="evidence_report">' + text + '</div>';
                    $('#evidence_show_' + id).html(text);
                }
            });

            return false;
        };

        var formSwitcher = function () {

            var id = $(this).parent().data('id');
            var value = $(this).val();

            $('#evidence_form_evidovat_' + id).hide();
            $('#evidence_form_jina_evidence_' + id).hide();
            $('#evidence_form_odmitnout_' + id).hide();

            if (value == 0) {
            } else if (value == 1) {
                $('#evidence_form_evidovat_' + id).show();
            } else if (value == 2) {
                $('#evidence_form_jina_evidence_' + id).show();
            } else if (value == 3) {
                $('#evidence_form_odmitnout_' + id).show();
            }
        };

        $('a.load-attachments').click(loadAttachments);

        $('input[type=submit]').click(submitHandler);

        $("input[name^=volba_evidence]").click(formSwitcher);

        $('.evidence_zprava_toggler').click(function () {
            var id = $(this).data('id');
            $('#evidence_zprava_' + id).toggle();
        });

        $('input[name=subjekt_autocomplete]').autocomplete({
            minLength: 3,
            source: BASE_URL + 'subjekty/seznam-ajax',
            focus: function (event, ui) {
                return false;
            },
            select: function (event, ui) {
                $(this).val('');

                subjekt_seznam = '<input type="checkbox" name="subjekt[' + ui.item.id + ']" />';
                subjekt_seznam = subjekt_seznam + ui.item.value + '<br/>';
                $('#subjekt_seznam_' + $(this).data('id')).append(subjekt_seznam);

                return false;
            }
        });

    };

    var url = BASE_URL + 'epodatelna/default/nacti-nove-ajax';
    $.getJSON(url, function (zpravy) {
        if (zpravy != '') {
            var len = zpravy.length;
            for (var i = 0; i < len; i++)
                generujZpravu(zpravy[i]);

            attachHandlers();
        }
    });
};

bytesToSize = function (bytes) {
    var sizes = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    if (bytes == 0)
        return 'n/a';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    var s = (bytes / Math.pow(1024, i)).toFixed(i > 1 ? 1 : 0);
    return s.replace('.', ',') + ' ' + sizes[i];
};


generujZpravu = function (data) {

    var id = data.id;
    var typ = 0;
    var typ_string, form_odmitnout;

    if (data['typ'] == "E") {
        typ = 1;
        typ_string = '<img src="' + PUBLIC_URL + 'images/icons/email.png" alt="Email" title="Email" width="24" height="16" />';

        form_odmitnout = '                    <dl>' +
                '                        <dt></dt>' +
                '                        <dd><input type="checkbox" name="odmitnout" /> Poslat upozornění odesilateli?</dd>' +
                '                    </dl>' +
                '                    <dl>' +
                '                        <dt>Email odesílatele:</dt>' +
                '                        <dd><input type="text" name="zprava_email" value="' + data['odesilatel'] + '" size="60" /></dd>' +
                '                    </dl>' +
                '                    <dl>' +
                '                        <dt>Předmět pro odesílatele:</dt>' +
                '                        <dd><input type="text" name="zprava_predmet" value="RE: ' + data['predmet'] + '" size="60" /></dd>' +
                '                    </dl>' +
                '                    <dl>' +
                '                        <dt>Zpráva pro odesilatele:</dt>' +
                '                        <dd><textarea name="zprava_odmitnuti" rows="3" cols="60"></textarea></dd>' +
                '                    </dl>';

    } else if (data['typ'] == "I") {
        typ = 2;
        typ_string = '<img src="' + PUBLIC_URL + 'images/icons/isds.png" alt="ISDS" title="ISDS" width="24" height="16" />';
        form_odmitnout = '';
    } else {
        typ_string = '';
        form_odmitnout = '';
    }

    var prilohy = '';
    if (data['prilohy'].length > 0) {
        for (var key in data['prilohy']) {
            prilohy = prilohy + '                    <li><a href="' + BASE_URL + 'epodatelna/prilohy/download/' + id + '?file=' + data['prilohy'][key]['id'] + '">' + data['prilohy'][key]['name'] + '</a> [ ' + bytesToSize(data['prilohy'][key]['size']) + ' ]</li>';
        }
    } else {
        var url = BASE_URL + 'epodatelna/prilohy/attachments/' + id;
        prilohy = '<a href="' + url + '" class="load-attachments" id="la-' + id + '">Načíst</a>';
    }

    var subjekt_seznam = '';
    if (typeof data['subjekt']['databaze'] != 'null') {
        for (var key in data['subjekt']['databaze']) {
            subjekt_seznam = subjekt_seznam + '<input type="checkbox" name="subjekt[' + data['subjekt']['databaze'][key]['id'] + ']" />';
            subjekt_seznam = subjekt_seznam + data['subjekt']['databaze'][key]['full_name'] + '<br/>';
        }
    }

    var zprava = '<form id="form' + id + '">' +
            '   <div class="evidence_item"> ' +
            '       <div class="evidence_item_header">' +
            '           ' + typ_string +
            '           <div class="evidence_item_cas">' +
            '               ' + data['doruceno_dne_datum'] + '<br/>' + data['doruceno_dne_cas'] + '' +
            '           </div>' +
            '           <div class="evidence_info">' +
            '               <span class="nazev">' + data['predmet'] + '</span>' +
            '               ' + data['odesilatel'] + '' +
            '           </div>' +
            '           <br class="clear" />' +
            '       </div>' +
            '       <div class="evidence_item_proccess" id="evidence_show_' + id + '">' +
            '           <div class="evidence_volba" data-id="' + id + '">' +
            '                Volba evidence:<br />' +
            '                <input type="radio" name="volba_evidence" value="0" title="Tato zpráva zůstane nezpracována. Bude zpracována později." /> Nedělat nic<br/>' +
            '                <input type="radio" name="volba_evidence" value="1" title="Zaeviduje zprávu do spisové služby."/> Evidovat<br/>' +
            '                <input type="radio" name="volba_evidence" value="2" title="Zaeviduje zprávu do jiné evidence. Nebude součásti spisové služby."/> Evidovat do jiné evidence<br/>' +
            '                <input type="radio" name="volba_evidence" value="3" title="Zpráva bude odmítnuta."/> Odmítnout<br/>' +
            '            </div>' +
            '            <div class="evidence_form">' +
            '                <div class="evidence_zprava_toggler" data-id="' + id + '">Zobrazit zprávu >></div>' +
            '                <div class="evidence_zprava" id="evidence_zprava_' + id + '">' +
            data['popis'] +
            '                </div>' +
            '                <span>Přílohy:</span>' +
            '                <ul id="evidence_prilohy_' + id + '">' +
            prilohy +
            '                </ul>' +
            '                <div id="evidence_form_evidovat_' + id + '">' +
            '                    <span>Subjekt:</span>' +
            '                    <dl>' +
            '                        <dt>Nalezené subjekty:</dt>' +
            '                        <dd id="subjekt_seznam_' + id + '">' +
            subjekt_seznam +
            '                        </dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt>Hledat subjekt:</dt>' +
            '                        <dd class="ui-widget">' +
            '                           <input type="text" name="subjekt_autocomplete" data-id="' + id + '" size="60" />' +
            '                        </dd>' +
            '                    </dl>' +
            '                    <dl id="subjekt_novy_' + id + '">' +
            '                        <dt></dt>' +
            '                        <dd><a href="' + linkNovySubjekt + '" id="novysubjekt_click_' + id + '">Vytvořit nový subjekt z odesílatele</a></dd>' +
            '                    </dl>' +
            '                    <span>Zaevidovat do spisové služby.</span>' +
            '                    <dl>' +
            '                        <dt>Věc:</dt>' +
            '                        <dd><input type="text" name="nazev" value="' + data['predmet'] + '" size="60" /></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt>Popis:</dt>' +
            '                        <dd><textarea name="popis" rows="3" cols="60"></textarea></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt>Poznámka:</dt>' +
            '                        <dd><textarea name="poznamka" rows="5" cols="60">' + '</textarea></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt>Předat:</dt>' +
            '                        <dd class="ui-widget">' +
            '                           <input type="text" name="predat_autocomplete" id="predat_autocomplete_' + id + '" size="60" />' +
            '                           <input type="hidden" name="predat" id="predat_' + id + '" />' +
            '                        </dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt>Poznámka k předání:</dt>' +
            '                        <dd><textarea name="predani_poznamka" rows="3" cols="60"></textarea></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt></dt>' +
            '                        <dd><input type="submit" name="evidovat" value="Zaevidovat tuto zprávu" data-id="' + id + '" /></dd>' +
            '                    </dl>' +
            '                </div>' +
            '                <div id="evidence_form_jina_evidence_' + id + '">' +
            '                    <span>Zaevidovat zprávu do jiné evidence.</span>' +
            '                    <dl>' +
            '                        <dt>Evidence:</dt>' +
            '                        <dd><textarea name="evidence" rows="3" cols="60"></textarea></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt></dt>' +
            '                        <dd><input type="submit" name="evidovat_jinam" value="Zaevidovat tuto zprávu do jiné evidence" data-id="' + id + '" /></dd>' +
            '                    </dl>' +
            '                </div>' +
            '                <div id="evidence_form_odmitnout_' + id + '">' +
            '                    <span>Odmítnout zprávu.</span>' +
            '                    <dl>' +
            '                        <dt>Důvod odmítnutí:</dt>' +
            '                        <dd><textarea name="duvod_odmitnuti" rows="3" cols="60"></textarea></dd>' +
            '                    </dl>' +
            form_odmitnout +
            '                    <dl>' +
            '                        <dt></dt>' +
            '                        <dd>' +
            '                           <input type="hidden" name="odmitnout_typ" value="' + typ + '" />' +
            '                           <input type="submit" name="odmitnout" value="Odmítnout tuto zprávu" data-id="' + id + '" />' +
            '                        </dd>' +
            '                    </dl>' +
            '                </div>' +
            '            </div>' +
            '            <br class="clear" />' +
            '        </div>' +
            '        <br class="clear" />' +
            '    </div>' +
            '</form>';

    $('#h_evidence').append(zprava);
    if (data.typ == 'I' || kopirovatEmailDoPoznamky)
        $('textarea[name="poznamka"]').html(data['popis']);

    $('#predat_autocomplete_' + id).autocomplete({
        minLength: 3,
        source: BASE_URL + 'uzivatel/seznam-ajax',
        focus: function (event, ui) {
            return false;
        },
        select: function (event, ui) {
            $('#predat_' + id).val(ui.item.id);
        }
    });


    // $.parseJSON(postdata);
    var postdata = JSON.stringify(data['subjekt']['original']);

    $('#novysubjekt_click_' + id).click(function (event) {
        dialog(this, 'Nový subjekt', $(this).attr('href') + '&extra_data=' + id);
        return false;
    }).attr('data-postdata', postdata);

    $('#evidence_form_evidovat_' + id).hide();
    $('#evidence_form_jina_evidence_' + id).hide();
    $('#evidence_form_odmitnout_' + id).hide();
    $('#evidence_zprava_' + id).hide();

};

