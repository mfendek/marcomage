/*******************************************
 * MArcomage JavaScript - Settings section *
 *******************************************/

import $ from 'jquery';

export default function () {

$(document).ready(function() {
    let dic = $.dic;

    if (!dic.bodyData().isSectionActive('settings')) {
        return;
    }

    let notification = dic.notificationsManager();
    let confirmed = false;

    // purchase item (MArcomage shop)
    $('button[name="buy_item"]').click(function() {
        let selected = $('select[name="selected_item"]').val();

        // action was already approved
        if (confirmed) {
            return true;
        }

        let triggerButton = $(this);
        let message = 'Do you really want to purchase ' + $('#' + selected + '_desc').text() + '?';

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

        let triggerButton = $(this);

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
        let uploadedFile = $('input[name="avatar_image_file"]');

        // no file was selected
        if (uploadedFile.val() === '') {
            // prompt user to select a file
            uploadedFile.click();
            return false;
        }
    });

});

}
