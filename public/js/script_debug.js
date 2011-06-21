$(function() {

    $('#debug').css({
        position: "absolute",
        marginLeft: 0, marginTop: 0,
        top: 0, left: 0,
        width: $(window).width()
    });

    /*$('#debug_head').click(function(event){
        event.preventDefault();
        $('#debug_body').toggle();
    });*/
    $('#debug_info').click(function(event){
        event.preventDefault();
        debug_toogle('debug_info');
        debug_hideAll();
        $('#debug_body_info').css('display','block');
    });
    $('#debug_route').click(function(event){
        event.preventDefault();
        debug_toogle('debug_body');
        debug_hideAll();
        $('#debug_body_route').css('display','block');
    });
    $('#debug_sql').click(function(event){
        event.preventDefault();
        debug_toogle('debug_sql');
        debug_hideAll();
        $('#debug_body_sql').css('display','block');
    });
    $('#debug_close').click(function(event){
        event.preventDefault();
        $('#debug').hide();
    });

    $('#debug_body').css({
        position: "absolute",
        marginLeft: 0, marginTop: 0,
        top: $('#debug_head').height()+10, left: 0,
        width: $(window).width(), height: ($(window).height()-$('#debug_head').height()+10),
        overflow: "scroll"
    });

    $('#debug_body').toggle();

});

function debug_toogle(elm) {
    if ( $('#debug_body').css('display') == 'none' ) {
        $('#debug_body').toggle();
    }
    else if ( $('#debug_body').css('display') != 'none' && $('#'+elm).css('display') != 'none' ) {
        $('#debug_body').toggle();
    }
}

function debug_hideAll() {

    $('#debug_body_info').css('display','none');
    $('#debug_body_route').css('display','none');
    $('#debug_body_sql').css('display','none');

}

