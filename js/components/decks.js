/****************************************
 * MArcomage JavaScript - Decks section *
 ****************************************/

import $ from 'jquery';

export default function () {

/**
 * Add card to deck via AJAX
 * @param {int}cardId
 * @returns {boolean}
 */
function takeCard(cardId)
{
    let dic = $.dic;
    let card = '#card_' + cardId;
    let deckId = $('input[name="current_deck"]').val();
    let api = dic.apiManager();
    let notification = dic.notificationsManager();

    api.takeCard(deckId, cardId, function(result) {
        // AJAX failed, display error message
        if (result.error) {
            notification.displayError(result.error);
            return;
        }

        let slot = '#slot_' + result.slot;
        let takenCard = result.taken_card;

        // move selected card to deck
        // disallow the card to be removed from the deck (prevent double clicks)
        $(card).removeAttr('data-take-card');
        $(card).unbind('click');

        $(card).find('.card').animate({opacity: 0.6}, 'slow', function() {
            $(slot).html(takenCard);

            // initialize hint tooltip for newly added card
            $(slot).find('[title]').tooltip({
                classes: {
                    'ui-tooltip': 'ui-corner-all ui-widget-shadow'
                },
                placement: 'auto bottom'
            });

            // mark card as taken
            $(card).addClass('taken');
            $(slot).find('.card').css('opacity', 1);
            $(slot).hide();
            $(slot).fadeIn('slow');

            // allow a card to be removed from deck
            $(slot).attr('data-remove-card', cardId);
            $(slot).click(function() {
                let cardId = parseInt($(this).attr('data-remove-card'));

                return removeCard(cardId);
            });
        });

        // update tokens when needed
        if (result.tokens !== 'no') {
            let token;

            $('#tokens > select').each(function(i) {
                token = document.getElementsByName('Token' + (i + 1)).item(0);
                $(this).find('option').each(function(j) {
                    if ($(this).val() === result.tokens[i + 1]) {
                        token.selectedIndex = j;
                    }
                });
            });
        }

        // recalculate avg cost per turn
        $('.cost-per-turn > b').each(function(i) {
            $(this).html(result.avg[i]);
        });
    });

    // disable standard processing
    return false;
}

/**
 * Remove card from deck via AJAX
 * @param {int}cardId
 * @returns {boolean}
 */
function removeCard(cardId)
{
    let dic = $.dic;
    let card = '#card_' + cardId;
    let deckId = $('input[name="current_deck"]').val();
    let api = dic.apiManager();
    let notification = dic.notificationsManager();

    api.removeCard(deckId, cardId, function(result) {
        // AJAX failed, display error message
        if (result.error) {
            notification.displayError(result.error);
            return;
        }

        let slot = '#slot_' + result.slot;
        let empty = result['slot_html'];

        // move selected card to card pool

        // disallow the card to be removed from the deck (prevent double clicks)
        $(slot).removeAttr('data-remove-card');
        $(slot).unbind('click');

        // remove return card button
        $(slot).find('noscript').remove();

        // unmark card as taken
        $(card).removeClass('taken');
        $(card).find('.card').css('opacity', 0.6);

        // allow a card to be removed from card pool
        $(card).attr('data-take-card', cardId);
        $(card).click(function() {
            let cardId = parseInt($(this).attr('data-take-card'));

            return takeCard(cardId);
        });

        $(slot).fadeOut('slow', function() {
            $(slot).html(empty);
            $(slot).show();
            $(card).find('.card').animate({ opacity: 1 }, 'slow');
        });

        // recalculate avg cost per turn
        $('.cost-per-turn > b').each(function(i) {
            $(this).html(result.avg[i]);
        });
    });

    // disable standard processing
    return false;
}

$(document).ready(function() {
    let dic = $.dic;

    if (!dic.bodyData().isSectionActive('decks')) {
        return;
    }

    let api = dic.apiManager();
    let notification = dic.notificationsManager();
    let confirmed = false;

    // apply card filters by pressing ENTER key
    $('input[name="name_filter"]').keypress(function(event) {
        if (event.keyCode === dic.KEY_ENTER) {
            event.preventDefault();
            $('button[name="deck_apply_filters"]').click();
        }
    });

    // card pool lock
    let cardPoolLock = false;

    // show/hide card pool
    $('button[name="card_pool_switch"]').click(function() {
        let cardPool =  $('#card-pool');
        let cardPoolSwitch = $(this);
        let cardPoolIcon = $(this).find('span');

        // card pool is locked
        if (cardPoolLock) {
            return false;
        }

        // show card pool
        if (cardPoolSwitch.hasClass('show-card-pool')) {
            // block switch button while animating
            cardPoolLock = true;

            // repair card pool state if necessary
            cardPool.hide();
            cardPool.css('height', 'hide');
            cardPool.css('opacity', 0);

            // expand card pool
            cardPool.animate({height: 'show'}, 'slow', function() {
                $('#card-pool').animate({opacity: 1}, 'slow', function() {
                    $('#card-pool').show();

                    // update hidden data element
                    $('input[name="card_pool"]').val('yes');

                    // unlock card pool
                    cardPoolSwitch.removeClass('show-card-pool');
                    cardPoolSwitch.addClass('hide-card-pool');
                    cardPoolIcon.removeClass('glyphicon-resize-full');
                    cardPoolIcon.addClass('glyphicon-resize-small');
                    cardPoolLock = false;
                });
            });
        }
        // hide card pool
        else if (cardPoolSwitch.hasClass('hide-card-pool')) {
            // block switch button while animating
            cardPoolLock = true;

            // repair card pool state if necessary
            cardPool.show();

            // collapse card pool
            cardPool.animate({opacity: 0}, 'slow', function() {
                $('#card-pool').animate({height: 'hide'}, 'slow', function() {
                    $('#card-pool').hide();

                    // update hidden data element
                    $('input[name="card_pool"]').val('no');

                    // unlock card pool
                    cardPoolSwitch.removeClass('hide-card-pool');
                    cardPoolSwitch.addClass('show-card-pool');
                    cardPoolIcon.removeClass('glyphicon-resize-small');
                    cardPoolIcon.addClass('glyphicon-resize-full');
                    cardPoolLock = false;
                });
            });
        }

        return false;
    });

    // deck reset confirmation
    $('button[name="reset_deck_prepare"]').click(function() {
        // action was already approved
        if (confirmed) {
            // skip standard confirmation
            $('button[name="reset_deck_prepare"]').attr('name', 'reset_deck_confirm');
            return true;
        }

        let triggerButton = $(this);
        let message = 'All cards will be removed from the deck, all token counters will be reset and deck statistics will be reset as well. Are you sure you want to continue?';

        // request confirmation
        notification.displayConfirm('Action confirmation', message, function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

    // deck statistics reset confirmation
    $('button[name="reset_stats_prepare"]').click(function() {
        // action was already approved
        if (confirmed) {
            // skip standard confirmation
            $('button[name="reset_stats_prepare"]').attr('name', 'reset_stats_confirm');
            return true;
        }

        let triggerButton = $(this);
        let message = 'Deck statistics will be reset. Are you sure you want to continue?';

        // request confirmation
        notification.displayConfirm('Action confirmation', message, function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

    // deck share confirmation
    $('button[name="share_deck"]').click(function() {
        // action was already approved
        if (confirmed) {
            return true;
        }

        let triggerButton = $(this);
        let message = 'Are you sure you want to share this deck to other players?';

        // request confirmation
        notification.displayConfirm('Action confirmation', message, function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

    // import shared deck confirmation
    $('button[name="import_shared_deck"]').click(function() {
        // action was already approved
        if (confirmed) {
            return true;
        }

        // extract target deck name
        let targetDeckId = $('select[name="selected_deck"]').val();
        let targetDeck = $('select[name="selected_deck"] >  option[value="' + targetDeckId + '"]').text();

        // extract source deck name
        let sourceDeck = $(this).parent().parent().find('a.deck').text();

        let triggerButton = $(this);
        let message = 'Are you sure you want to import ' + sourceDeck + ' into ' + targetDeck + '?';

        // request confirmation
        notification.displayConfirm('Action confirmation', message, function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

    // open deck note
    $('a#deck-note').click(function(event) {
        event.preventDefault();
        $('#deck-note-dialog').modal();
    });

    // save deck note button
    $('button[name="deck-note-dialog-save"]').click(function() {
        let deckNote = $('textarea[name="content"]').val();

        // check user input
        if (deckNote.length > 1000) {
            notification.displayError('Deck note is too long');
            return;
        }

        let deckId = $('input[name="current_deck"]').val();

        api.saveDeckNote(deckId, deckNote, function(result) {
            // AJAX failed, display error message
            if (result.error) {
                notification.displayError(result.error);
                return;
            }

            // update note button highlight
            // case 1: note is empty (remove highlight)
            if (deckNote === '') {
                $('a#deck-note').removeClass('marked_button');
            }
            // case 2: note is not empty (add highlight if not present)
            else if (!$('a#deck-note').hasClass('marked_button')) {
                $('a#deck-note').addClass('marked_button');
            }

            $('#deck-note-dialog').modal('hide');
        });
    });

    // clear deck note button
    $('button[name="deck-note-dialog-clear"]').click(function() {
        let deckId = $('input[name="current_deck"]').val();

        api.clearDeckNote(deckId, function(result) {
            // AJAX failed, display error message
            if (result.error) {
                notification.displayError(result.error);
                return;
            }

            // clear input field
            $('textarea[name="content"]').val('');

            // update note button highlight (remove highlight)
            $('a#deck-note').removeClass('marked_button');
        });

        // hide note dialog
        $('#deck-note-dialog').modal('hide');
    });

    // file upload
    $('button[name="import_deck"]').click(function() {
        let uploadedFile = $('input[name="deck_data_file"]');

        // no file was selected
        if (uploadedFile.val() === '') {
            // prompt user to select a file
            uploadedFile.click();
            return false;
        }
    });

    // take card from card pool
    $('[data-take-card]').click(function() {
        let cardId = parseInt($(this).attr('data-take-card'));

        return takeCard(cardId);
    });

    // remove card from deck
    $('[data-remove-card]').click(function() {
        let cardId = parseInt($(this).attr('data-remove-card'));

        return removeCard(cardId);
    });

});

}
