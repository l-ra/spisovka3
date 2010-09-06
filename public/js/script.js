var stop_timer = 0;
var cache = Array();

// Datepicker
$(function() {

    /*
     * DatePicter - volba datumu z kalendare
     */
    $("input.datepicker").datepicker(
    {
        showButtonPanel: true,
        changeMonth: true,
	changeYear: true,
        dateFormat : "dd.mm.yy",
        closeText:"Zrušit",
        prevText:"Předchozí",
        nextText:"Další",
        currentText:"Dnes",
        monthNames:["Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec"],
        monthNamesShort:["Leden","Únor","Březen","Duben","Květen","Červen","Červenec","Srpen","Září","Říjen","Listopad","Prosinec"],
        //monthNamesShort:["Led","Úno","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
        dayNames:["Neděle","Pondělí","Úterý","Středa","Čtvrtek","Pátek","Sobota"],
        dayNamesMin:["Ne","Po","Út","St","Čt","Pá","So"]
    });
    $('input.datetimepicker').datepicker(
    {
        duration: '',
        changeMonth: true,
        changeYear: true,
        yearRange: '2007:2020',
        showTime: true,
        time24h: true,
        currentText: 'Dnes',
        closeText: 'OK'
    });

    /*
     * Nastaveni spinneru jako okenko pod kurzorem
     */
    $('<div id="ajax-spinner"></div>').appendTo("body").ajaxStop(function () {
        // a při události ajaxStop spinner schovám a nastavím mu původní pozici
        $(this).hide().css({
            position: "fixed",
            left: "50%",
            top: "50%"
        });
    }).hide();

    /* Volání AJAXu u všech odkazů s třídou ajax */
    $("a.ajax").live("click", function (event) {
        event.preventDefault();
        $.get(this.href);

        $("#ajax-spinner").show().css({
            position: "absolute",
            left: event.pageX + 20,
            top: event.pageY + 40
        });
    });

    /* AJAXové odeslání formulářů */
    $("form.ajax").live("submit", function () {
        $(this).ajaxSubmit();
        return false;
    });

    $("form.ajax :submit").live("click", function () {
        $(this).ajaxSubmit();
        return false;
    });

    // Povinne polozky
    $('label.required').attr('title','Povinná položka').append(' <span class="star">*</span>');

    // Dialog
    $('#dialog').dialog({
        autoOpen: false,
	width: 700,
        height: 500,
        modal: true
    });

    // Dialog - Vyber spisu
    $('#dialog-spis').click(function(event){
        event.preventDefault();
        return dialog(this,'Spisy');
    });

    $("#spis-vytvorit").live("submit", function () {

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    text = '<div class="flash_message flash_info">Spis byl úspěšně vytvořen.</div>';
                    text = text + x.responseText;
                    $('#dialog').html(text);
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


    // Dialog - Vyber spisu
    $('#dialog-zmocneni').click(function(event){
        event.preventDefault();
        return dialog(this,'Připojit zmocnění');
    });

    // Dialog - Vyber subjektu
    $('#dialog-subjekt').click(function(event){
        event.preventDefault();
        return dialog(this,'Subjekt');
    });

    // Dialog - Vyber subjektu
    $('#dialog-subjektzmenit').click(function(event){
        event.preventDefault();
        return dialog(this,'Subjekt');
    });


    $("#subjekt-vytvorit").live("submit", function () {

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {

                    stav = x.responseText;
                    if ( stav.indexOf('###zmeneno###') != -1 ) {
                        stav = stav.replace('###zmeneno###','');
                        $('#dialog').dialog('close');
                        renderSubjekty(stav);
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

    // Dialog - Vyber subjektu
    $('#dialog-pridat-prilohu').click(function(event){
        event.preventDefault();
        return dialog(this,'Přidat přílohu');
    });

    // Dialog - Vyber zamestnance pro predani
    $('#dialog-uzivatel').click(function(event){
        event.preventDefault();
        return dialog(this,'Předat dokument zaměstnanci');
    });

    // Dialog - Vyber zamestnance pro predani
    $('#dialog-spojit').click(function(event){
        event.preventDefault();
        return dialog(this,'Spojit s dokumentem');
    });

    // Dialog - Historie
    $('#dialog-historie').click(function(event){
        event.preventDefault();
        return dialog(this,'Historie - Transakční protokol');
    });

    // Dialog - pripojit k archu
    $('#dialog-cjednaci').click(function(event){
        event.preventDefault();
        return dialog(this,'Připojit k dokumentu');
    });

});


/*
 *  Vyvolani dialogu
 *
 *
 */
dialog = function ( elm, title ) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                $('#dialog').html(x.responseText);
            }
        }
        x.open("GET", elm.href, true);
        x.send(null);
    }

    if ( typeof title == 'null' ) {
        $('#ui-dialog-title-dialog').html('Dialogové okno');
    } else {
        $('#ui-dialog-title-dialog').html(title);
    }
    
    $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
    $('#dialog').dialog('open');

    return false;

}

/*
 * ARES
 *
 */
aresSubjekt = function ( formName ) {

    var frmIC = document.getElementById('frm'+formName+'-ic');
    baseUri = baseUri.replace('/public','');
    //var url = baseUri + '/subjekty/ares/' + frmIC.value;
    var url = baseUri + 'subjekty/' + frmIC.value +'/ares';
    alert( url );

    $.getJSON(url, function(data) {

        if ( data == null ) {
            alert('Záznam neexistuje nebo bylo zadáno chybné IČ.');
        } else {
            document.getElementById('frm'+formName+'-ic').value = data.ico;
            document.getElementById('frm'+formName+'-dic').value = data.dic;
            document.getElementById('frm'+formName+'-nazev_subjektu').value = data.nazev;
            document.getElementById('frm'+formName+'-adresa_ulice').value = data.ulice;
            document.getElementById('frm'+formName+'-adresa_cp').value = data.cislo_popisne;
            document.getElementById('frm'+formName+'-adresa_co').value = data.cislo_orientacni;
            document.getElementById('frm'+formName+'-adresa_mesto').value = data.mesto;
            document.getElementById('frm'+formName+'-adresa_psc').value = data.psc;
        }


        
    });

    $("#ajax-spinner").show().css({
        position: "absolute"
        //left: event.pageX + 20,
        //top: event.pageY + 40
    });

    return false;


}

toggle = function (elm) {

    //alert(elm);

    //$('.'+elm).toggle();

    return true;

}

spisVybran = function (elm) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                stav = x.responseText;

                if ( stav.indexOf('###vybrano###') != -1 ) {
                    stav = stav.replace('###vybrano###','');
                    //old_text = document.getElementById('dok_spis').innerHTML;
                    //old_text = old_text.replace('nepřidělen k žádnemu spisu','');
                    $('#dok_spis').html(stav);
                    $('#dialog').dialog('close');
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

renderPrilohy = function (dokument_id) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                $('#dok-prilohy').html(x.responseText);
            }
        }
        baseUri = baseUri.replace('/public','');
        x.open("GET", baseUri + 'prilohy/'+ dokument_id +'/nacti', true);
        x.send(null);
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');


    return false;
}

