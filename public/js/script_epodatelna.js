var loaded_data = new Array();

$(function() {

    $('#dialog-evidence').click(function(event){
        dialog(this,'Evidovat');
        return false;
    });

    $('#dialog-odmitnout').click(function(event){
        dialog(this,'Odmítnout zprávu');
        return false;
    });

    $('#subjekt_epod_autocomplete').autocomplete({
        minLength: 3,
        source: (is_simple==1)?BASE_URL + '?presenter=Spisovka%3Asubjekty&action=seznamAjax':BASE_URL + 'subjekty/0/seznamAjax',

        focus: function(event, ui) {
            $('#subjekt_epod_autocomplete').val(ui.item.nazev);
            return false;
        },
        select: function(event, ui) {
            $('#subjekt_epod_autocomplete').val('');

            renderEpodSubjekty(ui.item.id);

            return false;
        }
    });

    $('#subjekt_novy').on('click', '#epod_evid_novysubjekt_click', function(event) {    
        event.preventDefault();

        id = document.getElementById('frmnovyForm-epodatelna_id').value;

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

        //alert('subjekt_original');

        var select_stat = '<select name="stat['+id+']">';
        $.each(seznamStatu, function(key, val) {
            select_stat = select_stat + '<option value="' + key + '">' + val + '</option>';
        });       
        select_stat = select_stat + "</select>";

        novy_subjekt = ''+
'                        <dt>Typ subjektu:</dt>'+
'                        <dd id="typ_subjektu"></dd>'+
'                        <dt>Název subjektu:</dt>'+
'                        <dd><input type="text" name="subjekt_nazev['+id+']" value="'+ ((typeof subjekt_original['nazev_subjektu'] != 'undefined')?htmlspecialchars(subjekt_original['nazev_subjektu']):"") +'" size="60" /></dd>'+
'                        <dt>Titul před, jméno, příjmení, titul za:</dt>'+
'                        <dd><input type="text" name="subjekt_titulpred['+id+']" value="" size="5" /><input type="text" name="subjekt_jmeno['+id+']" value="" size="20" /><input type="text" name="subjekt_prijmeni['+id+']" value="'+ ((typeof subjekt_original['prijmeni'] != 'undefined')?htmlspecialchars(subjekt_original['prijmeni']):"") +'" size="40" /><input type="text" name="subjekt_titulza['+id+']" value="" size="5" /></dd>'+
'                        <dt>Ulice a číslo popisné:</dt>'+
'                        <dd><input type="text" name="subjekt_ulice['+id+']" value="'+ ((typeof subjekt_original['adresa_ulice'] != 'undefined')?htmlspecialchars(subjekt_original['adresa_ulice']):"") +'" size="20" /><input type="text" name="subjekt_cp['+id+']" value="" size="10" /></dd>'+
'                        <dt>PSČ a Město:</dt>'+
'                        <dd><input type="text" name="subjekt_psc['+id+']" value="" size="6" /><input type="text" name="subjekt_mesto['+id+']" value="" size="50" /></dd>'+
'                        <dt>Stát:</dt>'+
'                        <dd>'+select_stat+'</dd>'+
'                        <dt>Email:</dt>'+
'                        <dd><input type="text" name="subjekt_email['+id+']" value="'+ ((typeof subjekt_original['email'] != 'undefined')?htmlspecialchars(subjekt_original['email']):"") +'" size="60" /></dd>'+
'                        <dt>ID datové schránky:</dt>'+
'                        <dd><input type="text" name="subjekt_isds['+id+']" value="'+ ((typeof subjekt_original['id_isds'] != 'undefined')?htmlspecialchars(subjekt_original['id_isds']):"") +'" size="30" /></dd>'+
'                        <dt>&nbsp;</dt>'+
'                        <dd><input type="submit" name="subjekt_pridat['+id+']" value="Vytvořit a přidat" id="subjekt_pridat" /></dd>';

        $('#subjekt_novy').html(novy_subjekt);

        $("#subjekt_pridat").click(function() {

            var formdata = 'id='+id+'&' + $(document.forms["frm-novyForm"]).serialize();

            if ( is_simple == 1 ) {
                url = BASE_URL + '?presenter=Epodatelna%3Asubjekty&action=vytvoritAjax';
            } else {  
                url = BASE_URL + 'epodatelna/subjekty/vytvoritAjax';
            }
            
            $.post(url, formdata, function (text) {
                if ( text[0] == "#" ) {
                    text = text.substr(1);
                    alert(text);
                } else {
                    part = text.split("#");
                    $('#subjekt_novy').html(
                        '<dt>&nbsp;</dt>'+
                        '<dd><a href="" id="epod_evid_novysubjekt_click">Vytvořit nový subjekt z odesílatele</a></dd>'
                    );

                    renderEpodSubjekty(part[0]);

                    alert('Subjekt byl vytvořen a přidán mezi nalezené subjekty.');

                }
            });

            return false;
        });

        return false;
    });


});


