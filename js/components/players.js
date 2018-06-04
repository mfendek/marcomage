/**
 * MArcomage JavaScript - Players section
 */

import $ from 'jquery';

export default function () {
  $(document).ready(() => {
    const dic = $.dic;

    if (!dic.bodyData().isSectionActive('players')) {
      return;
    }

    const notification = dic.notificationsManager();
    let confirmed = false;

    // apply player filters by pressing ENTER key
    $('input[name="pname_filter"]').keypress((event) => {
      if (event.keyCode === dic.KEY_ENTER) {
        event.preventDefault();
        $('button[name="players_apply_filters"]').click();
      }
    });

    // admin actions confirmation
    $('button[name="change_access"], button[name="reset_password"], button[name="reset_avatar_remote"], button[name="reset_exp"], button[name="add_gold"], button[name="delete_player"], button[name="rename_player"]').click(function () {
      // action was already approved
      if (confirmed) {
        return true;
      }

      const triggerButton = $(this);

      // request confirmation
      notification.displayConfirm('Action confirmation', 'Do you really want to '.concat($(this).html(), '?'), (result) => {
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
