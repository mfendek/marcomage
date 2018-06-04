/**
 * MArcomage JavaScript - highlighting functions
 */

import $ from 'jquery';

export default function () {
  /**
   * Highlight quick game vs AI button
   */
  function highlightQuickButton() {
    $('button[name="quick_game"]').effect('highlight', {}, 1000);
    window.setTimeout(highlightQuickButton, 3000);
  }

  /**
   * Highlight leave game button
   */
  function highlightLeaveButton() {
    $('.game button[name="leave_game"]').effect('highlight', {}, 1000);
    window.setTimeout(highlightLeaveButton, 3000);
  }

  /**
   * Highlight playable cards in hand
   */
  function highlightCards() {
    let playCard = $('.game button[name="play_card"]');

    if (playCard.length === 1 && playCard.val() === 0) {
      // case 1: single play card button mode is active
      if ($('.game__hand.my-hand .selected-card').length === 0) {
        // case 1: highlight playable cards
        $('.game__hand.my-hand .suggested > .card').animate({ opacity: 0.6 }, 500, function () {
          $(this).animate({ opacity: 1 }, 500);
        });
        window.setTimeout(highlightCards, 3000);
      } else if (playCard.length === 1) {
        // case 2: highlight play button
        playCard.effect('highlight', {}, 1000);
        window.setTimeout(highlightCards, 3000);
      }
    } else if (playCard.length > 0) {
      // case 2: multiple play card button mode is active
      playCard = $('.game button.suggested');
      playCard.effect('highlight', {}, 1000);
      window.setTimeout(highlightCards, 3000);
    } else if ($('.game__hand.my-hand .selected-card').length === 0) {
      // case 3: no play card button is available
      // case 1: highlight a card for discard action
      $('.game__hand.my-hand .suggested > .card').animate({ opacity: 0.6 }, 500, function () {
        $(this).animate({ opacity: 1 }, 500);
      });
      window.setTimeout(highlightCards, 3000);
    } else {
      // case 2: highlight discard button
      $('.game button[name="discard_card"]').effect('highlight', {}, 1000);
      window.setTimeout(highlightCards, 3000);
    }
  }

  $(document).ready(() => {
    const dic = $.dic;

    if (!dic.bodyData().isTutorialActive()) {
      return;
    }

    // highlight quick game vs AI button in games section
    if ($('button[name="quick_game"]').length > 0) {
      highlightQuickButton();
    }

    // highlight selectable cards
    highlightCards();

    // highlight leave game button
    if ($('.game button[name="leave_game"]').length > 0) {
      highlightLeaveButton();
    }
  });
}