epodSubjektVytvorit = function (elm) {

    postFormJ($("#subjekt-vytvorit"), function (data) {
        if ( data.indexOf('###zmeneno###') != -1 ) {
            data = data.replace('###zmeneno###','');
            $('#dialog').dialog('close');
            renderEpodSubjekty(data);
        } else {
            $('#dialog').html(data);
        }        
    });

    return false;
}

epodSubjektNovySubmit = function (elm) {
    epodSubjektVytvorit(elm);
    return false;    
}

epodSubjektNovyStorno = function () {

    $('#dialog').html(dialogSpinner());

    if ( is_simple == 1 ) {
        url = BASE_URL + '?presenter=Epodatelna%3Asubjekty&action=vyber';
    } else {    
        url = BASE_URL + 'epodatelna/subjekty/vyber';
    }
    
    $.get(url, function(data) {
        $('#dialog').html(data);
    });
    
    return false;
}

renderEpodSubjekty = function (subjekt_id) {

    showSpinner();
    
    if ( is_simple == 1 ) {
        url = BASE_URL + '?presenter=Epodatelna%3Asubjekty&action=nacti&id=' + subjekt_id;
    } else {  
        url = BASE_URL + 'epodatelna/subjekty/nacti/'+ subjekt_id;
    }

    $.get(url, function(data) {
        subjekty_table = document.getElementById('subjekty-table');
        
        if ( subjekty_table == null ) {
            html =        '        <table class="seznam" id="subjekty-table">';
            html = html + '           <tr>';
            html = html + '               <td class="icon">Použít</td>';
            html = html + '               <td class="icon">&nbsp;</td>';
            html = html + '               <td class="meta">&nbsp;</td>';
            html = html + '               <td class="meta_plus">&nbsp;</td>';
            html = html + '           </tr>';
            html = html + data;
            html = html + '        </table>';
            $('#dok-subjekty').html(html);
        } else {

            subjekt_tr = document.getElementById('epodsubjekt-'+subjekt_id);
            if ( subjekt_tr != null ) {
                $(subjekt_tr).replaceWith(data);
            } else {
                // append
                $('#subjekty-table tbody').append(data);
            }

            
        }
    });
    
    return false;
}

epodSubjektVybran = function (elm, subjekt_id) {

    $('#dialog').dialog('close');
    elm.href = "javaScript:void(0);"; // IE fix - zabraneni nacteni odkazu
    renderEpodSubjekty(subjekt_id);
}

zkontrolovatSchranku = function (elm) {

    $('#zkontrolovat_status').html('<img src="'+PUBLIC_URL+'images/spinner.gif" width="14" height="14" />&nbsp;&nbsp;&nbsp;Kontroluji schránky ...');

    if ( is_simple == 1 ) {
        url = BASE_URL + '?presenter=Epodatelna%3Adefault&action=zkontrolovatAjax';
    } else {  
        url = BASE_URL + 'epodatelna/default/zkontrolovatAjax';
    }

    $.get(url, function(data) {
        $('#zkontrolovat_status').html(data);
        // nacteni novych zprav z database se musi provest az po stazeni vsech zprav z emailove schranky
        nactiZpravy();
    }).fail(function(data) {
        $('#zkontrolovat_status').html('Při kontrole zpráv došlo k chybě ' + x.status);
    });    
}

