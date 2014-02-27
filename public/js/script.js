var stop_timer = 0;
var url;
var cache = Array();

function InstallDatePicker() {

    /*
     * DatePicter - volba datumu z kalendare
     */
    var date = new Date();
    var year = date.getFullYear();
    var year_before = year - 15;
    var year_after = year + 2;

    $("input.datepicker").datepicker(
    {
        /*showOn: 'button',
        buttonText: 'Choose',
        buttonImage: '/images/icons/1day.png',
        buttonImageOnly: true,*/
        showButtonPanel: true,
        changeMonth: true,
	changeYear: true,
        yearRange: year_before+':'+year_after,
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
    /*$("input.datepicker").keyup(function(event) {
        if (event.keyCode == '9') {
            $("#ui-datepicker-div").hide();
        }        
    }); */
    
    $("input.DPNoPast").datepicker("option", "minDate", 0);
    
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
}

$(function() {

    InstallDatePicker();
    
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
	width: 800,
        height: 500,
        modal: true
    });

    // Dialog - Vyber spisu
    $('#dialog-spis').click(function(event){
        event.preventDefault();
        return dialog(this,'Spisy');
    });

    $("#spis-vytvorit").live("submit", function () {
        spisVytvorit($(this));
        return false;
    });

    $("#spisplan-zmena").live("submit", function () {
        alert("kuk");
        spisplanZmenit($(this));
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
    $('#subjekt_pripojit_click').click(function(event){
        event.preventDefault();
        $('#subjekt_pripojit').show();
        $('#subjekt_autocomplete').focus();
        
        return false;
    });    

    // Dialog - Vyber subjektu
    $('#dialog-pridat-prilohu').click(function(event){
        event.preventDefault();

        if (document.getElementById) {
            var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
        }
        if (x) {
            x.onreadystatechange = function() {
                if (x.readyState == 4 && x.status == 200) {
                    $('#dok-prilohy-form').html(x.responseText);
                }
            }
            url = this.href;
            x.open("GET", url, true);
            x.send(null);
        }

        $('#dok-prilohy-form').html('<div id="ajax-spinner" style="display: inline;"></div>');

        return false;

        //return dialog(this,'Přidat přílohu');
    });

    // Dialog - Vyber zamestnance pro predani
    $('#dialog-uzivatel').click(function(event){
        event.preventDefault();
        return dialog(this,'Předat dokument organizační jednotce nebo zaměstnanci');
    });
    
    // Dialog - Vyber org pro predani spisu
    $('#dialog-spis').click(function(event){
        event.preventDefault();
        return dialog(this,'Předat spis organizační jednotce nebo zaměstnanci');
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
        return dialog(this,'Vložit do spisu');
    });

    // Dialog - hledat
    $('#dialog-search').click(function(event){
        event.preventDefault();
        return dialog(this,'Pokročilé vyhledávání');
    });


    $('#predat_autocomplete').autocomplete({
        minLength: 3,
	/*source: seznam_uzivatelu,*/
        source: (is_simple==1)?baseUri + '?presenter=Spisovka%3Auzivatel&action=seznamAjax':baseUri + 'uzivatel/seznamAjax',

	focus: function(event, ui) {
            $('#predat_autocomplete').val(ui.item.nazev);
            return false;
        },
	select: function(event, ui) {
            $('#predat_autocomplete').val('');
            if ( ui.item.id.substring(0,1) == "u" ) {
                $('#frmnovyForm-predano_user').val(ui.item.id.substr(1));
                $('#frmnovyForm-predano_org').val('');
                $('#predano').html("<dl class=\"detail_item\"><dt>Předáno:</dt><dd>"+ui.item.nazev+"<br />&nbsp;</dd></dl>");
            } else if ( ui.item.id.substring(0,1) == "o" ) {
                $('#frmnovyForm-predano_user').val('');
                $('#frmnovyForm-predano_org').val(ui.item.id.substr(1));
                $('#predano').html("<dl class=\"detail_item\"><dt>Předáno:</dt><dd>organizační jednotce<br />"+ui.item.nazev+"</dd></dl>");
            }
            return false;
	}
    });
    $("#predat_autocomplete").keypress(function(event) {
        if ( event.which == 13 ) {
            event.preventDefault();
        }
    });    
    

    $('#subjekt_autocomplete').autocomplete({
        minLength: 3,
        source: (is_simple==1)?baseUri + '?presenter=Spisovka%3Asubjekty&action=seznamAjax':baseUri + 'subjekty/0/seznamAjax',

	focus: function(event, ui) {
            $('#subjekt_autocomplete').val(ui.item.nazev);
            return false;
        },
	select: function(event, ui) {
            $('#subjekt_autocomplete').val('');

            if (document.getElementById) {
                var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
            }
            if (x) {
                x.onreadystatechange = function() {
                    if (x.readyState == 4 && x.status == 200) {
                        stav = x.responseText;

                        if ( stav.indexOf('###vybrano###') != -1 ) {
                            stav = stav.replace('###vybrano###','');
                            alert('Subjekt připojen.');
                            renderSubjekty(stav);
                        } else {
                            alert(stav);
                        }
                    }
                }

                // Nasledujici kod zajistuje automaticke urceni typu pripojeni subjektu
                var typ_id = $('#frmnovyForm-dokument_typ_id').val();
                if (typeof typ_id == 'undefined')
                    typ_id = $('#frmmetadataForm-dokument_typ_id').val();
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
                
                if ( is_simple == 1 ) {
                    url = baseUri + '?presenter=Spisovka%3Asubjekty&id='+ui.item.id+'&action=vybrano&dok_id='+document.getElementById('subjekt_dokument_id').value+'&typ=' + typ_code + '&autocomplete=1';                    
                } else {
                    url = baseUri + 'subjekty/'+ui.item.id+'/vybrano?dok_id='+document.getElementById('subjekt_dokument_id').value+'&typ=' + typ_code + '&autocomplete=1';
                }
                x.open("GET", url, true);
                x.send(null);
            }

            return false;
	}
    });
    $("#subjekt_autocomplete").keypress(function(event) {
        if ( event.which == 13 ) {
            event.preventDefault();
        }
    });    

    $('#novysubjekt_click').click( subjektNovy );

    $('#checkbox_all_on').click(function(event) {
        $('input[name^=dokument_vyber]').attr('checked',true);
        $('input[name^=spis_vyber]').attr('checked',true);
        $('input[name^=zapujcka_vyber]').attr('checked',true);
    });
    $('#checkbox_all_off').click(function(event) {
        $('input[name^=dokument_vyber]').attr('checked',false);
        $('input[name^=spis_vyber]').attr('checked',false);
        $('input[name^=zapujcka_vyber]').attr('checked',false);
    });
    
});


dontFollowLink = function () {

    // nutny hack pro Internet Explorer
    if (typeof event != 'undefined')
        event.returnValue = false;
}

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

        url = elm.href;

        if ( url.indexOf("?") !== -1 ) {
            url = url +"&is_ajax=1";
        } else {
            url = url +"?is_ajax=1";
        }

        x.open("GET", url, true);
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
    if ( is_simple == 1 ) {
        var url = baseUri + '?presenter=Spisovka%3Asubjekty&id=' + frmIC.value +'&action=ares';
    } else {    
        var url = baseUri + 'subjekty/' + frmIC.value +'/ares';
    }
    //alert( url );

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
            document.getElementById('frm'+formName+'-adresa_stat').value = 'CZE';
            document.getElementById('frm'+formName+'-stat_narozeni').value = 'CZE';
        }


        
    });

    $("#ajax-spinner").show().css({
        position: "absolute"
        //left: event.pageX + 20,
        //top: event.pageY + 40
    });

    return false;


}

