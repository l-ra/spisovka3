/* global BASE_URL, DOKUMENT_ID, typ_dokumentu_id, smer_typu_dokumentu, Nette */

var stop_timer = 0;
var url;
var cache = Array();


$(function () {

    installDatePicker();
    initSelect2();

    /**
     * AJAX
     */

    // Nastaveni spinneru. Je vzdy pritomen, standardne ale je schovan.
    $('<div id="ajax-spinner"></div>').appendTo("body").hide();

    $(document).on('click', 'a.ajax', ajaxAnchor);
    $(document).on('click', 'a.ajax-dialog', ajaxDialog);

    $(document).ajaxStart(function () {
        $("#ajax-spinner").show();
    });
    $(document).ajaxStop(function () {
        // při události ajaxStop spinner schovám
        $("#ajax-spinner").hide();
    });

    $(document).ajaxError(function (event, jqXHR, ajaxSettings, thrownError) {
        // Při spouštění plánovače nečekáme na odpověď
        if (ajaxSettings.url.indexOf('/cron/') != -1)
            return;

        // při chybě též spinner schovám, protože jQuery zdá se nevyvolá událost stop
        $("#ajax-spinner").hide();

        // Pozadavek muze byt zrusen bud skriptem nette.ajax.js nebo kdyz uzivatel
        // naviguje na jinou stranku, nez dele bezici pozadavek dokonci
        if (thrownError == 'canceled')
            return;

        var msg = 'Je nám líto, asynchronní požadavek skončil s chybou:\n' + thrownError;
        if (jqXHR.responseText
                && $("#dialog").dialog("isOpen") === false)
            msg = msg + '\n\n' + jqXHR.responseText;

        alert(msg);

        if (jqXHR.responseText
                && $("#dialog").dialog("isOpen") === true) {
            $('#dialog').html('<pre>' + jqXHR.responseText + '</pre>');
            dialogScrollUp();
        }
    });

    // Dialog
    $('#dialog').dialog({
        autoOpen: false,
        width: 800,
        height: 500,
        modal: true
    });

    $('#napoveda').dialog({
        autoOpen: false,
        width: 900,
        height: 700,
        modal: true
    });

    $('#dialog-uzivatel').click(function (event) {
        return dialog(this, 'Předat dokument organizační jednotce nebo zaměstnanci');
    });

    $('#subjekt_pripojit_click').click(function (event) {
        $('#subjekt_pripojit').show();
        $('#subjekt_autocomplete').focus();
        return false;
    });

    $('#dialog').on('submit', '#frm-searchForm', function (event) {
        postFormJ(this, function (data) {
            if (data.redirect)
                window.location.href = data.redirect;
            else {
                $('#dialog').html(data);
                dialogScrollUp();
            }
        });
        return false;
    });

    $('#predat_autocomplete').autocomplete({
        minLength: 3,
        /*source: seznam_uzivatelu,*/
        source: BASE_URL + 'uzivatel/seznam-ajax',
        focus: function (event, ui) {
            return false;
        },
        select: function (event, ui) {
            $('#predat_autocomplete').val('');
            if (ui.item.id.substring(0, 1) == "u") {
                $('input[name=predano_user]').val(ui.item.id.substr(1));
                $('input[name=predano_org]').val('');
                $('#predano').html("<dl><dt>Předán:</dt><dd>" + ui.item.nazev + "</dd></dl>");
            } else if (ui.item.id.substring(0, 1) == "o") {
                $('input[name=predano_user]').val('');
                $('input[name=predano_org]').val(ui.item.id.substr(1));
                $('#predano').html("<dl><dt>Předán:</dt><dd>organizační jednotce<br />" + ui.item.nazev + "</dd></dl>");
            }
        }
    });
    $("#predat_autocomplete").keypress(function (event) {
        if (event.which == 13) {
            event.preventDefault();
        }
    });


    $('#subjekt_autocomplete').autocomplete({
        minLength: 3,
        source: BASE_URL + 'subjekty/seznam-ajax',
        focus: function (event, ui) {
            return false;
        },
        select: function (event, ui) {
            $(this).val('');

            // Nasledujici kod zajistuje automaticke urceni typu pripojeni subjektu
            var typ_id = $('#frmnovyForm-dokument_typ_id').val();
            if (typeof typ_id == 'undefined')
                typ_id = $('#frmmetadataForm-dokument_typ_id').val();
            if (typeof typ_id == 'undefined')
                typ_id = $('#frmodpovedForm-dokument_typ_id').val();
            if (typeof typ_id == 'undefined')
                typ_id = typ_dokumentu_id;

            var typ_code;
            if (typeof typ_id != 'undefined'
                    && typeof smer_typu_dokumentu != 'undefined')
                typ_code = smer_typu_dokumentu[typ_id] == 0 ? 'O' : 'A';
            else {
                // V sablone chybi informace o typech dokumentu
                // window.alert('chyba programu');
                typ_code = 'AO';
            }

            url = BASE_URL + 'subjekty/' + ui.item.id + '/vybrano?dok_id=' + DOKUMENT_ID + '&typ=' + typ_code + '&autocomplete=1';

            $.get(url, function (data) {
                if (data.indexOf('###vybrano###') != -1) {
                    renderSubjekty();
                } else {
                    alert(data);
                }
            });

            return false;
        }
    });
    $("#subjekt_autocomplete").keypress(function (event) {
        if (event.which == 13) {
            event.preventDefault();
        }
    });
});

