/*******************************************
 * MArcomage JavaScript - Messages section *
 *******************************************/

import $ from 'jquery';

export default function () {

$(document).ready(function() {
    if (!$.dic.bodyData().isSectionActive('messages')) {
        return;
    }

    let notification = $.dic.notificationsManager();
    let confirmed = false;

    // apply message filters by pressing ENTER key
    $('input[name="name_filter"]').keypress(function(event) {
        if (event.keyCode === $.dic.KEY_ENTER) {
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

        let triggerButton = $(this);
        let message = 'Current message will be deleted. Are you sure you want to continue?';

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
        if ($('input[name*="mass_delete_"]:checked').length === 0) {
            notification.displayInfo('No messages selected for deletion', 'Please select at least one message.');
            return false;
        }

        // action was already approved
        if (confirmed) {
            return true;
        }

        let triggerButton = $(this);
        let message = 'All selected messages will be deleted. Are you sure you want to continue?';

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

    // select / deselect all messages button
    $('button[name="select_all_messages"]').click(function() {
        let checkboxes = $('input[type="checkbox"][name^="mass_delete_"]');

        // all checkboxes are checked - deselect all
        if (checkboxes.filter(':checked').length === checkboxes.length) {
            checkboxes.prop('checked', false);
        }
        // at least one checkbox is deselected - select all
        else {
            checkboxes.prop('checked', true);
        }
    });

});

}