renderSubjekty = function (dokument_id) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {

            if (x.readyState == 4 && x.status == 200) {

                $('#dok-subjekty').html(x.responseText);
                //document.getElementById('dok-subjekty').innerHTML = "dffds";// x.responseText;
            }
        }
        baseUri = baseUri.replace('/public','');
        x.open("GET", baseUri + 'subjekty/'+ dokument_id +'/nacti', true);
        x.send(null);
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');

    return false;
}

novySubjekt = function (elm) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                $('#dialog').html(x.responseText);
            }
        }
        x.open("GET", elm.href, true);
        x.send(null);
    }

    $('#ui-dialog-title-dialog').html('Nový subjekt');
    $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
    //$('#dialog').dialog('open');

    return false;
}

subjektVybran = function (elm) {

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
                    renderSubjekty(stav);
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

subjektzmenit = function(elm){
        

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    $('#dialog').html(x.responseText);
                }
            }
            x.open("GET", elm.href, true);
            x.send(null);
        }

        $('#ui-dialog-title-dialog').html('Subjekt');
        $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
        $('#dialog').dialog('open');

        return false;
}

subjektUpravitStorno = function () {
    $('#dialog').dialog('close');
}

subjektNovyStorno = function (doc_id) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                $('#dialog').html(x.responseText);
            }
            return false;
        }
        x.open("GET", baseUri + '/subjekty/0/vyber?dok_id='+doc_id, true);
        x.send(null);
        return false;
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');

    return false;

}

osobaVybrana = function (elm) {

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

prilohazmenit = function(elm){

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    $('#dialog').html(x.responseText);
                }
            }
            x.open("GET", elm.href, true);
            x.send(null);
        }

        $('#ui-dialog-title-dialog').html('Upravit přílohu');
        $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
        $('#dialog').dialog('open');

        return false;
}

odebratPrilohu = function(elm, dok_id){

    if ( confirm('Opravdu chcete odebrat přílohu z dokumentu?') ) {

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    renderPrilohy(dok_id);
                }
            }
            x.open("GET", elm.href, true);
            x.send(null);
        }

    }

    return false;
}

odebratSubjekt = function(elm, dok_id){

    if ( confirm('Opravdu chcete odebrat subjekt z dokumentu?') ) {
        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    renderSubjekty(dok_id);
                    return false;
                }
            }
            x.open("GET", elm.href, true);
            x.send(null);
        }

    }

    return false;
}