/*
 *  Vyvolani dialogu
 *
 *
 */
dialog = function (elm, title, url) {

    // jQuery UI bug - je-li title prázdný, rozhodí se layout dialogu
    if (typeof title == 'null' || title == '')
        title = 'Dialogové okno';

    $('#dialog').dialog("option", "title", title);
    $('#dialog').html('');
    $('#dialog').dialog('open');

    var postdata = false;
    if (elm) {
        if (!(elm instanceof jQuery))
            elm = $(elm);

        postdata = elm.attr('data-postdata');
        if (postdata)
            postdata = $.parseJSON(postdata);
    }
    if (typeof url == 'undefined')
        url = elm.attr('href');

    var jqXHR;
    var successFunction = function (data) {
        $('#dialog').html(data);
    };
    if (postdata)
        jqXHR = $.post(url, $.param(postdata), successFunction);
    else
        jqXHR = $.get(url, successFunction);

    jqXHR.fail(function (jqXHR) {
        // Následující akce nyní provádí globální handler:
        // alert('Při načítání obsahu okna došlo k vážné chybě.');
        // $('#dialog').html('<pre>' + jqXHR.responseText);
    });

    return false;
};

reloadDialog = function (elm) {

    $.get(elm.href, function (data) {
        $('#dialog').html(data);
    });

    return false;
};

closeDialog = function () {

    $('#dialog').dialog('close');
    return false;
};

dialogScrollUp = function () {

    $("#dialog").scrollTop("0");
};

/*
 * ARES
 *
 */
aresSubjekt = function (link) {

    var inputEl = $(link).prev();
    var IC = inputEl.val();
    if (!IC) {
        alert('Vyplňte IČ subjektu.');
        return false;
    }
    var inputSelector = inputEl.attr('id');
    inputSelector = '#' + inputSelector.replace('-ic', '');

    var url = BASE_URL + 'subjekty/ares?ic=' + IC;

    $.getJSON(url, function (data) {
        if (data.error) {
            alert(data.error);
        } else {
            $(inputSelector + "-ic").val(data.ico);
            $(inputSelector + "-dic").val(data.dic);
            $(inputSelector + "-nazev_subjektu").val(data.nazev);
            $(inputSelector + "-adresa_ulice").val(data.ulice);
            $(inputSelector + "-adresa_cp").val(data.cislo_popisne);
            $(inputSelector + "-adresa_co").val(data.cislo_orientacni);
            $(inputSelector + "-adresa_mesto").val(data.mesto);
            $(inputSelector + "-adresa_psc").val(data.psc);
            $(inputSelector + "-adresa_stat").val('CZE');
            $(inputSelector + "-stat_narozeni").val('CZE');
        }
    });

    return false;
};

