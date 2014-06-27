/**
 * AJAX Nette Framwork plugin for jQuery
 *
 * @copyright   Copyright (c) 2009 Jan Marek
 * @license     MIT
 * @link        http://nettephp.com/cs/extras/jquery-ajax
 * @version     0.2
 */

jQuery.extend({
        updateSnippet: function(id, html) {
                $("#" + id).html(html);
        },

        netteCallback: function(data) {
                // přesměrování
                if (data.redirect) {
                        window.location.href = data.redirect;
                }

                // snippety
                if (data.snippets) {
                        for (var i in data.snippets) {
                                jQuery.updateSnippet(i, data.snippets[i]);
                        }
                }
        }
});


jQuery.ajaxSetup({
        success: function (data) {
                jQuery.netteCallback(data);
        },
        dataType: "json"
});