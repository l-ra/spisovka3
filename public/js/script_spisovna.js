$(function() {

    $('#frmnovyForm-user_text').autocomplete({
        minLength: 3,
        source: baseUri + 'uzivatel/userSeznamAjax',
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
        source: baseUri + 'spisovna/dokumenty/0/seznamAjax',
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
