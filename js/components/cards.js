/**
 * MArcomage JavaScript - Cards section
 */

import $ from 'jquery';

export default function () {
  $(document).ready(() => {
    const dic = $.dic;

    if (!dic.bodyData().isSectionActive('cards')) {
      return;
    }

    const notification = dic.notificationsManager();
    let confirmed = false;

    // purchase foil card version
    $('button[name="buy_foil_card"]').click(function () {
      // action was already approved
      if (confirmed) {
        return true;
      }

      const triggerButton = $(this);
      let message = $('#foil-version-purchase').text().concat('?');
      message = message.replace('version', 'version of '.concat($('#foil-version-name').text())).concat('?');

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

    // apply card filters by pressing ENTER key
    $('input[name="name_filter"]').keypress((event) => {
      if (event.keyCode === dic.KEY_ENTER) {
        event.preventDefault();
        $('button[name="cards_apply_filters"]').click();
      }
    });
  });
}
