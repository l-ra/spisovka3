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

    $("#epodsubjekt-vytvorit").live("submit", function () {

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {

                    stav = x.responseText;
                    if ( stav.indexOf('###zmeneno###') != -1 ) {
                        stav = stav.replace('###zmeneno###','');
                        stav_a = stav.split("#");

                        $('#dialog').dialog('close');
                        renderEpodSubjekty(stav_a[0],stav_a[1]);
                    } else {
                        text = '';// '<div class="flash_message flash_info">Subjekt byl úspěšně vytvořen.</div>';
                        text = text + x.responseText;
                        $('#dialog').html(text);
                    }

                }
            }
            var formdata = $(this).serialize();

            x.open("POST", $(this).attr('action'), true);
            x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            x.setRequestHeader("Content-length", formdata.length);
            x.setRequestHeader("Connection", "close");
            x.send(formdata);
        }

        return false;
    });


});


renderEpodSubjekty = function (subjekt_id, epodatelna_id) {

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

                    subjekt_tr = document.getElementById('epodsubjekt-'+subjekt_id);
                    if ( subjekt_tr != null ) {
                        // replace
                        $(subjekt_tr).replaceWith(x.responseText);
                    } else {
                        // append
                        $('#subjekty-table').append(x.responseText);
                    }

                    
                }
            }
        }
        baseUri = baseUri.replace('/public','');
        x.open("GET", baseUri + 'epodatelna/subjekty/nacti/'+ subjekt_id + '/?epod_id='+epodatelna_id, true);
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
                    stav_a = stav.split("#");
                    $('#dialog').dialog('close');
                    renderEpodSubjekty(stav_a[0],stav_a[1]);
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