/* global BASE_URL */

$(function() {

    $('#frmnovyForm-user_text').autocomplete({
        minLength: 3,
        source: BASE_URL + 'uzivatel/userSeznamAjax',
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
        source: BASE_URL + 'spisovna/zapujcky/seznamAjax',
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
