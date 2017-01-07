/*************************************************
 * MArcomage JavaScript - highlighting functions *
 *************************************************/

'use strict';

/**
 * Highlight quick game vs AI button
 */
function highlightQuickButton()
{
    $('div#games button[name="quick_game"]').effect('highlight', {}, 1000);
    window.setTimeout(highlightQuickButton, 3000);
}

/**
 * Highlight leave game button
 */
function highlightLeaveButton()
{
    $('div.game button[name="leave_game"]').effect('highlight', {}, 1000);
    window.setTimeout(highlightLeaveButton, 3000);
}

/**
 * Highlight playable cards in hand
 */
function highlightCards()
{
    var playCard = $('div.game button[name="play_card"]');

    // case 1: single play card button mode is active
    if (playCard.length == 1 && playCard.val() == 0) {
        // case 1: highlight playable cards
        if ($('div.game .hand.my-hand div.selected-card').length == 0) {
            $('div.game .hand.my-hand div.suggested > div.card').animate({ opacity: 0.6 }, 500, function() {
                $(this).animate({ opacity: 1} , 500);
            });
            window.setTimeout(highlightCards, 3000);
        }
        // case 2: highlight play button
        else if (playCard.length == 1) {
            playCard.effect('highlight', {}, 1000);
            window.setTimeout(highlightCards, 3000);
        }
    }
    // case 2: multiple play card button mode is active
    else if (playCard.length > 0) {
        playCard = $('div.game button.suggested');
        playCard.effect('highlight', {}, 1000);
        window.setTimeout(highlightCards, 3000);
    }
    // case 3: no play card button is available
    else {
        // case 1: highlight a card for discard action
        if ($('div.game .hand.my-hand div.selected-card').length == 0) {
            $('div.game .hand.my-hand div.suggested > div.card').animate({ opacity: 0.6 }, 500, function() {
                $(this).animate({ opacity: 1} , 500);
            });
            window.setTimeout(highlightCards, 3000);
        }
        // case 2: highlight discard button
        else {
            $('div.game button[name="discard_card"]').effect('highlight', {}, 1000);
            window.setTimeout(highlightCards, 3000);
        }
    }
}

$(document).ready(function() {

    // highlight quick game vs AI button in games section
    if ($('div#games button[name="quick_game"]').length > 0) {
        highlightQuickButton();
    }

    // highlight selectable cards
    highlightCards();

    // highlight leave game button
    if ($('div.game button[name="leave_game"]').length > 0) {
        highlightLeaveButton();
    }

});
