/******************************************
 * MArcomage JavaScript - Players section *
 ******************************************/

'use strict';

$(document).ready(function() {
    var notification = dic().notificationsManager();
    var confirmed = false;

    // apply player filters by pressing ENTER key
    $('input[name="pname_filter"]').keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();
            $('button[name="players_apply_filters"]').click();
        }
    });

    // admin actions confirmation
    $('button[name="change_access"], button[name="reset_password"], button[name="reset_avatar_remote"], button[name="reset_exp"], button[name="add_gold"], button[name="delete_player"], button[name="rename_player"]').click(function() {
        // action was already approved
        if (confirmed) {
            return true;
        }

        var triggerButton = $(this);

        // request confirmation
        notification.displayConfirm('Action confirmation', 'Do you really want to ' + $(this).html() + '?', function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

});
