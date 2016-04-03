/* global BASE_URL */

$(function () {

    $('#frmnovyForm-user_text').autocomplete({
        minLength: 3,
        source: BASE_URL + 'uzivatel/user-seznam-ajax',
        focus: function (event, ui) {
            return false;
        },
        select: function (event, ui) {
            $('#frmnovyForm-user_id').val(ui.item.id);
        }
    });

    $('#frmnovyForm-dokument_text').autocomplete({
        minLength: 3,
        source: BASE_URL + 'spisovna/zapujcky/seznam-ajax',
        focus: function (event, ui) {
            return false;
        },
        select: function (event, ui) {
            $('#frmnovyForm-dokument_id').val(ui.item.id);
        }
    });

});
