/* global BASE_URL, PUBLIC_URL, linkNovySubjekt */

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
        source: BASE_URL + 'subjekty/0/seznamAjax',

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

    var checkbox = '<input type="checkbox" name="subjekt['+id+']['+data.id+']" />';
    checkbox += data.name +'<br/>';
    $('#subjekt_seznam_'+id).append(checkbox);
};

renderEpodSubjekty = function (subjekt_id) {

    showSpinner();
    
    url = BASE_URL + 'epodatelna/subjekty/nacti/'+ subjekt_id;

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
};

epodSubjektVybran = function (elm, subjekt_id) {

    $('#dialog').dialog('close');
    elm.href = "javaScript:void(0);"; // IE fix - zabraneni nacteni odkazu
    renderEpodSubjekty(subjekt_id);
};

zkontrolovatSchranku = function (elm) {

    $('#zkontrolovat_status').html('<img src="'+PUBLIC_URL+'images/spinner.gif" width="14" height="14" />&nbsp;&nbsp;&nbsp;Kontroluji schránky ...');

    url = BASE_URL + 'epodatelna/default/zkontrolovatAjax';

    $.get(url, function(data) {
        $('#zkontrolovat_status').html(data);
        // nacteni novych zprav z database se musi provest az po stazeni vsech zprav z emailove schranky
        nactiZpravy();
    }).fail(function(data) {
        $('#zkontrolovat_status').html('Při kontrole zpráv došlo k chybě.');
    });    
};

zkontrolovatOdchoziSchranku = function (elm) {

    url = BASE_URL + 'epodatelna/default/zkontrolovatOdchoziISDS';

    // zde není potřeba žádná zpětná vazba
    $.get(url);
};


nactiZpravy = function () {

    showSpinner();
    
    url = BASE_URL + 'epodatelna/default/nactiNoveAjax';
    $.get(url, function(data) {
        var zpravy = eval("(" + data + ")");
        if ( zpravy != '' ) {
            
            for(var index in zpravy) {
                if ( in_array(index, loaded_data) == true ) {
                    continue;
                }
                generujZpravu( index, zpravy[index] );
                loaded_data[ index ] = index;
            }
        }
    });    
};

function in_array (needle, haystack)
{
    var key = '';

    for (key in haystack) {
        if ( haystack[key] == needle ) {
            return true;
        }
    }
    return false;
};

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
        typ_string = '<img src="'+PUBLIC_URL+'images/icons/typdok1.png" alt="Email" title="Email" width="24" height="16" />';

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
        typ_string = '<img src="'+PUBLIC_URL+'images/icons/typdok2.png" alt="ISDS" title="ISDS" width="24" height="16" />';
        form_odmitnout = '';
    } else {
        typ_string = '';
        form_odmitnout = '';
    }

    prilohy = '';
    if ( data['prilohy'].length > 0 ) {
        for ( var key in data['prilohy'] ) {
            prilohy = prilohy + '                    <li><a href="' + BASE_URL + 'epodatelna/prilohy/download/' + id + '?file=' + data['prilohy'][key]['id'] + '">' + data['prilohy'][key]['name'] + '</a> [ ' + bytesToSize(data['prilohy'][key]['size']) + ' ]</li>';
        }
    }
    subjekt_seznam = '';
    //alert(data['subjekt']['databaze']);
    if ( typeof data['subjekt']['databaze'] != 'null' ) {
        for ( var key in data['subjekt']['databaze'] ) {
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
'                        <dd><a href="' + linkNovySubjekt + '" id="novysubjekt_click_'+id+'">Vytvořit nový subjekt z odesílatele</a></dd>'+
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
        source: BASE_URL + 'uzivatel/seznamAjax',

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
        source: BASE_URL + 'subjekty/0/seznamAjax',

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
    
    // $.parseJSON(postdata);
    var postdata = JSON.stringify(data['subjekt']['original']);

    $('#novysubjekt_click_'+id).click(function(event) {    
        dialog(this, 'Nový subjekt', $(this).attr('href') + '&extra_data='+id);    
        return false;                
    }).attr('data-postdata', postdata);

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
        var id = element_id.substring(15,element_id.length - 1);

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
