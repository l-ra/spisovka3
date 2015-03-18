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

function showSpinner() {
    $("#ajax-spinner").show();
}
function hideSpinner() {
    $("#ajax-spinner").hide();
}
function dialogSpinner()
{
    return $('<div class="dialog-spinner"></div>');    
}


$(function() {

    InstallDatePicker();
    initSelect2();
    
    /*
     * Nastaveni spinneru. Je vzdy pritomen, standarne ale je schovan.
     */
    $('<div id="ajax-spinner"></div>').appendTo("body").hide();
    
    $(document).ajaxStop(function () {
        // při události ajaxStop spinner schovám
        hideSpinner();
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
        dialog(this,'Spisy');
        return false;
    });

    // Dialog - Vyber spisu
    $('#dialog-zmocneni').click(function(event){
        dialog(this,'Připojit zmocnění');
        return false;
    });

    // Dialog - Vyber subjektu
    $('#dialog-subjekt').click(function(event){
        dialog(this,'Subjekt');
        return false;
    });
    
    // Dialog - Vyber subjektu
    $('#subjekt_pripojit_click').click(function(event){
        $('#subjekt_pripojit').show();
        $('#subjekt_autocomplete').focus();        
        return false;
    });    

    // Dialog - Vyber subjektu
    $('#dialog-pridat-prilohu').click(function(event){
        dialog(this,'Přidat přílohu');
        return false;
    });

    // Dialog - Vyber zamestnance pro predani
    $('#dialog-uzivatel').click(function(event){
        dialog(this,'Předat dokument organizační jednotce nebo zaměstnanci');
        return false;
    });
    
    // Dialog - Vyber org pro predani spisu
    $('#dialog-predatspis').click(function(event){
        dialog(this,'Předat spis organizační jednotce nebo zaměstnanci');
        return false;
    });    

    // Dialog - Vyber zamestnance pro predani
    $('#dialog-spojit').click(function(event){
        dialog(this,'Spojit s dokumentem');
        return false;
    });

    // Dialog - Historie
    $('#dialog-historie').click(function(event){
        dialog(this,'Historie - Transakční protokol');
        return false;        
    });

    // Dialog - pripojit k archu
    $('#dialog-cjednaci').click(function(event){
        dialog(this,'Vložit do spisu');
        return false;
    });

    // Dialog - hledat
    $('#dialog-search').click(function(event){
        dialog(this,'Pokročilé vyhledávání');
        return false;
    });


    $('#predat_autocomplete').autocomplete({
        minLength: 3,
        /*source: seznam_uzivatelu,*/
        source: (is_simple==1)?BASE_URL + '?presenter=Spisovka%3Auzivatel&action=seznamAjax':BASE_URL + 'uzivatel/seznamAjax',

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
        source: (is_simple==1)?BASE_URL + '?presenter=Spisovka%3Asubjekty&action=seznamAjax':BASE_URL + 'subjekty/0/seznamAjax',

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
                
                if ( is_simple == 1 ) {
                    url = BASE_URL + '?presenter=Spisovka%3Asubjekty&id='+ui.item.id+'&action=vybrano&dok_id='+document.getElementById('subjekt_dokument_id').value+'&typ=' + typ_code + '&autocomplete=1';                    
                } else {
                    url = BASE_URL + 'subjekty/'+ui.item.id+'/vybrano?dok_id='+document.getElementById('subjekt_dokument_id').value+'&typ=' + typ_code + '&autocomplete=1';
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
        $('input[name^=dokument_vyber]').prop('checked',true);
        $('input[name^=spis_vyber]').prop('checked',true);
        $('input[name^=zapujcka_vyber]').prop('checked',true);
    });
    $('#checkbox_all_off').click(function(event) {
        $('input[name^=dokument_vyber]').prop('checked',false);
        $('input[name^=spis_vyber]').prop('checked',false);
        $('input[name^=zapujcka_vyber]').prop('checked',false);
    });
    
});

/*
 *  Vyvolani dialogu
 *
 *
 */
dialog = function ( elm, title, url ) {

    if ( typeof title == 'null' )
        title = 'Dialogové okno';
        
    $('#dialog').dialog( "option", "title", title );
    
    // Interni spinner v dialogu - bude nahrazen, az se obsah dialogu nahraje
    $('#dialog').html(dialogSpinner());
    $('#dialog').bind('dialogclose', function(event) {
        // Dialog pouziva lokalni spinner, takze by nemelo byt treba resit ten globalni.
        // Ale bohuzel ne vsechen kod zatim pouziva jQuery Ajax interface
        // Pri zavreni dialogu skryj globalni spinner, aby nebezel donekonecna
        hideSpinner();
    });
    $('#dialog').dialog('open');

    if (typeof url == 'undefined')
        url = elm.href;
    
    $.get(url, function(data) {
        $('#dialog').html(data);
    });
    
    return false;
}

reloadDialog = function (elm) {

    $('#dialog').html(dialogSpinner());
    $.get(elm.href, function(data) {
        $('#dialog').html(data);
    });
    
    return false;
}

/*
 * ARES
 *
 */
aresSubjekt = function ( formName ) {

    var frmIC = document.getElementById('frm'+formName+'-ic');
    IC = frmIC.value;
    if (!IC) {
        alert('Vyplňte IČ subjektu.');
        return false; // Je-li pole IC prázdné, nevolej neplatné URL
    }
    
    if ( is_simple == 1 ) {
        var url = BASE_URL + '?presenter=Spisovka%3Asubjekty&id=' + IC +'&action=ares';
    } else {    
        var url = BASE_URL + 'subjekty/' + IC +'/ares';
    }
    //alert( url );
    
    showSpinner();

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

    return false;
}

/*
 * ISDS - vyhledat subjekt na zaklade id  schranky
 *
 */
isdsSubjekt = function ( formName ) {

    var frmID = document.getElementById('frm'+formName+'-id_isds');
    ID = frmID.value;
    if (!ID) {
        alert('Zadejte ID datové schránky.');
        return false;
    }

    //var url = BASE_URL + '/subjekty/ares/' + frmIC.value;
    if ( is_simple == 1 ) {
        var url = BASE_URL + '?presenter=Spisovka%3Asubjekty&id=' + frmID.value +'&action=isdsid';
    } else {    
        var url = BASE_URL + 'subjekty/' + frmID.value +'/isdsid';
    }
    //alert( url );

    $.getJSON(url, function(data) {

        if ( data == null ) {
            alert('Záznam neexistuje nebo bylo zadáno chybné ID datové schránky.');
        } else if ( data.error ) {
            alert(data.error);
        } else {
            
            $("#frm"+formName+"-type option[value="+data.dbType+"]").prop('selected', true);
            
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

    showSpinner();

    return false;

}

/*
 * CRON - zpracovani na pozadi
 *
 */
ajaxcron = function () {
    
    if (!(Math.random() < 0.1))
        return false;
    
    if ( is_simple == 1 ) {
        var url = BASE_URL + '?presenter=Spisovka%3Acron&action=spustit';
    } else {    
        var url = BASE_URL + 'cron/spustit';
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

    showSpinner();
    
    $.get(elm.href, function(data) {
        if ( data.indexOf('###vybrano###') != -1 ) {
            data = data.replace('###vybrano###','');
            $('#dok_spis').html(data);
            $('#dialog').dialog('close');
        } else {
            alert(data);
        }
    });

    return false;
}

subjektVybran = function (elm) {

    showSpinner();
    
    $.get(elm.href, function(data) {
        if ( data.indexOf('###vybrano###') != -1 ) {
            data = data.replace('###vybrano###','');
            $('#dialog').dialog('close');
            renderSubjekty(data);
        } else {
            alert(data);
        }
    });

    return false;
}


renderPrilohy = function (dokument_id) {

    showSpinner();

    if ( is_simple == 1 ) {
        url = BASE_URL + '?presenter=Spisovka%3Aprilohy&id='+ dokument_id +'&action=nacti';
    } else {    
        url = BASE_URL + 'prilohy/'+ dokument_id +'/nacti';
    }

    $.get(url, function(data) {
        $('#dok-prilohy').html(data);
    });

    return false;
}

renderSubjekty = function (dokument_id) {

    showSpinner();

    if ( is_simple == 1 ) {
        url = BASE_URL + '?presenter=Spisovka%3Asubjekty&id='+ dokument_id +'&action=nacti';
    } else {    
        url = BASE_URL + 'subjekty/'+ dokument_id +'/nacti';
    }

    $.get(url, function(data) {
        $('#dok-subjekty').html(data);
    });

    return false;
}


novySubjekt = function (elm) {

    return dialog(elm, 'Nový subjekt');
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

        postForm(x, $("#subjekt-vytvorit"));
    }

    return false;
}

subjektzmenit = function(elm){

    return dialog(elm, 'Subjekt');
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

    if ( is_simple == 1 ) {
        url = BASE_URL + '?presenter=Spisovka%3Asubjekty&id=0&action=vyber&dok_id='+doc_id;
    } else {    
        url = BASE_URL + 'subjekty/0/vyber?dok_id='+doc_id;
    }

    dialog(null, 'Subjekt', url);   
    return false;
}

subjektNovy = function(event) {

        id = document.getElementById('subjekt_dokument_id').value;

        if ( is_simple == 1 ) {
            url_ajaxtyp = BASE_URL + '?presenter=Spisovka%3Asubjekty&id=0&action=seznamtypusubjektu';
        } else { 
            url_ajaxtyp = BASE_URL + 'subjekty/0/seznamtypusubjektu';
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
            url_ajaxtyp = BASE_URL + '?presenter=Spisovka%3Asubjekty&id=0&action=seznamstatuajax';
        } else { 
            url_ajaxtyp = BASE_URL + 'subjekty/0/seznamstatuajax';
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

                var form = document.forms["frm-novyForm"];
                if ( typeof form == "undefined" )
                    form = document.forms["frm-odpovedForm"];
                if ( typeof form == "undefined" )
                    form = document.forms["frm-novySubjekt"];
                    
                var formdata = '';                
                if ( typeof form != "undefined" )
                    formdata = 'id='+id+'&' + $(form).serialize();
                
                if ( is_simple == 1 ) {
                    x.open("POST", BASE_URL + '?presenter=Spisovka%3Asubjekty&id=0&action=vytvoritAjax', true);
                } else { 
                    x.open("POST", BASE_URL + 'subjekty/0/vytvoritAjax', true);
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

        postForm(x, $("#spis-vytvorit"));
    }

    return false;
}

spisVytvoritStorno = function (doc_id) {

    $('#spis_novy').hide();
    return false;
}

osobaVybrana = function (elm) {

    poznamka = document.getElementById('frmpred-poznamka').value;
    poznamka = encodeURI(poznamka);
    url = elm.href + '&poznamka='+ poznamka;

    showSpinner();
    
    $.get(url, function(data) {
        if ( data.indexOf('###vybrano###') != -1 ) {
            data = data.replace('###vybrano###','');
            $('#dialog').dialog('close');
            location.href = data;
        } else if ( data.indexOf('###predano###') != -1 ) {
            data = data.replace('###predano###','');
            part = data.split('#');
            $('#frmnovyForm-predano_user').val(part[1]);
            $('#frmnovyForm-predano_org').val(part[2]);
            $('#frmnovyForm-predano_poznamka').val(part[3]);
            $('#predano').html("<dl class=\"detail_item\"><dt>Předáno:</dt><dd>"+part[5]+"<br />"+part[4]+"</dd></dl><dl class=\"detail_item\"><dt>Poznámka pro předávajícího:</dt><dd>"+part[3]+"&nbsp;</dd></dl>");
            $('#dialog').dialog('close');
        } else {
            $('#dialog').html(data);
        }
    });

    return false;
}


prilohazmenit = function(elm){

    return dialog(elm, 'Upravit přílohu');
}

odebratPrilohu = function(elm, dok_id){

    if ( confirm('Opravdu chcete odebrat přílohu z dokumentu?') ) {

        $.get(elm.href, function(data) {
            renderPrilohy(dok_id);
        });
    }

    return false;
}

odebratSubjekt = function(elm, dok_id){

    if ( confirm('Opravdu chcete odebrat subjekt z dokumentu?') ) {

        $.get(elm.href, function(data) {
            renderSubjekty(dok_id);
        });
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

        postForm(x, $("#vypravna_form"));        
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

    showSpinner();

    if ( is_simple == 1 ) {
        var url = BASE_URL + '?presenter=Spisovka%3Aspojit&id=0&action=nacti&q=' + vyraz;
    } else
        var url = BASE_URL + 'spojit/0/nacti?q=' + vyraz;
    
    $.get(url, function(data) {
        var vysledek = document.getElementById('vysledek');
        
        if ( data == '' ) {
            vysledek.innerHTML = '<div class="prazdno">Nebyly nalezeny žádné dokumenty odpovídající dané sekvenci.</div>';
        } else if ( data == 'prilis_mnoho' ) {
            vysledek.innerHTML = '<div class="prazdno">Daná sekvence obsahuje příliš mnoho záznamů. Zkuste zvolit přesnější sekvenci.</div>';                    
        } else {
            var seznam = eval('(' + data + ')');
            cache[vyraz] = data;
            vysledek.innerHTML = nastylovat(seznam, typ);
        }
    });
    
    return false;
}

spojitDokument = function (elm) {

    showSpinner();
    
    $.get(elm.href, function(data) {
        if ( data.indexOf('###vybrano###') != -1 ) {
            data = data.replace('###vybrano###','');
            $('#dok_spojit').html(data);
            $('#dialog').dialog('close');
        } else {
            $('#dialog').html(data);
        }
    });

    return false;
}

pripojitDokument = function (elm) {

    showSpinner();
    
    $.get(elm.href, function(data) {
        if ( data.indexOf('###vybrano###') != -1 ) {
            data = data.replace('###vybrano###','');

            part = data.split('#');

            //alert(data +' - '+ part[1]);

            $('#dok_cjednaci').html(part[0]);
            document.getElementById('frmnovyForm-poradi').value = part[1];
            document.getElementById('frmnovyForm-odpoved').value = part[2];

            //elm.href = url;
            $('#dialog').dialog('close');
        } else if ( data.indexOf('###zaevidovano###') != -1 ) {
            data = data.replace('###zaevidovano###','');
            window.location = data;
            //elm.href = url;
            $('#dialog').dialog('close');

        } else {
            $('#dialog').html(data);
        }
    });

    return false;                
}


overitISDS = function (elm) {
    
    return dialog(elm,'Ověření datové zprávy');
    
}


selectReadOnly = function ( select ) {
    select.selectedIndex = 1;
    return false;
}

filtrSestavy = function (elm) {

    if ( is_simple == 1 ) {
        re = /id=([0-9]+)/;
        var matched = re.exec(elm.href);
        var url = BASE_URL + '?presenter=Spisovka%3Asestavy&id='+ matched[1] +'&action=filtr';
    } else {         
        re = /sestavy\/([0-9]+)/;
        var matched = re.exec(elm.href);
        var url = BASE_URL + 'sestavy/'+ matched[1] +'/filtr/';
    }

    return dialog(elm, 'Filtr', url);
}

zobrazSestavu = function (elm) {

    var param = is_simple == 1 ? '&' : '?';
    
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

    dokument_id = document.getElementById('dokumentid').value;
    evidence = document.getElementById('evidence');
    if ( evidence == null ) {
        evidence = 0;
    } else {
        evidence = evidence.value;
    }

    if ( typ == 1 ) {

        if ( is_simple == 1 ) {
            var url = BASE_URL + '?presenter=Spisovka%3Adokumenty&id='+ dokument_id +'&action=vlozitdosbernehoarchu&vlozit_do=';
        } else {         
            var url = BASE_URL + 'dokumenty/'+ dokument_id +'/vlozitdosbernehoarchu?vlozit_do=';
        }
        var fnc = "pripojitDokument(this)";
    } else {
        if ( is_simple == 1 ) {
            var url = BASE_URL + '?presenter=Spisovka%3Aspojit&id='+ dokument_id +'&action=vybrano&spojit_s=';
        } else {            
            var url = BASE_URL + 'spojit/'+ dokument_id +'/vybrano?spojit_s=';
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
        url = BASE_URL + '?presenter=Spisovka%3Asubjekty&id='+subjekt_id+'&action=zmenrezim&';        
    else
        url = BASE_URL + 'subjekty/'+subjekt_id+'/zmenrezim?';
    url += 'dok_id='+document.getElementById('subjekt_dokument_id').value+'&typ='+rezim;    

    $.get(url, '', function(data) {
        renderSubjekty(document.getElementById('subjekt_dokument_id').value);
    }, 'text');
    
}
/**
 * Initializuje Select2 widgety na prvcich select s atributem data-widget-select2=1
 * kontroluje zda je funkce dostupna
 * @returns {void}
 */
initSelect2 = function() {
    if (typeof $().select2 !== 'undefined') {
        $('select:not(has-select2-widget)[data-widget-select2=1]').each(function() {

            var options = $(this).data('widget-select2-options') || {};

            $(this).select2(options).attr('has-select2-widget', 1);
        });
    }
};

vybratSpisovyZnak = function(element) {

    var formName = $(element).parents('form').first().attr('name');
   
    var key = document.forms[formName].spisovy_znak_id.selectedIndex;
    var value = document.forms[formName].spisovy_znak_id.options[key].value;
    document.forms[formName].skartacni_znak.value = spisz_skart[value];
    document.forms[formName].skartacni_lhuta.value = spisz_lhuta[value];
    //document.forms['frm-novyForm'].spousteci_udalost.value = spisz_udalost[value];
    select_set_value(document.forms[formName].spousteci_udalost_id, spisz_udalost[value]);
    return true;
};

postForm = function (x, form) {

    if (!(form instanceof jQuery))
        form = $(form);
        
    if (x) {
        var formdata = form.serialize();

        x.open("POST", form.attr('action'), true);
        x.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        x.setRequestHeader("Content-length", formdata.length);
        x.setRequestHeader("Connection", "close");
        x.send(formdata);
    }
}

