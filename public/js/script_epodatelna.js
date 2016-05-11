/* global BASE_URL, PUBLIC_URL, linkNovySubjekt */

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

    var checkbox = '<input type="checkbox" name="subjekt[' + id + '][' + data.id + ']" />';
    checkbox += data.name + '<br/>';
    $('#subjekt_seznam_' + id).append(checkbox);
};

renderEpodSubjekty = function (subjekt_id) {

    url = BASE_URL + 'epodatelna/subjekty/nacti/' + subjekt_id;

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

epodSubjektVybran = function (elm, subjekt_id) {

    $('#dialog').dialog('close');
    elm.href = "javaScript:void(0);"; // IE fix - zabraneni nacteni odkazu
    renderEpodSubjekty(subjekt_id);
};

zkontrolovatSchranku = function (nacist_nove_zpravy) {

    // pro jistotu. Ošetři případ, že by byl parametr neuveden.
    if (typeof nacist_nove_zpravy === 'undefined')
        nacist_nove_zpravy = true;

    $('#zkontrolovat_status').html('<img src="' + PUBLIC_URL + 'images/spinner.gif" width="14" height="14" /> Kontroluji schránky ...');

    url = BASE_URL + 'epodatelna/default/zkontrolovat-ajax';

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

    var url = BASE_URL + 'epodatelna/default/nacti-nove-ajax';
    $.getJSON(url, function (zpravy) {
        if (zpravy != '') {
            var len = zpravy.length;
            for (var i = 0; i < len; i++)
                generujZpravu(zpravy[i]);

            $('a.load-attachments').click(loadAttachments);
        }
    });
};

loadAttachments = function () {
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

function in_array(needle, haystack)
{
    var key = '';

    for (key in haystack) {
        if (haystack[key] == needle) {
            return true;
        }
    }
    return false;
}
;

function bytesToSize(bytes) {
    var sizes = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    if (bytes == 0)
        return 'n/a';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    var s = (bytes / Math.pow(1024, i)).toFixed(i > 1 ? 1 : 0);
    return s.replace('.', ',') + ' ' + sizes[i];
}
;

generujZpravu = function (data) {

    var id = data.id;
    var typ = 0;
    var typ_string, form_odmitnout;

    if (data['typ'] == "E") {
        typ = 1;
        typ_string = '<img src="' + PUBLIC_URL + 'images/icons/email.png" alt="Email" title="Email" width="24" height="16" />';

        form_odmitnout = '                    <dl>' +
                '                        <dt></dt>' +
                '                        <dd><input type="checkbox" name="odmitnout[' + id + ']" /> Poslat upozornění odesilateli?</dd>' +
                '                    </dl>' +
                '                    <dl>' +
                '                        <dt>Email odesílatele:</dt>' +
                '                        <dd><input type="text" name="zprava_email[' + id + ']" value="' + data['odesilatel'] + '" size="60" /></dd>' +
                '                    </dl>' +
                '                    <dl>' +
                '                        <dt>Předmět pro odesílatele:</dt>' +
                '                        <dd><input type="text" name="zprava_predmet[' + id + ']" value="RE: ' + data['predmet'] + '" size="60" /></dd>' +
                '                    </dl>' +
                '                    <dl>' +
                '                        <dt>Zpráva pro odesilatele:</dt>' +
                '                        <dd><textarea name="zprava_odmitnuti[' + id + ']" rows="3" cols="60"></textarea></dd>' +
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
            subjekt_seznam = subjekt_seznam + '<input type="checkbox" name="subjekt[' + id + '][' + data['subjekt']['databaze'][key]['id'] + ']" />';
            subjekt_seznam = subjekt_seznam + data['subjekt']['databaze'][key]['full_name'] + '<br/>';
        }
    }

    var zprava = '   <div class="evidence_item"> ' +
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
            '           <div class="evidence_volba">' +
            '                Volba evidence:<br />' +
            '                <input type="radio" name="volba_evidence[' + id + ']" value="0" title="Tato zpráva zůstane nezpracována. Bude zpracována později." /> Nedělat nic<br/>' +
            '                <input type="radio" name="volba_evidence[' + id + ']" value="1" title="Zaeviduje zprávu do spisové služby."/> Evidovat<br/>' +
            '                <input type="radio" name="volba_evidence[' + id + ']" value="2" title="Zaeviduje zprávu do jiné evidence. Nebude součásti spisové služby."/> Evidovat do jiné evidence<br/>' +
            '                <input type="radio" name="volba_evidence[' + id + ']" value="3" title="Zpráva bude odmítnuta."/> Odmítnout<br/>' +
            '            </div>' +
            '            <div class="evidence_form">' +
            '                <div class="evidence_zprava_toogle" id="evidence_zprava_toogle_' + id + '">Zobrazit zprávu >></div>' +
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
            '                           <input type="text" name="subjekt_autocomplete[' + id + ']" id="subjekt_autocomplete_' + id + '" size="60" />' +
            '                        </dd>' +
            '                    </dl>' +
            '                    <dl id="subjekt_novy_' + id + '">' +
            '                        <dt></dt>' +
            '                        <dd><a href="' + linkNovySubjekt + '" id="novysubjekt_click_' + id + '">Vytvořit nový subjekt z odesílatele</a></dd>' +
            '                    </dl>' +
            '                    <span>Zaevidovat do spisové služby.</span>' +
            '                    <dl>' +
            '                        <dt>Věc:</dt>' +
            '                        <dd><input type="text" name="vec[' + id + ']" value="' + data['predmet'] + '" size="60" /></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt>Popis:</dt>' +
            '                        <dd><textarea name="popis[' + id + ']" rows="3" cols="60"></textarea></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt>Poznámka:</dt>' +
            '                        <dd><textarea name="poznamka[' + id + ']" rows="5" cols="60">' + '</textarea></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt>Předat:</dt>' +
            '                        <dd class="ui-widget">' +
            '                           <input type="text" name="predat_autocomplete[' + id + ']" id="predat_autocomplete_' + id + '" size="60" />' +
            '                           <input type="hidden" name="predat[' + id + ']" id="predat_' + id + '" />' +
            '                        </dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt>Poznámka k předání:</dt>' +
            '                        <dd><textarea name="predat_poznamka[' + id + ']" rows="3" cols="60"></textarea></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt></dt>' +
            '                        <dd><input type="submit" name="evidovat[' + id + ']" value="Zaevidovat tuto zprávu" id="submit_evidovat_' + id + '" /></dd>' +
            '                    </dl>' +
            '                </div>' +
            '                <div id="evidence_form_jina_evidence_' + id + '">' +
            '                    <span>Zaevidovat zprávu do jiné evidence.</span>' +
            '                    <dl>' +
            '                        <dt>Evidence:</dt>' +
            '                        <dd><textarea name="evidence[' + id + ']" rows="3" cols="60"></textarea></dd>' +
            '                    </dl>' +
            '                    <dl>' +
            '                        <dt></dt>' +
            '                        <dd><input type="submit" name="evidovat_jinam[' + id + ']" value="Zaevidovat tuto zprávu do jiné evidence" id="submit_evidovat_jinam_' + id + '" /></dd>' +
            '                    </dl>' +
            '                </div>' +
            '                <div id="evidence_form_odmitnout_' + id + '">' +
            '                    <span>Odmítnout zprávu.</span>' +
            '                    <dl>' +
            '                        <dt>Důvod odmítnutí:</dt>' +
            '                        <dd><textarea name="duvod_odmitnuti[' + id + ']" rows="3" cols="60"></textarea></dd>' +
            '                    </dl>' +
            form_odmitnout +
            '                    <dl>' +
            '                        <dt></dt>' +
            '                        <dd>' +
            '                           <input type="hidden" name="odmitnout_typ[' + id + ']" value="' + typ + '" />' +
            '                           <input type="submit" name="odmitnout[' + id + ']" value="Odmítnout tuto zprávu" id="submit_odmitnout_' + id + '" />' +
            '                        </dd>' +
            '                    </dl>' +
            '                </div>' +
            '            </div>' +
            '            <br class="clear" />' +
            '        </div>' +
            '        <br class="clear" />' +
            '    </div>';

    $('#h_evidence').append(zprava);
    if (data.typ == 'I' || kopirovatEmailDoPoznamky)
        $('textarea[name="poznamka[' + id + ']"]').html(data['popis']);

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

    $('#subjekt_autocomplete_' + id).autocomplete({
        minLength: 3,
        source: BASE_URL + 'subjekty/seznam-ajax',
        focus: function (event, ui) {
            return false;
        },
        select: function (event, ui) {
            //$('#subjekt_'+id).val(ui.item.id);
            $('#subjekt_autocomplete_' + id).val('');

            subjekt_seznam = '<input type="checkbox" name="subjekt[' + id + '][' + ui.item.id + ']" />';
            subjekt_seznam = subjekt_seznam + ui.item.value + '<br/>';
            $('#subjekt_seznam_' + id).append(subjekt_seznam);

            return false;
        }
    });

    // $.parseJSON(postdata);
    var postdata = JSON.stringify(data['subjekt']['original']);

    $('#novysubjekt_click_' + id).click(function (event) {
        dialog(this, 'Nový subjekt', $(this).attr('href') + '&extra_data=' + id);
        return false;
    }).attr('data-postdata', postdata);

    $('#evidence_form_evidovat_' + id).css('display', 'none');
    $('#evidence_form_jina_evidence_' + id).css('display', 'none');
    $('#evidence_form_odmitnout_' + id).css('display', 'none');
    $('#evidence_zprava_' + id).toggle();
    $('#evidence_zprava_toogle_' + id).click(function () {
        $('#evidence_zprava_' + id).toggle();
    });

    $("input[name^=volba_evidence]").click(function () {

        var element_id = $(this).attr('name');
        var value = $(this).val();
        //the initial starting point of the substring is based on "myboxes["
        var id = element_id.substring(15, element_id.length - 1);

        $('#evidence_form_evidovat_' + id).css('display', 'none');
        $('#evidence_form_jina_evidence_' + id).css('display', 'none');
        $('#evidence_form_odmitnout_' + id).css('display', 'none');

        if (value == 0) {
        } else if (value == 1) {
            $('#evidence_form_evidovat_' + id).css('display', 'block');
        } else if (value == 2) {
            $('#evidence_form_jina_evidence_' + id).css('display', 'block');
        } else if (value == 3) {
            $('#evidence_form_odmitnout_' + id).css('display', 'block');
        }
    });

    evidenceFormHandler = function () {

        var formdata = 'id=' + id + '&' + $('#h_evidence').serialize();

        $.post($('#h_evidence').attr('action'), formdata, function (text) {
            if (text[0] == "#") {
                text = text.substr(3);
                alert(text);
            } else {
                $('#evidence_show_' + id).html(text);
            }
        });

        return false;
    };

    $("#submit_evidovat_" + id).click(evidenceFormHandler);

    // handler je totozny, jak v predeslem pripade
    $("#submit_evidovat_jinam_" + id).click(evidenceFormHandler);

    $("#submit_odmitnout_" + id).click(evidenceFormHandler);

};