/*
 * ISDS - vyhledat subjekt na zaklade id  schranky
 *
 */
isdsSubjekt = function ( formName ) {

    var frmID = document.getElementById('frm'+formName+'-id_isds');
    baseUri = baseUri.replace('/public','');
    //var url = baseUri + '/subjekty/ares/' + frmIC.value;
    if ( is_simple == 1 ) {
        var url = baseUri + '?presenter=Spisovka%3Asubjekty&id=' + frmID.value +'&action=isdsid';
    } else {    
        var url = baseUri + 'subjekty/' + frmID.value +'/isdsid';
    }
    //alert( url );

    $.getJSON(url, function(data) {

        if ( data == null ) {
            alert('Záznam neexistuje nebo bylo zadáno chybné ID datové schránky.');
        } else if ( data.error ) {
            alert(data.error);
        } else {
            
            $("#frm"+formName+"-type option[value="+data.dbType+"]").attr('selected', 'selected');
            
            document.getElementById('frm'+formName+'-ic').value = data.ic;
            document.getElementById('frm'+formName+'-nazev_subjektu').value = data.firmName;
            document.getElementById('frm'+formName+'-jmeno').value = data.pnFirstName;
            document.getElementById('frm'+formName+'-prostredni_jmeno').value = data.pnMiddleName;
            document.getElementById('frm'+formName+'-prijmeni').value = data.pnLastName;
            document.getElementById('frm'+formName+'-adresa_ulice').value = data.adStreet;
            document.getElementById('frm'+formName+'-adresa_cp').value = data.adNumberInMunicipality;
            document.getElementById('frm'+formName+'-adresa_co').value = data.adNumberInStreet;
            document.getElementById('frm'+formName+'-adresa_mesto').value = data.adCity;
            document.getElementById('frm'+formName+'-adresa_psc').value = data.adZipCode;
        }
        
    });

    $("#ajax-spinner").show().css({
        position: "absolute"
        //left: event.pageX + 20,
        //top: event.pageY + 40
    });

    return false;

}