/*
 * ISDS - vyhledat subjekt na zaklade id  schranky
 *
 */
isdsSubjekt = function (link) {

    var inputEl = $(link).prev();
    var ID = inputEl.val();
    if (!ID) {
        alert('Zadejte ID datové schránky.');
        return false;
    }
    var inputSelector = inputEl.attr('id');
    inputSelector = '#' + inputSelector.replace('-id_isds', '');

    var url = BASE_URL + 'subjekty/isds?box=' + ID;

    $.getJSON(url, function (data) {

        if (data == null) {
            alert('Záznam neexistuje nebo bylo zadáno chybné ID datové schránky.');
        } else if (data.error) {
            alert(data.error);
        } else {

            $(inputSelector + "-type option[value=" + data.dbType + "]").prop('selected', true);

            $(inputSelector + "-ic").val(data.ic);
            $(inputSelector + "-nazev_subjektu").val(data.firmName);
            $(inputSelector + "-jmeno").val(data.pnFirstName);
            $(inputSelector + "-prostredni_jmeno").val(data.pnMiddleName);
            $(inputSelector + "-prijmeni").val(data.pnLastName);
            $(inputSelector + "-adresa_ulice").val(data.adStreet);
            $(inputSelector + "-adresa_cp").val(data.adNumberInMunicipality);
            $(inputSelector + "-adresa_co").val(data.adNumberInStreet);
            $(inputSelector + "-adresa_mesto").val(data.adCity);
            $(inputSelector + "-adresa_psc").val(data.adZipCode);
        }
    });

    return false;
};

/*
 * CRON - zpracovani na pozadi
 *
 */
ajaxcron = function () {

    if (Math.random() > 0.1)
        return;

    var url = BASE_URL + 'cron/spustit';

    $.ajax({
        url: url,
        timeout: 1000
    });
};



toggleWindow = function (elm) {

    //parent = $(elm).parent();

    //alert(parent.prop('tagName'));

    return false;

};

spisZobrazit = function (spis_id) {
    window.location.href = BASE_URL + 'spisy/' + spis_id + '/';
};
spisZobrazitSpisovna = function (spis_id) {
    window.location.href = BASE_URL + 'spisovna/spisy/' + spis_id + '/';
};
spisZobrazitAdministrace = function (spis_id) {
    window.location.href = BASE_URL + 'admin/spisy/detail/' + spis_id + '/';
};

spisVlozitDokument = function (spis_id) {

    var href = BASE_URL + 'spisy/' + spis_id + '/vlozit-dokument?dok_id=' + DOKUMENT_ID;

    $.get(href, function (spis) {
        $('#dok-spis-nazev').html(spis.nazev);
        $('#dok-spis-vyjmout').show();
        closeDialog();
    });

    return false;
};

spisVyjmoutDokument = function () {

    if (confirm('Opravdu chcete vyjmout tento dokument ze spisu?')) {
        var url = BASE_URL + 'spisy/vyjmout-dokument?dok_id=' + DOKUMENT_ID;

        $.getJSON(url, function (data) {
            if (data.ok) {
                $('#dok-spis-nazev').html('nezařazen do žádného spisu');
                $('#dok-spis-vyjmout').hide();
            }
        });
    }

    return false;
};

subjektVybran = function (elm) {

    var url = elm.href + '&dok_id=' + DOKUMENT_ID;

    $.get(url, function (data) {
        if (data.indexOf('###vybrano###') != -1) {
            closeDialog();
            renderSubjekty();
        } else {
            alert(data);
        }
    });

    return false;
};


renderPrilohy = function () {

    url = BASE_URL + 'prilohy/' + DOKUMENT_ID + '/nacti';

    $.get(url, function (data) {
        $('#dok-prilohy').html(data);
    });

    return false;
};

