/*******************************************
 * MArcomage JavaScript - Messages section *
 *******************************************/

'use strict';

$(document).ready(function() {
    var notification = dic().notificationsManager();
    var confirmed = false;

    // apply message filters by pressing ENTER key
    $('input[name="name_filter"]').keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();
            $('button[name="messages_apply_filters"]').click();
        }
    });

    // message delete confirmation
    $('button[name="message_delete"]').click(function() {
        // action was already approved
        if (confirmed) {
            // skip standard confirmation
            $('button[name="message_delete"]').attr('name', 'message_delete_confirm');
            return true;
        }

        var triggerButton = $(this);
        var message = 'Current message will be deleted. Are you sure you want to continue?';

        // request confirmation
        notification.displayConfirm('Action confirmation', message, function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

    // mass message delete confirmation
    $('button[name="delete_mass_messages"]').click(function() {
        // check if at least one message has been selected
        if ($('input[name*="mass_delete_"]:checked').length == 0) {
            notification.displayInfo('No messages selected for deletion', 'Please select at least one message.');
            return false;
        }

        // action was already approved
        if (confirmed) {
            return true;
        }

        var triggerButton = $(this);
        var message = 'All selected messages will be deleted. Are you sure you want to continue?';

        // request confirmation
        notification.displayConfirm('Action confirmation', message, function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

});
