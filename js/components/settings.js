/**
 * MArcomage JavaScript - Settings section
 */

import $ from 'jquery';

export default function () {
  $(document).ready(() => {
    const dic = $.dic;

    if (!dic.bodyData().isSectionActive('settings')) {
      return;
    }

    const notification = dic.notificationsManager();
    let confirmed = false;

    // purchase item (MArcomage shop)
    $('button[name="buy_item"]').click(function () {
      const selected = $('select[name="selected_item"]').val();

      // action was already approved
      if (confirmed) {
        return true;
      }

      const triggerButton = $(this);
      const message = 'Do you really want to purchase '.concat($('#'.concat(selected, '_desc')).text(), '?');

      // request confirmation
      notification.displayConfirm('Purchase confirmation', message, (result) => {
        if (result) {
          // pass confirmation
          confirmed = true;
          triggerButton.click();
        }
      });

      return false;
    });

    // skip tutorial
    $('button[name="skip_tutorial"]').click(function () {
      // action was already approved
      if (confirmed) {
        return true;
      }

      const triggerButton = $(this);

      // request confirmation
      notification.displayConfirm('Action confirmation', 'Do you really want to skip tutorial?', (result) => {
        if (result) {
          // pass confirmation
          confirmed = true;
          triggerButton.click();
        }
      });

      return false;
    });

    // file upload
    $('button[name="upload_avatar_image"]').click(() => {
      const uploadedFile = $('input[name="avatar_image_file"]');

      // no file was selected
      if (uploadedFile.val() === '') {
        // prompt user to select a file
        uploadedFile.click();
        return false;
      }

      return true;
    });
  });
}
