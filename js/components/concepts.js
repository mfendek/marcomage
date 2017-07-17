/*******************************************
 * MArcomage JavaScript - Concepts section *
 *******************************************/

import $ from 'jquery';

export default function () {

$(document).ready(function() {
    let dic = $.dic;

    if (!dic.bodyData().isSectionActive('concepts')) {
        return;
    }

    let notification = dic.notificationsManager();
    let confirmed = false;

    // apply card filters by pressing ENTER key
    $('input[name="card_name"]').keypress(function(event) {
        if (event.keyCode === dic.KEY_ENTER) {
            event.preventDefault();
            $('button[name="concepts_apply_filters"]').click();
        }
    });

    // card concept delete confirmation
    $('button[name="delete_concept"]').click(function() {
        // action was already approved
        if (confirmed) {
            // skip standard confirmation
            $('button[name="delete_concept"]').attr('name', 'delete_concept_confirm');
            return true;
        }

        let triggerButton = $(this);
        let message = 'Card concept data will be deleted. Are you sure you want to continue?';

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
        let uploadedFile = $('input[name="concept_image_file"]');

        // no file was selected
        if (uploadedFile.val() === '') {
            // prompt user to select a file
            uploadedFile.click();
            return false;
        }
    });

});

}
