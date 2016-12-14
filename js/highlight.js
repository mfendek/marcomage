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
    window.setTimeout(highlightQuickButton, 1500);
}

/**
 * Highlight leave game button
 */
function highlightLeaveButton()
{
    $('div.game button[name="leave_game"]').effect('highlight', {}, 1000);
    window.setTimeout(highlightLeaveButton, 1500);
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
        if ($('div.game tr.hand:first-child div.selected-card').length == 0) {
            $('div.game tr.hand:first-child div.suggested > div.card').animate({ borderColor: '#000000' }, 500, function() {
                $(this).animate({ borderColor: '#ffffff'} , 500);
            });
            window.setTimeout(highlightCards, 3000);
        }
        // case 2: highlight play button
        else if (playCard.length == 1) {
            playCard.effect('highlight', {}, 1000);
            window.setTimeout(highlightCards, 1500);
        }
    }
    // case 2: multiple play card button mode is active
    else {
        playCard = $('div.game button.suggested');
        playCard.effect('highlight', {}, 1000);
        window.setTimeout(highlightCards, 1500);
    }
}

$(document).ready(function() {

    // highlight quick game vs AI button in games section
    if ($('div#games button[name="quick_game"]').length > 0) {
        highlightQuickButton();
    }

    // highlight selectable cards
    if ($('div.game button[name="play_card"]').length > 0) {
        highlightCards();
    }

    // highlight leave game button
    if ($('div.game button[name="leave_game"]').length > 0) {
        highlightLeaveButton();
    }

});