/*
 * CRON - zpracovani na pozadi
 *
 */
ajaxcron = function () {
    
    if (!(Math.random() < 0.1))
        return false;
	
    baseUri = baseUri.replace('/public','');
    if ( is_simple == 1 ) {
        var url = baseUri + '?presenter=Spisovka%3Acron&action=spustit';
    } else {    
        var url = baseUri + 'cron/spustit';
    }
    //alert( url );

    $.get(url, function(data) {
    });

    return false;

}



toggleWindow = function (elm) {

    //parent = $(elm).parent();

    //alert(parent.prop('tagName'));

    return false;

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
        dontFollowLink();
        url = elm.href;
        x.open("GET", url, true);
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
                $('#reload_spinner').html('');
            }
        }
        baseUri = baseUri.replace('/public','');
        if ( is_simple == 1 ) {
            x.open("GET", baseUri + '?presenter=Spisovka%3Aprilohy&id='+ dokument_id +'&action=nacti', true);
        } else {    
            x.open("GET", baseUri + 'prilohy/'+ dokument_id +'/nacti', true);
        }
        x.send(null);
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');
    $('#reload_spinner').append('<div id="ajax-spinner-hor" style="display: inline;"></div>');


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
                $('#reload_spinner').html('');
            }
        }
        baseUri = baseUri.replace('/public','');
        if ( is_simple == 1 ) {
            x.open("GET", baseUri + '?presenter=Spisovka%3Asubjekty&id='+ dokument_id +'&action=nacti', true);
        } else {
            x.open("GET", baseUri + 'subjekty/'+ dokument_id +'/nacti', true);
        }
        x.send(null);
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');
    $('#reload_spinner').append('<div id="ajax-spinner-hor" style="display: inline;"></div>');

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
        url = elm.href;
        x.open("GET", url, true);
        x.send(null);
    }

    $('#ui-dialog-title-dialog').html('Nový subjekt');
    $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
    //$('#dialog').dialog('open');

    return false;
}

subjektVytvorit = function () {

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

        var subjekt_vytvorit = document.getElementById("subjekt-vytvorit");
        var formdata = $(subjekt_vytvorit).serialize();
        
        //alert(subjekt_vytvorit.getAttribute('action'));
        
        x.open("POST", subjekt_vytvorit.getAttribute('action'), true);
        x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        x.setRequestHeader("Content-length", formdata.length);
        x.setRequestHeader("Connection", "close");
        x.send(formdata);
    }

    return false;
}

