/**
 * MArcomage JavaScript - Messages section
 */

import $ from 'jquery';

export default function () {
  $(document).ready(() => {
    const dic = $.dic;

    if (!dic.bodyData().isSectionActive('messages')) {
      return;
    }

    const notification = dic.notificationsManager();
    let confirmed = false;

    // apply message filters by pressing ENTER key
    $('input[name="name_filter"]').keypress((event) => {
      if (event.keyCode === dic.KEY_ENTER) {
        event.preventDefault();
        $('button[name="messages_apply_filters"]').click();
      }
    });

    // message delete confirmation
    $('button[name="message_delete"]').click(function () {
      // action was already approved
      if (confirmed) {
        // skip standard confirmation
        $('button[name="message_delete"]').attr('name', 'message_delete_confirm');
        return true;
      }

      const triggerButton = $(this);
      const message = 'Current message will be deleted. Are you sure you want to continue?';

      // request confirmation
      notification.displayConfirm('Action confirmation', message, (result) => {
        if (result) {
          // pass confirmation
          confirmed = true;
          triggerButton.click();
        }
      });

      return false;
    });

    // mass message delete confirmation
    $('button[name="delete_mass_messages"]').click(function () {
      // check if at least one message has been selected
      if ($('input[name*="mass_delete_"]:checked').length === 0) {
        notification.displayInfo('No messages selected for deletion', 'Please select at least one message.');
        return false;
      }

      // action was already approved
      if (confirmed) {
        return true;
      }

      const triggerButton = $(this);
      const message = 'All selected messages will be deleted. Are you sure you want to continue?';

      // request confirmation
      notification.displayConfirm('Action confirmation', message, (result) => {
        if (result) {
          // pass confirmation
          confirmed = true;
          triggerButton.click();
        }
      });

      return false;
    });

    // select / deselect all messages button
    $('button[name="select_all_messages"]').click(() => {
      const checkboxes = $('input[type="checkbox"][name^="mass_delete_"]');

      if (checkboxes.filter(':checked').length === checkboxes.length) {
        // all checkboxes are checked - deselect all
        checkboxes.prop('checked', false);
      } else {
        // at least one checkbox is deselected - select all
        checkboxes.prop('checked', true);
      }
    });
  });
}
