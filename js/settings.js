/*******************************************
 * MArcomage JavaScript - Settings section *
 *******************************************/

'use strict';

$(document).ready(function() {
    var notification = dic().notificationsManager();
    var confirmed = false;

    // purchase item (MArcomage shop)
    $('button[name="buy_item"]').click(function() {
        var selected = $('select[name="selected_item"]').val();

        // action was already approved
        if (confirmed) {
            return true;
        }

        var triggerButton = $(this);
        var message = 'Do you really want to purchase ' + $('#' + selected + '_desc').text() + '?';

        // request confirmation
        notification.displayConfirm('Purchase confirmation', message, function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

    // skip tutorial
    $('button[name="skip_tutorial"]').click(function() {
        // action was already approved
        if (confirmed) {
            return true;
        }

        var triggerButton = $(this);

        // request confirmation
        notification.displayConfirm('Action confirmation', 'Do you really want to skip tutorial?', function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

    // file upload
    $('button[name="upload_avatar_image"]').click(function() {
        var uploadedFile = $('input[name="avatar_image_file"]');

        // no file was selected
        if (uploadedFile.val() == '') {
            // prompt user to select a file
            uploadedFile.click();
            return false;
        }
    });

});