renderSubjekty = function () {

    url = BASE_URL + 'subjekty/' + DOKUMENT_ID + '/nacti';

    $.get(url, function (data) {
        $('#dok-subjekty').html(data);
    });

    return false;
};

subjektUpravitSubmit = function () {
    postFormJ($("#subjekt-vytvorit"), function (data) {
        if (data.indexOf('###zmeneno###') != -1) {
            closeDialog();
            renderSubjekty();
        } else {
            // chyba
            $('#dialog').html(data);
            dialogScrollUp();
        }
    });
    return false;
};

handleNovySubjekt = function (okFunc) {
    postFormJ($("#subjekt-vytvorit"), function (data) {
        if (typeof data == "object") {
            if (data.status == "OK") {
                okFunc(data);
            } else
                alert(data.status);
        } else {
            // formular neprosel validaci
            $('#dialog').html(data);
            dialogScrollUp();
        }
    });
    return false;

};

novySubjektOk = function () {
    // Neobtezuj uzivatele
    // alert('Subjekt byl úspěšně vytvořen a přidán.');
    closeDialog();
    renderSubjekty();
};

spisVytvoritSubmit = function (form) {

    postFormJ(form, function (data) {
        if (typeof data == 'object') {
            if (data.status == "OK")
                spisVlozitDokument(data.id);
        }
        else {
            $('#dialog').html(data);
            dialogScrollUp();
        }
    });

    return false;
};

osobaVybrana = function (elm) {

    url = elm.href;
    poznamka = $('#frmpred-poznamka').val();
    if (poznamka) {
        url = url + '&poznamka=' + encodeURI(poznamka);
    }

    $.get(url, function (data) {
        if (data.indexOf('###vybrano###') != -1) {
            data = data.replace('###vybrano###', '');
            closeDialog();
            location.href = data;
        } else if (data.indexOf('###predano###') != -1) {
            data = data.replace('###predano###', '');
            part = data.split('#');
            $('#frmnovyForm-predano_user').val(part[1]);
            $('#frmnovyForm-predano_org').val(part[2]);
            $('#predano').html("<dl><dt>Předán:</dt><dd>" + part[3] + "</dd></dl>");
            closeDialog();
        } else {
            $('#dialog').html(data);
            dialogScrollUp();
        }
    });

    return false;
};


odebratPrilohu = function (elm) {

    if (confirm('Opravdu chcete odebrat přílohu z dokumentu?')) {

        $.get(elm.href, function (data) {
            renderPrilohy();
        });
    }

    return false;
};

odebratSubjekt = function (elm) {

    if (confirm('Opravdu chcete odebrat subjekt z dokumentu?')) {

        $.get(elm.href, function (data) {
            renderSubjekty();
        });
    }

    return false;
};

vyber_odes_form = function (elm, subjekt_id) {

    var volba = elm.options[elm.selectedIndex].value;

    odes_form_reset(subjekt_id);

    $('#odes_' + subjekt_id + '_' + volba).show();


    return false;

};

odes_form_reset = function (subjekt_id) {

    if (typeof subjekt_id != "undefined") {
        $('#odes_' + subjekt_id + '_1').hide();
        $('#odes_' + subjekt_id + '_2').hide();
        $('#odes_' + subjekt_id + '_3').hide();
        $('#odes_' + subjekt_id + '_4').hide();
    } else {
        $('.odes_form').hide();
    }


};

