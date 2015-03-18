$(function() {

    $('#frmnovyForm-user_text').autocomplete({
        minLength: 3,
        source: (is_simple==1)?BASE_URL + '?presenter=Spisovka%3Auzivatel&action=userSeznamAjax':BASE_URL + 'uzivatel/userSeznamAjax',
    focus: function(event, ui) {
            $('#frmnovyForm-user_text').val(ui.item.nazev);
            return false;
        },
    select: function(event, ui) {
            $('#frmnovyForm-user_id').val(ui.item.id);
            return false;
    }
    });
    
    $('#frmnovyForm-dokument_text').autocomplete({
        minLength: 3,
        source: (is_simple==1)?BASE_URL + '?presenter=Spisovna%3Adokumenty&action=seznamAjax':BASE_URL + 'spisovna/dokumenty/0/seznamAjax',
    focus: function(event, ui) {
            $('#frmnovyForm-dokument_text').val(ui.item.nazev);
            return false;
        },
    select: function(event, ui) {
            $('#frmnovyForm-dokument_id').val(ui.item.id);
            return false;
    }
    });  

});