zkontrolovatOdchoziSchranku = function (elm) {

    if ( is_simple == 1 ) {
        url = BASE_URL + '?presenter=Epodatelna%3Adefault&action=zkontrolovatOdchoziISDS';
    } else {  
        url = BASE_URL + 'epodatelna/default/zkontrolovatOdchoziISDS';
    }

    // zde není potřeba žádná zpětná vazba
    $.get(url);
}


nactiZpravy = function () {

    showSpinner();
    
    if ( is_simple == 1 ) {
        url = BASE_URL + '?presenter=Epodatelna%3Adefault&action=nactiNoveAjax';
    } else {  
        url = BASE_URL + 'epodatelna/default/nactiNoveAjax';
    }
    $.get(url, function(data) {
        var zpravy = eval("(" + data + ")");
        if ( zpravy != '' ) {
            
            for(index in zpravy) {
                if ( in_array(index, loaded_data) == true ) {
                    continue;
                }
                generujZpravu( index, zpravy[index] );
                loaded_data[ index ] = index;
            }
        }
    });    
}

function in_array (needle, haystack)
{
    var key = '';

    for (key in haystack) {
        if ( haystack[key] == needle ) {
            return true;
        }
    }
    return false;
}

function bytesToSize (bytes) {
  var sizes = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  if (bytes == 0) return 'n/a';
  var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
  return ((i == 0)? (bytes / Math.pow(1024, i)) : (bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + sizes[i];
};

generujZpravu = function ( id, data ) {

    typ = 0;
    if ( typeof data['email_id'] == "string" ) {
        typ = 1;
        typ_string = '<img src="'+BASE_URL+'images/icons/typdok1.png" alt="Email" title="Email" width="24" height="16" />';

        form_odmitnout = '                    <dl>'+
'                        <dt>&nbsp;</dt>'+
'                        <dd><input type="checkbox" name="odmitnout['+id+']" /> Poslat upozornění odesilateli?</dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>Email odesílatele:</dt>'+
'                        <dd><input type="text" name="zprava_email['+id+']" value="'+ data['odesilatel'] +'" size=60 /></dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>Předmět pro odesílatele:</dt>'+
'                        <dd><input type="text" name="zprava_predmet['+id+']" value="RE: '+ data['predmet'] +'" size=60 /></dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>Zpráva pro odesilatele:</dt>'+
'                        <dd><textarea name="zprava_odmitnuti['+id+']" rows="3" cols="60"></textarea></dd>'+
'                    </dl>';

    } else if ( typeof data['isds_id'] == "string" ) {
        typ = 2;
        typ_string = '<img src="'+BASE_URL+'images/icons/typdok2.png" alt="ISDS" title="ISDS" width="24" height="16" />';
        form_odmitnout = '';
    } else {
        typ_string = '';
        form_odmitnout = '';
    }

    prilohy = '';
    if ( data['prilohy'].length > 0 ) {
        for ( key in data['prilohy'] ) {
            if ( is_simple == 1 ) {
                prilohy = prilohy + '                    <li><a href="'+BASE_URL+'?presenter=Epodatelna%3Aprilohy&action=download&id='+id+'&file='+ data['prilohy'][key]['id'] +'">'+ data['prilohy'][key]['name'] +'</a> [ '+ bytesToSize(data['prilohy'][key]['size']) +' ]</li>';
            } else { 
                prilohy = prilohy + '                    <li><a href="'+BASE_URL+'epodatelna/prilohy/download/'+id+'?file='+ data['prilohy'][key]['id'] +'">'+ data['prilohy'][key]['name'] +'</a> [ '+ bytesToSize(data['prilohy'][key]['size']) +' ]</li>';
            }
        }
    }
    subjekt_seznam = '';
    //alert(data['subjekt']['databaze']);
    if ( typeof data['subjekt']['databaze'] != 'null' ) {
        for ( key in data['subjekt']['databaze'] ) {
                subjekt_seznam = subjekt_seznam + '<input type="checkbox" name="subjekt['+id+']['+data['subjekt']['databaze'][key]['id']+']" />';
                subjekt_seznam = subjekt_seznam + data['subjekt']['databaze'][key]['full_name'] +'<br/>';
        }
    }
    //alert(subjekt_seznam);

    zprava = '   <div class="evidence_item"> '+
'       <div class="evidence_item_header">'+
'           '+ typ_string +
'           <div class="evidence_item_cas">'+
'               '+ data['doruceno_dne_datum'] +'<br/>'+ data['doruceno_dne_cas'] +''+
'           </div>'+
'           <div class="evidence_info">'+
'               <span class="nazev">'+ data['predmet'] +'</span>'+
'               '+ data['odesilatel'] +''+
'           </div>'+
'           <br class="clear" />'+
'       </div>'+
'       <div class="evidence_item_proccess" id="evidence_show_'+id+'">'+
'           <div class="evidence_volba">'+
'                Volba evidence:<br />'+
'                <input type="radio" name="volba_evidence['+id+']" value="0" title="Tato zpráva zůstane nezpracována. Bude zpracována později." /> Nedělat nic<br/>'+
'                <input type="radio" name="volba_evidence['+id+']" value="1" title="Zaeviduje zprávu do spisové služby."/> Evidovat<br/>'+
'                <input type="radio" name="volba_evidence['+id+']" value="2" title="Zaeviduje zprávu do jiné evidence. Nebude součásti spisové služby."/> Evidovat do jiné evidence<br/>'+
'                <input type="radio" name="volba_evidence['+id+']" value="3" title="Zpráva bude odmítnuta."/> Odmítnout<br/>'+
'            </div>'+
'            <div class="evidence_form">'+
'                <div class="evidence_zprava_toogle" id="evidence_zprava_toogle_'+id+'">Zobrazit zprávu >></div>'+
'                <div class="evidence_zprava" id="evidence_zprava_'+id+'">'+
data['popis'] +
'                </div><p>'+
'                <span>Přílohy:</span>'+
'                <ul id="evidence_prilohy_'+id+'">'+
prilohy +
'                </ul>'+
'                <div id="evidence_form_evidovat_'+id+'">'+
'                    <span>Subjekt:</span>'+
'                    <dl>'+
'                        <dt>Nalezené subjekty:</dt>'+
'                        <dd id="subjekt_seznam_'+id+'">'+
subjekt_seznam +
'                        &nbsp;</dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>Hledat subjekt:</dt>'+
'                        <dd class="ui-widget">'+
'                           <input type="text" name="subjekt_autocomplete['+id+']" id="subjekt_autocomplete_'+id+'" size="60" />'+
'                        </dd>'+
'                    </dl>'+
'                    <dl id="subjekt_novy_'+id+'">'+
'                        <dt>&nbsp;</dt>'+
'                        <dd><a href="" id="novysubjekt_click_'+id+'">Vytvořit nový subjekt z odesílatele</a></dd>'+
'                    </dl>'+
'                    <span>Zaevidovat do spisové služby.</span>'+
'                    <dl>'+
'                        <dt>Věc:</dt>'+
'                        <dd><input type="text" name="vec['+id+']" value="'+ data['predmet'] +'" size="60" /></dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>Popis:</dt>'+
'                        <dd><textarea name="popis['+id+']" rows="3" cols="60"></textarea></dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>Poznámka:</dt>'+
'                        <dd><textarea name="poznamka['+id+']" rows="5" cols="60">'+ data['popis'] +'</textarea></dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>Předat:</dt>'+
'                        <dd class="ui-widget">'+
'                           <input type="text" name="predat_autocomplete['+id+']" id="predat_autocomplete_'+id+'" size="60" />'+
'                           <input type="hidden" name="predat['+id+']" id="predat_'+id+'" />'+
'                        </dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>Poznámka k předání:</dt>'+
'                        <dd><textarea name="predat_poznamka['+id+']" rows="3" cols="60"></textarea></dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>&nbsp;</dt>'+
'                        <dd><input type="submit" name="evidovat['+id+']" value="Zaevidovat tuto zprávu" id="submit_evidovat_'+id+'" /></dd>'+
'                    </dl>'+
'                </div>'+
'                <div id="evidence_form_jina_evidence_'+id+'">'+
'                    <span>Zaevidovat zprávu do jiné evidence.</span>'+
'                    <dl class="detail_item">'+
'                        <dt>Evidence:</dt>'+
'                        <dd><textarea name="evidence['+id+']" rows="3" cols="60"></textarea></dd>'+
'                    </dl>'+
'                    <dl>'+
'                        <dt>&nbsp;</dt>'+
'                        <dd><input type="submit" name="evidovat_jinam['+id+']" value="Zaevidovat tuto zprávu do jiné evidence" id="submit_evidovat_jinam_'+id+'" /></dd>'+
'                    </dl>'+
'                </div>'+
'                <div id="evidence_form_odmitnout_'+id+'">'+
'                    <span>Odmítnout zprávu.</span>'+
'                    <dl>'+
'                        <dt>Důvod odmítnutí:</dt>'+
'                        <dd><textarea name="duvod_odmitnuti['+id+']" rows="3" cols="60"></textarea></dd>'+
'                    </dl>'+
form_odmitnout +
'                    <dl>'+
'                        <dt>&nbsp;</dt>'+
'                        <dd>'+
'                           <input type="hidden" name="odmitnout_typ['+id+']" value="'+typ+'" />'+
'                           <input type="submit" name="odmitnout['+id+']" value="Odmítnout tuto zprávu" id="submit_odmitnout_'+id+'" />'+
'                        </dd>'+
'                    </dl>'+
'                </div>'+
'            </div>'+
'            <br class="clear" />'+
'        </div>'+
'        <br class="clear" />'+
'    </div>';

    $('#h_evidence').append(zprava);

    $('#predat_autocomplete_'+id).autocomplete({
        minLength: 3,
        source: (is_simple==1)?BASE_URL + '?presenter=Spisovka%3Auzivatel&action=seznamAjax':BASE_URL + 'uzivatel/seznamAjax',

        focus: function(event, ui) {
            $('#predat_autocomplete_'+id).val(ui.item.nazev);
            return false;
        },
        select: function(event, ui) {
            $('#predat_'+id).val(ui.item.id);
            return false;
        }
    });

    $('#subjekt_autocomplete_'+id).autocomplete({
        minLength: 3,
        source: (is_simple==1)?BASE_URL + '?presenter=Spisovka%3Asubjekty&action=seznamAjax':BASE_URL + 'subjekty/0/seznamAjax',

        focus: function(event, ui) {
            $('#subjekt_autocomplete_'+id).val(ui.item.nazev);
            return false;
        },
        select: function(event, ui) {
            //$('#subjekt_'+id).val(ui.item.id);
            $('#subjekt_autocomplete_'+id).val('');

            subjekt_seznam = '<input type="checkbox" name="subjekt['+id+']['+ui.item.id+']" />';
            subjekt_seznam = subjekt_seznam + ui.item.full +'<br/>';
            $('#subjekt_seznam_'+id).append(subjekt_seznam);

            return false;
        }
    });
    
    //$('#subjekt_novy_'+id).delegate('click', '#novysubjekt_click_'+id, function(event) {    
    $('#novysubjekt_click_'+id).click(function(event) {    
        event.preventDefault();

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
            $('#typ_subjektu_'+id).html(typ_select);
        });   

        var select = '<select name="stat['+id+']">';
        $.each(seznamStatu, function(key, val) {
            select = select + '<option value="' + key + '">' + val + '</option>';
        });       
        select = select + "</select>";

        novy_subjekt = ''+
'                        <dt>Typ subjektu:</dt>'+
'                        <dd id="typ_subjektu_'+id+'"></dd>'+
'                        <dt>Název subjektu:</dt>'+
'                        <dd><input type="text" name="subjekt_nazev['+id+']" value="'+ ((typeof data['subjekt']['original']['nazev_subjektu'] != 'undefined')?data['subjekt']['original']['nazev_subjektu']:"") +'" size="60" /></dd>'+
'                        <dt>Jméno Příjmení:</dt>'+
'                        <dd><input type="text" name="subjekt_jmeno['+id+']" value="" size="20" /><input type="text" name="subjekt_prijmeni['+id+']" value="'+ ((typeof data['subjekt']['original']['prijmeni'] != 'undefined')?data['subjekt']['original']['prijmeni']:"") +'" size="40" /></dd>'+
'                        <dt>Ulice a číslo popisné:</dt>'+
'                        <dd><input type="text" name="subjekt_ulice['+id+']" value="'+ ((typeof data['subjekt']['original']['adresa_ulice'] != 'undefined')?data['subjekt']['original']['adresa_ulice']:"") +'" size="20" /><input type="text" name="subjekt_cp['+id+']" value="" size="10" /></dd>'+
'                        <dt>PSČ a Město:</dt>'+
'                        <dd><input type="text" name="subjekt_psc['+id+']" value="" size="6" /><input type="text" name="subjekt_mesto['+id+']" value="" size="50" /></dd>'+
'                        <dt>Stát:</dt>'+
'                        <dd>'+select+'</dd>'+
'                        <dt>Email:</dt>'+
'                        <dd><input type="text" name="subjekt_email['+id+']" value="'+ ((typeof data['subjekt']['original']['email'] != 'undefined')?data['subjekt']['original']['email']:"") +'" size="60" /></dd>'+
'                        <dt>ID datové schránky:</dt>'+
'                        <dd><input type="text" name="subjekt_isds['+id+']" value="'+ ((typeof data['subjekt']['original']['id_isds'] != 'undefined')?data['subjekt']['original']['id_isds']:"") +'" size="30" /></dd>'+
'                        <dt>&nbsp;</dt>'+
'                        <dd><input type="submit" name="subjekt_pridat['+id+']" value="Vytvořit a přidat" id="subjekt_pridat_'+id+'" /></dd>';


        $('#subjekt_novy_'+id).html(novy_subjekt);

        $("#subjekt_pridat_"+id).click(function() {

            var formdata = 'id='+id+'&' + $('#h_evidence').serialize();

            if ( is_simple == 1 ) {
                url = BASE_URL + '?presenter=Epodatelna%3Asubjekty&action=vytvoritAjax';
            } else { 
                url = BASE_URL + 'epodatelna/subjekty/vytvoritAjax';
            }

            $.post(url, formdata, function (text) {
                if ( text[0] == "#" ) {
                    text = text.substr(1);
                    alert(text);
                } else {

                    part = text.split("#");

                    $('#subjekt_novy_'+id).html('');

                    subjekt_seznam = '<input type="checkbox" name="subjekt['+id+']['+part[0]+']" />';
                    subjekt_seznam = subjekt_seznam + part[1] +'<br/>';
                    $('#subjekt_seznam_'+id).append(subjekt_seznam);

                    alert('Subjekt byl vytvořen a přidán mezi nalezené subjekty.');
                }                    
            });

            return false;
        });

        return false;
    });


    $('#evidence_form_evidovat_'+id).css('display','none');
    $('#evidence_form_jina_evidence_'+id).css('display','none');
    $('#evidence_form_odmitnout_'+id).css('display','none');
    $('#evidence_zprava_'+id).toggle();
    $('#evidence_zprava_toogle_'+id).click( function() {
        $('#evidence_zprava_'+id).toggle();
    });

    $("input[name^=volba_evidence]").click(function() {

        var element_id = $(this).attr('name');
        var value = $(this).val();
        //the initial starting point of the substring is based on "myboxes["
        var id = element_id.substring(15,element_id.length - 1)

        $('#evidence_form_evidovat_'+id).css('display','none');
        $('#evidence_form_jina_evidence_'+id).css('display','none');
        $('#evidence_form_odmitnout_'+id).css('display','none');

        if ( value == 0 ) {
        } else if ( value == 1 ) {
            $('#evidence_form_evidovat_'+id).css('display','block');
        } else if ( value == 2 ) {
            $('#evidence_form_jina_evidence_'+id).css('display','block');
        } else if ( value == 3 ) {
            $('#evidence_form_odmitnout_'+id).css('display','block');
        }
    });

    evidenceFormHandler = function() {

        var formdata = 'id='+id+'&' + $('#h_evidence').serialize();

        $.post($('#h_evidence').attr('action'), formdata, function (text) {
            if ( text[0] == "#" ) {
                text = text.substr(3);
                alert(text);
            } else {
                $('#evidence_show_'+id).html(text);
            }
        });

        return false;
    };
    
    $("#submit_evidovat_"+id).click(evidenceFormHandler);

    // handler je totozny, jak v predeslem pripade
    $("#submit_evidovat_jinam_"+id).click(evidenceFormHandler);

    $("#submit_odmitnout_"+id).click(evidenceFormHandler);

};