subjektVybran = function (elm) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');

        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                stav = x.responseText;

                if ( stav.indexOf('###vybrano###') != -1 ) {
                    stav = stav.replace('###vybrano###','');
                    $('#dialog').dialog('close');
                    renderSubjekty(stav);
                } else {
                    $('#ajax-spinner', $('#dialog')).remove();
                    alert(stav);
                }
            }
        }
        url = elm.href;
        dontFollowLink();
        x.open("GET", url, true);
        x.send(null);
    }

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
        url = elm.href;
        dontFollowLink();
        x.open("GET", url, true);
        x.send(null);
    }

    $('#ui-dialog-title-dialog').html('Subjekt');
    $('#dialog').html('<div id="ajax-spinner" style="display: inline;"></div>');
    $('#dialog').dialog('open');

    return false;
}

subjektUpravitSubmit = function () {
    subjektVytvorit();
    return false;    
}

subjektNovySubmit = function () {
    subjektVytvorit();
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
        if ( is_simple == 1 ) {
            x.open("GET", baseUri + '?presenter=Spisovka%3Asubjekty&id=0&action=vyber&dok_id='+doc_id, true);
        } else {    
            x.open("GET", baseUri + 'subjekty/0/vyber?dok_id='+doc_id, true);
        }        
        
        x.send(null);
        return false;
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');

    return false;

}

subjektNovy = function(event) {
        event.preventDefault();

        id = document.getElementById('subjekt_dokument_id').value;

        if ( is_simple == 1 ) {
            url_ajaxtyp = baseUri + '?presenter=Spisovka%3Asubjekty&id=0&action=seznamtypusubjektu';
        } else { 
            url_ajaxtyp = baseUri + 'subjekty/0/seznamtypusubjektu';
        }
        $.getJSON(url_ajaxtyp, function(data){
            var typ_select = '<select name="subjekt_typ['+id+']">';

            $.each(data, function(key, val) {
                typ_select = typ_select + '<option value="' + key + '">' + val + '</option>';
            });
            
            typ_select = typ_select + "</select>";
            $('#typ_subjektu').html(typ_select);
        });            

        if ( is_simple == 1 ) {
            url_ajaxtyp = baseUri + '?presenter=Spisovka%3Asubjekty&id=0&action=seznamstatuajax';
        } else { 
            url_ajaxtyp = baseUri + 'subjekty/0/seznamstatuajax';
        }
        $.getJSON(url_ajaxtyp, function(data){
            var select = '<select name="stat['+id+']">';

            $.each(data, function(key, val) {
                select = select + '<option value="' + key + '">' + val + '</option>';
            });
            
            select = select + "</select>";
            $('#novy_subjekt_stat').html(select);
        });            
        
        
        novy_subjekt = ''+
'                        <dt>Typ subjektu:</dt>'+
'                        <dd id="typ_subjektu"></dd>'+
'                        <dt>Název subjektu:</dt>'+
'                        <dd><input type="text" name="subjekt_nazev['+id+']" value="" size="60" /></dd>'+
'                        <dt>Titul před, jméno, příjmení, titul za:</dt>'+
'                        <dd><input type="text" name="subjekt_titulpred['+id+']" value="" size="5" /><input type="text" name="subjekt_jmeno['+id+']" value="" size="20" /><input type="text" name="subjekt_prijmeni['+id+']" value="" size="40" /><input type="text" name="subjekt_titulza['+id+']" value="" size="5" /></dd>'+
'                        <dt>Ulice a číslo popisné:</dt>'+
'                        <dd><input type="text" name="subjekt_ulice['+id+']" value="" size="20" /><input type="text" name="subjekt_cp['+id+']" value="" size="10" /></dd>'+
'                        <dt>PSČ a Město:</dt>'+
'                        <dd><input type="text" name="subjekt_psc['+id+']" value="" size="6" /><input type="text" name="subjekt_mesto['+id+']" value="" size="50" /></dd>'+
'                        <dt>Stát:</dt>'+
'                        <dd id="novy_subjekt_stat"></dd>'+
'                        <dt>Email:</dt>'+
'                        <dd><input type="text" name="subjekt_email['+id+']" value="" size="60" /></dd>'+
'                        <dt>ID datové schránky:</dt>'+
'                        <dd><input type="text" name="subjekt_isds['+id+']" value="" size="30" /></dd>'+
'                        <dt>&nbsp;</dt>'+
'                        <input type="hidden" name="dokument_id" value="'+id+'" />'+
'                        <dd><input type="submit" name="subjekt_pridat['+id+']" value="Vytvořit a přidat" id="subjekt_pridat" /></dd>';

        if ( typeof document.forms["frm-novyForm"] == "undefined" ) {
            novy_subjekt = '<form action="" method="POST" name="frm-novySubjekt">'+novy_subjekt+'</form>';
        }

        $('#subjekt_novy').html(novy_subjekt);

        $("#subjekt_pridat").click(function() {

            if (document.getElementById) {
                var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
            }
            if (x) {
                x.onreadystatechange = function() {
                    if (x.readyState == 4 && x.status == 200) {
                        text = x.responseText;

                        if ( text[0] == "#" ) {
                            text = text.substr(1);
                            alert(text);
                        } else {
                            part = text.split("#");
                            $('#subjekt_novy').html(
                                '                        <dt>&nbsp;</dt>'+
                                '                        <dd><a href="" id="novysubjekt_click">Vytvořit nový subjekt</a></dd>'
                            );
                            $('#novysubjekt_click').click( subjektNovy );
                            renderSubjekty(id);

                            alert('Subjekt byl vytvořen a přidán.');

                        }
                    }
                }

                if ( typeof document.forms["frm-novyForm"] != "undefined" ) {
                    formdata = 'id='+id+'&' + $(document.forms["frm-novyForm"]).serialize();
                }else if ( typeof document.forms["frm-novySubjekt"] != "undefined" ) {
                    formdata = 'id='+id+'&' + $(document.forms["frm-novySubjekt"]).serialize();
                } else {
                    formdata = '';
                }

                if ( is_simple == 1 ) {
                    x.open("POST", baseUri + '?presenter=Spisovka%3Asubjekty&id=0&action=vytvoritAjax', true);
                } else { 
                    x.open("POST", baseUri + 'subjekty/0/vytvoritAjax', true);
                }
                
                x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                x.setRequestHeader("Content-length", formdata.length);
                x.setRequestHeader("Connection", "close");
                x.send(formdata);
            }

            return false;
        });

        return false;
    }