function renderVysledekHledaniDokumentu(data, typ) {

    var html = "<table class='seznam hledani-dokumentu'>";
    html = html + "<tr>";
    html = html + "<th class='cislo-jednaci'>číslo jednací</th>";
    html = html + "<th class='jid'>JID</th>";
    html = html + "<th>věc</th>";
    html = html + "</tr>";

    dokument_id = document.getElementById('dokumentid').value;
    evidence = document.getElementById('evidence');
    if (evidence == null) {
        evidence = 0;
    } else {
        evidence = evidence.value;
    }

    if (typ == 1) {
        var url = BASE_URL + 'dokumenty/' + dokument_id + '/vlozitdosbernehoarchu?vlozit_do=';
        var fnc = "pripojitDokument(this)";
    } else {
        var url = BASE_URL + 'spojit/' + dokument_id + '/vybrano?spojit_s=';
        var fnc = "spojitDokument(this)";
    }

    for (var zaznam in data) {
        if (evidence == 1) {
            uevidence = '&evidence=1';
        } else {
            uevidence = '';
        }

        a = '<a href="' + url + data[zaznam]['dokument_id'] + uevidence + '" onclick="' + fnc + '; return false;">';
        html = html + "<tr>";
        var cj = data[zaznam]['cislo_jednaci'];
        if (cj === null)
            cj = '';
        html = html + "<td class='cislo-jednaci'>" + a + cj + "</a></td>";
        html = html + "<td class='jid'>" + a + data[zaznam]['jid'] + "</a></td>";
        html = html + "<td>" + a + data[zaznam]['nazev'] + "</a></td>";
        html = html + "</tr>";
    }

    html = html + "</table>";
    return html;
}

hledejDokument = function (input, typ) {

    // nacteme hodnotu
    var vyraz = input.value;
    // vezmeme jen nad tri znaky
    if (vyraz.length < 2)
        return false;

    // cache
    var nalezeno = false;
    for (var index in cache) {
        if (index == vyraz) {
            nalezeno = true;
            $('#vysledek').html(renderVysledekHledaniDokumentu(cache[index], typ));
            break;
        }
    }

    if (!nalezeno) {
        hledejDokumentAjax(vyraz, typ);
    }

    return false;

};

hledejDokumentAjax = function (vyraz, typ) {

    var url = BASE_URL + 'spojit/hledat?q=' + vyraz;

    $.get(url, function (data) {
        var vysledek = $('#vysledek');

        if (data == '') {
            vysledek.html('<div class="prazdno">Nebyly nalezeny žádné dokumenty odpovídající dané sekvenci.</div>');
        } else if (data == 'prilis_mnoho') {
            vysledek.html('<div class="prazdno">Daná sekvence obsahuje příliš mnoho záznamů. Zkuste zvolit přesnější sekvenci.</div>');
        } else {
            cache[vyraz] = data;
            vysledek.html(renderVysledekHledaniDokumentu(data, typ));
        }
    });

    return false;
};

renderSpojeni = function () {

    var href = BASE_URL + 'dokumenty/' + DOKUMENT_ID + '/detail-spojeni';
    $.get(href, function (html) {
        $('#snippet-spojeni').replaceWith(html);
    });
};

spojitDokument = function (elm) {

    $.get(elm.href, function () {
        closeDialog();
        renderSpojeni();
    });

    return false;
};

odebratSpojeni = function (elm) {

    $.get(elm.href, function () {
        renderSpojeni();
    });

    return false;
};

pripojitDokument = function (elm) {

    $.get(elm.href, function (data) {
        if (data.indexOf('###vybrano###') != -1) {
            data = data.replace('###vybrano###', '');

            part = data.split('#');

            //alert(data +' - '+ part[1]);

            $('#dok_cjednaci').html(part[0]);
            document.getElementById('frmnovyForm-poradi').value = part[1];
            document.getElementById('frmnovyForm-odpoved').value = part[2];

            //elm.href = url;
            closeDialog();
        } else if (data.indexOf('###zaevidovano###') != -1) {
            data = data.replace('###zaevidovano###', '');
            window.location = data;
            //elm.href = url;
            closeDialog();

        } else {
            $('#dialog').html(data);
            dialogScrollUp();
        }
    });

    return false;
};


selectReadOnly = function (select) {
    select.selectedIndex = 1;
    return false;
};

filtrSestavy = function (elm) {

    re = /sestavy\/([0-9]+)\/(.*)/;
    var matched = re.exec(elm.href);
    var url = BASE_URL + 'sestavy/' + matched[1] + '/filtr/';
    if (matched[2])
        url += '?tisk=' + matched[2];

    return dialog(elm, 'Filtr', url);
};

