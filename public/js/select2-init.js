/**
 * Initializuje Select2 widgety na prvcich select s atributem data-widget-select2=1
 * 
 * @returns {void}
 */
$(function() {
    //not(has-select2-widget) - ty kde jeste Select2 neni inicializovan
    $('select:not(has-select2-widget)[data-widget-select2=1]').each(function() {

        var options = $(this).data('widget-select2-options') || {};

        $(this).select2(options).attr('has-select2-widget', 1);
    });
});

