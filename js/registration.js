/***********************************************
 * MArcomage JavaScript - Registration section *
 ***********************************************/

'use strict';

$(document).ready(function() {
    var notification = dic().notificationsManager();

    var newUsername = $('input[name="new_username"]');

    // set focus on login name
    newUsername.focus();

    // login name input handling
    newUsername.keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();

            // login name is specified - move cursor to the next input
            if ($('input[name="new_username"]').val() != '') {
                $('input[name="new_password"]').focus();
            }
        }
    });

    // new password input handling
    $('input[name="new_password"]').keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();

            // new password is specified - move cursor to the next input
            if ($('input[name="new_password"]').val() != '') {
                $('input[name="confirm_password"]').focus();
            }
        }
    });

    // new password confirmation input handling
    $('input[name="confirm_password"]').keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();

            // new password is specified - execute register
            if ($('input[name="confirm_password"]').val() != '') {
                $('button[name="register"]').click();
            }
        }
    });

    // validate captcha before submission
    $('button[name="register"]').click(function(event) {
        // validate only if CAPTCHA is present
        if ($('.g-recaptcha').length > 0 && $('#g-recaptcha-response').val() == '') {
            notification.displayInfo('Mandatory input is missing', 'Please fill out CAPTCHA');
            return false;
        }
    });
});