zobrazSestavu = function (elm) {

    var param = '?';

    if (elm.pc_od.value != '') {
        param = param + 'pc_od=' + elm.pc_od.value + '&';
    }
    if (elm.pc_do.value != '') {
        param = param + 'pc_do=' + elm.pc_do.value + '&';
    }
    if (elm.d_od.value != '') {
        param = param + 'd_od=' + elm.d_od.value + '&';
    }
    if (elm.d_do.value != '') {
        param = param + 'd_do=' + elm.d_do.value + '&';
    }
    if (elm.d_today.checked) {
        param = param + 'd_today=' + elm.d_today.value + '&';
    }
    if (elm.rok.value != '') {
        param = param + 'rok=' + elm.rok.value;
    }

    //window.location.href = elm.url.value + param;
    window.open(elm.url.value + param);

    closeDialog();

    return false;
};

function select_set_value(SelectObject, Value) {
    for (index = 0;
            index < SelectObject.length;
            index++) {
        if (SelectObject[index].value == Value)
            SelectObject.selectedIndex = index;
    }
}

function htmlspecialchars(string, quote_style, charset, double_encode) {
    // http://kevin.vanzonneveld.net
    // +   original by: Mirek Slugen
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nathan
    // +   bugfixed by: Arno
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      input by: Mailfaker (http://www.weedem.fr/)
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +      input by: felix
    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +    bugfixed by: Tomas Vancura (chyba pri prazdnem string ;))
    // %        note 1: charset argument not supported
    // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
    // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
    // *     example 2: htmlspecialchars("ab\"c'd", ['ENT_NOQUOTES', 'ENT_QUOTES']);
    // *     returns 2: 'ab"c&#039;d'
    // *     example 3: htmlspecialchars("my "&entity;" is still here", null, null, false);
    // *     returns 3: 'my &quot;&entity;&quot; is still here'
    var optTemp = 0,
            i = 0,
            noquotes = false;
    if (typeof quote_style === 'undefined' || quote_style === null) {
        quote_style = 2;
    }

    if (typeof string == "object")
        return "";

    string = string.toString();
    if (double_encode !== false) { // Put this first to avoid double-encoding
        string = string.replace(/&/g, '&amp;');
    }
    string = string.replace(/</g, '&lt;').replace(/>/g, '&gt;');

    var OPTS = {
        'ENT_NOQUOTES': 0,
        'ENT_HTML_QUOTE_SINGLE': 1,
        'ENT_HTML_QUOTE_DOUBLE': 2,
        'ENT_COMPAT': 2,
        'ENT_QUOTES': 3,
        'ENT_IGNORE': 4
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== 'number') { // Allow for a single string or an array of string flags
        quote_style = [].concat(quote_style);
        for (i = 0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'ENT_IGNORE' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            }
            else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/'/g, '&#039;');
    }
    if (!noquotes) {
        string = string.replace(/"/g, '&quot;');
    }

    return string;
}

zmen_rezim_subjektu = function () {

    var rezim = this.title;
    if (rezim == 'A')
        rezim = 'O';
    else if (rezim == 'O')
        rezim = 'AO';
    else
        rezim = 'A';

    // zmen element obrazku
    // this.title = this.alt = rezim;
    // var url = this.src;
    // url = url.replace(/subjekt_[^\.]*/, 'subjekt_' + rezim.toLowerCase());
    // this.src = url;

    var subjekt_id = this.id.replace(/subjekt_ikona_/, '');
    var url = BASE_URL + 'subjekty/' + subjekt_id + '/zmenrezim?';
    url += 'dok_id=' + DOKUMENT_ID + '&typ=' + rezim;

    $.get(url, '', function (data) {
        renderSubjekty();
    }, 'text');

};
/**
 * Initializuje Select2 widgety na prvcich select s atributem data-widget-select2=1
 * kontroluje zda je funkce dostupna
 * @returns {void}
 */
initSelect2 = function () {
    if (typeof $().select2 !== 'undefined') {
        // Poznamka: nasledujici neni pouzito, nahrazeno widgetem spisoveho znaku
        $('select:not(has-select2-widget)[data-widget-select2=1]').each(function () {

            var options = $(this).data('widget-select2-options') || {};

            $(this).select2(options).attr('has-select2-widget', 1);
        });

        $('.widget_spisovy_znak').select2({
            width: 'resolve'
        });
    }
};

vybratSpisovyZnak = function (element) {

    // ziskej DOM element <form>
    var form = $(element).parents('form').first()[0];

    var key = form.spisovy_znak_id.selectedIndex;
    var sz_id = form.spisovy_znak_id.options[key].value;
    if (sz_id == 0) // položka "vyberte z nabídky"
        return true;

    var url = BASE_URL + 'spisznak/' + sz_id + '/';
    $.get(url, function (data) {
        form.skartacni_znak.value = data.skartacni_znak;
        form.skartacni_lhuta.value = data.skartacni_lhuta;
        if (typeof form.spousteci_udalost_id != 'undefined')
            select_set_value(form.spousteci_udalost_id, data.spousteci_udalost_id);
    });

    return true;
};

postFormJ = function (form, callback) {

    if (!(form instanceof jQuery))
        form = $(form);

    $.post(form.attr('action'), form.serialize(), callback);
};

initSpisAutocomplete = function (filter) {
    filter = filter || 'spisovka';
    var url = BASE_URL + 'spisy/seznam-ajax?filter=' + filter;
    $('.spis_autocomplete').select2({
        width: '500px',
        minimumInputLength: 3,
        ajax: {
            url: url,
            dataType: 'json',
            quietMillis: 400,
            data: function (term, page) {
                return {
                    q: term // search term
                };
            },
            results: function (data, page) {
                // parse the results into the format expected by Select2.
                return {results: data};
            },
            cache: true
        }
    });
};

openHelpWindow = function (url) {
    $('#napoveda').dialog("option", "title", "Nápověda");
    // $('#napoveda').html('');
    $('#napoveda').dialog('open');

    displayHelpPage(url);
    return false;
};

displayHelpPage = function (url) {
    $.get(url, function (html) {
        $('#napoveda').html(html);
        $('#napoveda a').click(function () {
            displayHelpPage(this.href);
            return false;
        });
    });
};

ajaxAnchor = function (event) {
    $.get(this.href, function (payload) {
        var sel = $('#' + payload.id);
        sel.html(payload.html);
    });

    return false;
};

ajaxDialog = function (event) {
    var title = $(this).data('title') || '';
    dialog(this, title);
    return false;
};

installDatePicker = function () {

    /*
     * DatePicter - volba datumu z kalendare
     */
    $("input.datepicker").datepicker(
            {
                showButtonPanel: true,
                changeMonth: true,
                changeYear: true,
                yearRange: '-15:+2',
                dateFormat: "dd.mm.yy",
                closeText: "Zrušit",
                prevText: "Předchozí",
                nextText: "Další",
                currentText: "Dnes",
                monthNames: ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"],
                monthNamesShort: ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"],
                //monthNamesShort:["Led","Úno","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
                dayNames: ["Neděle", "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota"],
                dayNamesMin: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So"],
                firstDay: 1
            });

    $("input.DPNoPast").datepicker("option", "minDate", 0);
};

attachmentUploadCompleted = function (response) {

    if (response.substring(0, 13) == '###nahrano###') {
        alert('Příloha byl nahrána.');
    } else if (response.substring(0, 13) == '###zmemeno###') {
        alert('Příloha byla upravena.');
    } else {
        // Bohuzel nedokazi rozpoznat, jestli response obsahuje HTML formular nebo 
        // Tracy dump s chybou
        // Neni dostupna informace, jestli pozadavek skoncil s 200 nebo 500
        // alert('V aplikaci došlo k problému.');
        $('#dialog').html(response);
        return;
    }
    $('#dialog').dialog('close');
    renderPrilohy();
};
