/****************************************
 * MArcomage JavaScript - Cards section *
 ****************************************/

'use strict';

$(document).ready(function() {
    var notification = dic().notificationsManager();
    var confirmed = false;

    // purchase foil card version
    $('button[name="buy_foil_card"]').click(function() {
        // action was already approved
        if (confirmed) {
            return true;
        }

        var triggerButton = $(this);
        var message = $('#foil-version-purchase').text() + '?';
        message = message.replace('version', 'version of ' + $('#foil-version-name').text()) + '?';

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

    // apply card filters by pressing ENTER key
    $('input[name="name_filter"]').keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();
            $('button[name="cards_apply_filters"]').click();
        }
    });

});
