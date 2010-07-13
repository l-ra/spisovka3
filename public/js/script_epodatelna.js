$(function() {

    $('#dialog-evidence').click(function(event){
        event.preventDefault();

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    $('#dialog').html(x.responseText);
                }
            }
            x.open("GET", this.href, true);
            x.send(null);
        }

        $('#ui-dialog-title-dialog').html('Evidovat');
        $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
        $('#dialog').dialog('open');

        return false;
    });

    $('#dialog-odmitnout').click(function(event){
        event.preventDefault();

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    $('#dialog').html(x.responseText);
                }
            }
            x.open("GET", this.href, true);
            x.send(null);
        }

        $('#ui-dialog-title-dialog').html('Odmítnout zprávu');
        $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
        $('#dialog').dialog('open');

        return false;
    });

    $('#dialog-novysubjekt').click(function(event){
        event.preventDefault();

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    $('#dialog').html(x.responseText);
                }
            }
            x.open("GET", this.href, true);
            x.send(null);
        }

        $('#ui-dialog-title-dialog').html('Nový subjekt');
        $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
        $('#dialog').dialog('open');

        return false;
    });


});


renderEpodSubjekty = function (subjekt_id) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {

            if (x.readyState == 4 && x.status == 200) {

                subjekty_table = document.getElementById('subjekty-table');
                if ( subjekty_table == null ) {
                    html =        '        <table class="seznam" id="subjekty-table">';
                    html = html + '           <tr>';
                    html = html + '               <td class="icon">Použít</td>';
                    html = html + '               <td class="icon">&nbsp;</td>';
                    html = html + '               <td class="meta">&nbsp;</td>';
                    html = html + '               <td class="meta_plus">&nbsp;</td>';
                    html = html + '           </tr>';
                    html = html + x.responseText;
                    html = html + '        </table>';
                    $('#dok-subjekty').html(html);
                } else {
                    $('#subjekty-table').append(x.responseText);
                }
            }
        }
        baseUri = baseUri.replace('/public','');
        x.open("GET", baseUri + 'epodatelna/subjekty/nacti/'+ subjekt_id, true);
        x.send(null);
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');


    return false;
}

epodSubjektVybran = function (elm) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                stav = x.responseText;

                if ( stav.indexOf('###vybrano###') != -1 ) {
                    stav = stav.replace('###vybrano###','');
                    $('#dialog').dialog('close');
                    renderEpodSubjekty(stav);
                } else {
                    $('#dialog').html(stav);
                }
            }
        }
        x.open("GET", elm.href, true);
        x.send(null);
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');


    return false;
}

EpodosobaVybrana = function (elm) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                stav = x.responseText;

                if ( stav.indexOf('###vybrano###') != -1 ) {
                    stav = stav.replace('###vybrano###','');
                    $('#dialog').dialog('close');
                    location.href = stav;

                    //renderSubjekty(stav);
                } else {
                    $('#dialog').html(stav);
                }
            }
        }

        poznamka = document.getElementById('frmpred-poznamka').value;
        url = elm.href + '&poznamka='+ poznamka;

        x.open("GET", url, true);
        x.send(null);
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');


    return false;
}