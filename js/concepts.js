/*******************************************
 * MArcomage JavaScript - Concepts section *
 *******************************************/

'use strict';

$(document).ready(function() {
    var notification = dic().notificationsManager();
    var confirmed = false;

    // apply card filters by pressing ENTER key
    $('input[name="card_name"]').keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();
            $('button[name="concepts_apply_filters"]').click();
        }
    });

    // card concept delete confirmation
    $('div.concepts-edit button[name="delete_concept"]').click(function() {
        // action was already approved
        if (confirmed) {
            // skip standard confirmation
            $('button[name="delete_concept"]').attr('name', 'delete_concept_confirm');
            return true;
        }

        var triggerButton = $(this);
        var message = 'Card concept data will be deleted. Are you sure you want to continue?';

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

    // file upload
    $('button[name="upload_concept_image"]').click(function() {
        var uploadedFile = $('input[name="concept_image_file"]');

        // no file was selected
        if (uploadedFile.val() == '') {
            // prompt user to select a file
            uploadedFile.click();
            return false;
        }
    });

});
