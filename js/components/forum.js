/**
 * MArcomage JavaScript - Forum section
 */

import $ from 'jquery';

export default function () {
  $(document).ready(() => {
    const dic = $.dic;

    if (!dic.bodyData().isSectionActive('forum')) {
      return;
    }

    const notification = dic.notificationsManager();
    let confirmed = false;

    // executes forum search by pressing the ENTER key
    $('input[name="phrase"]').keypress((event) => {
      if (event.keyCode === dic.KEY_ENTER) {
        event.preventDefault();
        $('button[name="forum_search"]').click();
      }
    });

    // forum thread delete confirmation
    $('button[name="thread_delete"]').click(function () {
      // action was already approved
      if (confirmed) {
        // skip standard confirmation
        $('button[name="thread_delete"]').attr('name', 'thread_delete_confirm');
        return true;
      }

      const triggerButton = $(this);
      const message = 'Current thread and all its posts will be deleted. Are you sure you want to continue?';

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

    // forum post delete confirmation
    $('button[name="delete_post"]').click(function () {
      // action was already approved
      if (confirmed) {
        // skip standard confirmation
        $('button[name="delete_post"]').attr('name', 'delete_post_confirm');
        return true;
      }

      const triggerButton = $(this);
      const message = 'Current post will be deleted. Are you sure you want to continue?';

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
  });
}
