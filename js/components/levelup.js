/**
 * MArcomage JavaScript - levelup functions
 */

import $ from 'jquery';

export default function () {
  /**
   * Highlight specified section
   * @param {string}section
   */
  function highlightSection(section) {
    $('.inner-navbar__menu-center > a:contains("'.concat(section, '")')).effect('fade', {}, 800);
    window.setTimeout(highlightSection, 3000, section);
  }

  $(document).ready(() => {
    const levelUpDialog = $('#level-up-dialog');

    // dialog is inactive
    if (levelUpDialog.length === 0) {
      return;
    }

    levelUpDialog.on('hidden.bs.modal', () => {
      // highlight newly unlocked section
      const unlockSection = $('input[name="unlock_section"]');
      if (unlockSection.length === 1) {
        highlightSection(unlockSection.val());
      }
    });

    levelUpDialog.modal();
  });
}
