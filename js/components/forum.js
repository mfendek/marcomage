/****************************************
 * MArcomage JavaScript - Forum section *
 ****************************************/

import $ from 'jquery';

export default function () {

$(document).ready(function() {
    let dic = $.dic;

    if (!dic.bodyData().isSectionActive('forum')) {
        return;
    }

    let notification = dic.notificationsManager();
    let confirmed = false;

    // executes forum search by pressing the ENTER key
    $('input[name="phrase"]').keypress(function(event) {
        if (event.keyCode === dic.KEY_ENTER) {
            event.preventDefault();
            $('button[name="forum_search"]').click();
        }
    });

    // forum thread delete confirmation
    $('button[name="thread_delete"]').click(function() {
        // action was already approved
        if (confirmed) {
            // skip standard confirmation
            $('button[name="thread_delete"]').attr('name', 'thread_delete_confirm');
            return true;
        }

        let triggerButton = $(this);
        let message = 'Current thread and all its posts will be deleted. Are you sure you want to continue?';

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

    // forum post delete confirmation
    $('button[name="delete_post"]').click(function() {
        // action was already approved
        if (confirmed) {
            // skip standard confirmation
            $('button[name="delete_post"]').attr('name', 'delete_post_confirm');
            return true;
        }

        let triggerButton = $(this);
        let message = 'Current post will be deleted. Are you sure you want to continue?';

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

}