spisVytvoritSubmit = function () {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                $('#dialog').html(x.responseText);
            }
        }

        var spis_vytvorit = document.getElementById("spis-vytvorit");
        var formdata = $(spis_vytvorit).serialize();
        
        //alert(spis_vytvorit.getAttribute('action'));
        
        x.open("POST", spis_vytvorit.getAttribute('action'), true);
        x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        x.setRequestHeader("Content-length", formdata.length);
        x.setRequestHeader("Connection", "close");
        x.send(formdata);
    }

    return false;
}

spisVytvoritStorno = function (doc_id) {

    $('#spis_novy').hide();
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
                } else if ( stav.indexOf('###predano###') != -1 ) {
                    stav = stav.replace('###predano###','');
                    part = stav.split('#');
                    $('#frmnovyForm-predano_user').val(part[1]);
                    $('#frmnovyForm-predano_org').val(part[2]);
                    $('#frmnovyForm-predano_poznamka').val(part[3]);
                    $('#predano').html("<dl class=\"detail_item\"><dt>Předáno:</dt><dd>"+part[5]+"<br />"+part[4]+"</dd></dl><dl class=\"detail_item\"><dt>Poznámka pro předávajícího:</dt><dd>"+part[3]+"&nbsp;</dd></dl>");
                    $('#dialog').dialog('close');
                } else {
                    $('#dialog').html(stav);
                }
            }
        }

        poznamka = document.getElementById('frmpred-poznamka').value;
        poznamka = encodeURI(poznamka);
        url = elm.href + '&poznamka='+ poznamka;
        dontFollowLink();
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
        url = elm.href;
        dontFollowLink();
        x.open("GET", url, true);
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
            url = elm.href;
            dontFollowLink();
            x.open("GET", url, true);
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
            url = elm.href;
            dontFollowLink();
            x.open("GET", url, true);
            x.send(null);
        }

    }

    return false;
}

vyber_odes_form = function ( elm, subjekt_id ) {
    
    var volba = elm.options[elm.selectedIndex].value;
    
    odes_form_reset(subjekt_id);
    
    $('#odes_'+subjekt_id+'_'+volba).show();
    
    
    return false;
    
}