hledejDokument = function (input, typ) {
	
    // nacteme hodnotu
    var vyraz = input.value;
    // vezmeme jen nad tri znaky
    if (vyraz.length < 2) return false;

    // cache
    var nalezeno = 0;
    for (var index in cache){
        if ( index == vyraz ) {
            nalezeno = 1;
            var seznam = eval('(' + cache[index] + ')');
            vysledek = document.getElementById('vysledek');
            vysledek.innerHTML = nastylovat(seznam, typ);
            break;
        }
    }

    if(nalezeno==0) {
        hledejDokumentAjax(vyraz, typ);
    }

    return false;

}

hledejDokumentAjax = function (vyraz, typ) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                $('#vysledek-spinner').hide();
               	vysledek = document.getElementById('vysledek');
            	var seznam_json = x.responseText;
                
                if ( seznam_json == '' ) {
                    vysledek.innerHTML = '<div class="prazdno">Nebyli nalezeny žádné dokumenty odpovidající dané sekvenci.</div>';
                } else {
                    var seznam = eval('(' + seznam_json + ')');
                    cache[vyraz] = seznam_json;
                    vysledek.innerHTML = nastylovat(seznam, typ);
                }
            	
            }
        }
        baseUri = baseUri.replace('/public','');
        var url = baseUri + 'spojit/0/nacti?q=' + vyraz;
        x.open("GET", url, true);
        x.send(null);
    }

    $('#vysledek-spinner').toggle();
    return false;
}

spojitDokument = function (elm) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                stav = x.responseText;

                if ( stav.indexOf('###vybrano###') != -1 ) {
                    stav = stav.replace('###vybrano###','');
                    $('#dok_spojit').html(stav);
                    $('#dialog').dialog('close');
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

pripojitDokument = function (elm) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                stav = x.responseText;

                if ( stav.indexOf('###vybrano###') != -1 ) {
                    stav = stav.replace('###vybrano###','');

                    part = stav.split('#');

                    alert(stav +' - '+ part[1]);

                    $('#dok_cjednaci').html(part[0]);
                    document.getElementById('frmnovyForm-poradi').value = part[1];
                    document.getElementById('frmnovyForm-odpoved').value = part[2];
                    

                    $('#dialog').dialog('close');
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

filtrSestavy = function (elm) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                $('#dialog').html(x.responseText);
            }
        }

        baseUri = baseUri.replace('/public','');
        var url = baseUri + 'sestavy/0/filtr/?url='+elm.href;
        x.open("GET", url, true);
        x.send(null);
    }

    $('#ui-dialog-title-dialog').html('Filtr');
    $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
    $('#dialog').dialog('open');

    return false;
}

zobrazSestavu = function (elm) {

    var param = '?';
    if ( elm.pc_od.value != '' ) { param = param + 'pc_od=' + elm.pc_od.value + '&' }
    if ( elm.pc_do.value != '' ) { param = param + 'pc_do=' + elm.pc_do.value + '&' }
    if ( elm.d_od.value != '' )  { param = param + 'd_od=' + elm.d_od.value + '&' }
    if ( elm.d_do.value != '' )  { param = param + 'd_do=' + elm.d_do.value + '&' }
    if ( elm.rok.value != '' )   { param = param + 'rok=' + elm.rok.value }

    window.location.href = elm.url.value + param;

    return false;
}

zrusitFiltrSestavy = function () {
    $('#dialog').dialog('close');
    return false;
}

function nastylovat(data,typ) {

    var html = "<table class='seznam' border='1'>";
        html = html + "<tr>";
        html = html + "<th>číslo jednací</th>";
        html = html + "<th>JID</th>";
        html = html + "<th>věc</th>";
        html = html + "</tr>";

    baseUri = baseUri.replace('/public','');
    dokument_id = document.getElementById('dokumentid').value;

    if ( typ == 1 ) {
        var url = baseUri + 'dokumenty/'+ dokument_id +'/cjednaciadd?spojit_s=';
        var fnc = "pripojitDokument(this)";
    } else {
        var url = baseUri + 'spojit/'+ dokument_id +'/vybrano?spojit_s=';
        var fnc = "spojitDokument(this)";
    }

    for (var zaznam in data){
        a = '<a href="'+ url + data[zaznam]['dokument_id'] +'" onclick="'+fnc+'; return false;">';
        html = html + "<tr>";
        html = html + "<td>"+ a + data[zaznam]['cislo_jednaci'] +"</a></td>";
        html = html + "<td>"+ a + data[zaznam]['jid'] +"</a></td>";
        html = html + "<td>"+ a + data[zaznam]['nazev'] +"</a></td>";
        html = html + "</tr>";
    }
    return html;

}
