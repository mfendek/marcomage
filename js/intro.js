/******************************************
 * MArcomage JavaScript - intro functions *
 ******************************************/

'use strict';

$(document).ready(function() {
    var introDialog = $('#intro-dialog');

    introDialog.bind('dialogclose', function() {
        // case 1: there are no active games available - start a new game
        if ($('div#active-games table a.button').length == 0) {
            $('div#games button[name="quick_game"]').click();
        }
        // case 2: there are some active game available - enter the first game
        else {
            window.location.href = $('div#active-games table a.button:first').attr('href');
        }
    });

    // introduction dialog
    introDialog.dialog({
        autoOpen: true,
        show: 'fade',
        hide: 'fade',
        title: 'Introduction',
        width: 500,
        modal: true,
        draggable: false,
        closeOnEscape: true,
        resizable: false,
        buttons: {
            'Play now': function() {
                $('#intro-dialog').dialog('close');
            }
        }
    });

});