odes_form_reset = function ( subjekt_id ) {
    
    if ( typeof subjekt_id != "undefined" ) {
        $('#odes_'+subjekt_id+'_1').hide();
        $('#odes_'+subjekt_id+'_2').hide();
        $('#odes_'+subjekt_id+'_3').hide();
        $('#odes_'+subjekt_id+'_4').hide();
    } else {
        $('.odes_form').hide();
    }
    
    
}

zobrazFax = function (elm) {
    return dialog(elm,'Zobrazit zprávu faxu');
}

vypravnaDetail = function (elm) {
    return dialog(elm,'Detail záznamu');
}
vypravnaSubmit = function () {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                text = x.responseText;
                
                if ( text.indexOf('###provedeno###') != -1 ) {
                    $('#dialog').dialog('close');
                    alert('Záznam byl úspěšně upraven.');
                    window.top.location = window.top.location;
                } else {
                    $('#dialog').html(text);
                }
            }
        }

        var vypravna_form = document.getElementById("vypravna_form");
        var formdata = $(vypravna_form).serialize();

        x.open("POST", vypravna_form.getAttribute('action'), true);
        x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        x.setRequestHeader("Content-length", formdata.length);
        x.setRequestHeader("Connection", "close");
        x.send(formdata);
    }
    
    return false;
}
vypravnaZrusit = function () {
    $('#dialog').dialog('close');
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
               	var vysledek = document.getElementById('vysledek');
            	var seznam_json = x.responseText;
                
                if ( seznam_json == '' ) {
                    vysledek.innerHTML = '<div class="prazdno">Nebyly nalezeny žádné dokumenty odpovídající dané sekvenci.</div>';
                } else if ( seznam_json == 'prilis_mnoho' ) {
                    vysledek.innerHTML = '<div class="prazdno">Daná sekvence obsahuje příliš mnoho záznamů. Zkuste zvolit přesnější sekvenci.</div>';                    
                }else {
                    var seznam = eval('(' + seznam_json + ')');
                    cache[vyraz] = seznam_json;
                    vysledek.innerHTML = nastylovat(seznam, typ);
                }
            	
            }
        }
        baseUri = baseUri.replace('/public','');
        
        if ( is_simple == 1 ) {
            var url = baseUri + '?presenter=Spisovka%3Aspojit&id=0&action=nacti&q=' + vyraz;
        } else { 
            var url = baseUri + 'spojit/0/nacti?q=' + vyraz;
        }
        
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
        url = elm.href;
        dontFollowLink();
        x.open("GET", url, true);
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

                    //alert(stav +' - '+ part[1]);

                    $('#dok_cjednaci').html(part[0]);
                    document.getElementById('frmnovyForm-poradi').value = part[1];
                    document.getElementById('frmnovyForm-odpoved').value = part[2];

                    //elm.href = url;
                    $('#dialog').dialog('close');
                } else if ( stav.indexOf('###zaevidovano###') != -1 ) {
                    stav = stav.replace('###zaevidovano###','');
                    window.location = stav;
                    //elm.href = url;
                    $('#dialog').dialog('close');

                } else {
                    $('#dialog').html(stav);
                }
            }
        }
        url = elm.href;
        x.open("GET", url, true);
        x.send(null);
    }

    $('#dialog').append('<div id="ajax-spinner" style="display: inline;"></div>');


    return false;
}

spisVytvorit = function (elm) {

    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                //text = '<div class="flash_message flash_info">Spis byl úspěšně vytvořen.</div>';
                text = x.responseText;
                $('#dialog').html(text);
            }
        }

        var formdata = $(elm).serialize();

        x.open("POST", $(elm).attr('action'), true);
        x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        x.setRequestHeader("Content-length", formdata.length);
        x.setRequestHeader("Connection", "close");
        x.send(formdata);
    }

    return false;
}

overitISDS = function (elm) {
    
    return dialog(elm,'Ověření datové zprávy');
    
}

spisplanZmenit = function (elm) {
    
    if (document.getElementById) {
        var x = (window.ActiveXObject) ? new ActiveXObject("Microsoft.XMLHTTP") : new XMLHttpRequest();
    }
    if (x) {
        x.onreadystatechange = function() {
            if (x.readyState == 4 && x.status == 200) {
                text = x.responseText;
                $('#dialog').html(text);
            }
        }

        var formdata = $(elm).serialize();

        x.open("POST", $(elm).attr('action'), true);
        x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        x.setRequestHeader("Content-length", formdata.length);
        x.setRequestHeader("Connection", "close");
        x.send(formdata);
    }

    return false;
}

selectReadOnly = function ( select ) {
    select.selectedIndex = 1;
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
        if ( is_simple == 1 ) {
            var url = baseUri + '?presenter=Spisovka%3Asestavy&id=0&action=filtr&url='+elm.href;
        } else {         
            var url = baseUri + 'sestavy/0/filtr/?url='+elm.href;
        }
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
    if ( elm.pc_od.value != '' ) {param = param + 'pc_od=' + elm.pc_od.value + '&'}
    if ( elm.pc_do.value != '' ) {param = param + 'pc_do=' + elm.pc_do.value + '&'}
    if ( elm.d_od.value != '' )  {param = param + 'd_od=' + elm.d_od.value + '&'}
    if ( elm.d_do.value != '' )  {param = param + 'd_do=' + elm.d_do.value + '&'}
    if ( elm.d_today.checked )  {param = param + 'd_today=' + elm.d_today.value + '&'}
    if ( elm.rok.value != '' )   {param = param + 'rok=' + elm.rok.value}

    //window.location.href = elm.url.value + param;
    window.open(elm.url.value + param);
    $('#dialog').dialog('close');
    
    

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
    evidence = document.getElementById('evidence');
    if ( evidence == null ) {
        evidence = 0;
    } else {
        evidence = evidence.value;
    }

    if ( typ == 1 ) {

        if ( is_simple == 1 ) {
            var url = baseUri + '?presenter=Spisovka%3Adokumenty&id='+ dokument_id +'&action=cjednaciadd&spojit_s=';
        } else {         
            var url = baseUri + 'dokumenty/'+ dokument_id +'/cjednaciadd?spojit_s=';
        }
        var fnc = "pripojitDokument(this)";
    } else {
        if ( is_simple == 1 ) {
            var url = baseUri + '?presenter=Spisovka%3Aspojit&id='+ dokument_id +'&action=vybrano&spojit_s=';
        } else {            
            var url = baseUri + 'spojit/'+ dokument_id +'/vybrano?spojit_s=';
        }
        var fnc = "spojitDokument(this)";
    }

    for (var zaznam in data){
        if ( evidence == 1 ) {
            uevidence = '&evidence=1';
        } else {
            uevidence = '';
        }

        a = '<a href="'+ url + data[zaznam]['dokument_id'] + uevidence +'" onclick="'+fnc+'; return false;">';
        html = html + "<tr>";
        html = html + "<td>"+ a + data[zaznam]['cislo_jednaci'] +"</a></td>";
        html = html + "<td>"+ a + data[zaznam]['jid'] +"</a></td>";
        html = html + "<td>"+ a + data[zaznam]['nazev'] +"</a></td>";
        html = html + "</tr>";
    }
    return html;

}

function select_set_value(SelectObject, Value) {
  for(index = 0; 
    index < SelectObject.length; 
    index++) {
   if(SelectObject[index].value == Value)
     SelectObject.selectedIndex = index;
   }
}

function htmlspecialchars (string, quote_style, charset, double_encode) {
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
    
    if ( typeof string == "object" ) return "";

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

zmen_rezim_subjektu = function() {

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
    var url;
    if ( is_simple == 1 )
        url = baseUri + '?presenter=Spisovka%3Asubjekty&id='+subjekt_id+'&action=zmenrezim&';
        
    else
        url = baseUri + 'subjekty/'+subjekt_id+'/zmenrezim?';
    url += 'dok_id='+document.getElementById('subjekt_dokument_id').value+'&typ='+rezim;    

    $.get(url, '', function(data) {
        renderSubjekty(document.getElementById('subjekt_dokument_id').value);
    }, 'text');
    
}