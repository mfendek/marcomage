/****************************************
 * MArcomage JavaScript - Games section *
 ****************************************/

'use strict';

/**
 * Refresh user screen within the games list section
 */
function refreshGameList()
{
    var api = dic().apiManager();

    // check if there are any active games available
    api.activeGames(function(result) {
        // AJAX failed, display error message
        if (result.error) {
            console.log(result.error);
            return;
        }

        // active games are available
        if (result['active_games']) {
            // refresh game screen
            window.location.replace($('div#hosted-games > p > a.pushed').attr('href'));
        }
    });
}

/**
 * Refresh user screen within the game
 */
function refreshGame()
{
    var api = dic().apiManager();

    var nextGame = $('div.game button[name="next_game"]');

    // case 1: it is not player's turn in current game and the next game button is available - go to next game
    if ($('div.game button[name="discard_card"]').length == 0 && nextGame.length > 0) {
        nextGame.click();
    }
    // case 2: stay in current game and refresh screen
    else {
        // check if there are any active games available
        api.activeGames(function(result) {
            // AJAX failed, display error message
            if (result.error) {
                console.log(result.error);
                return;
            }

            // active games are available
            if (result['active_games']) {
                window.location.replace($('a#game_refresh').attr('href'));
            }
        });
    }
}

/**
 *
 * @returns {number}
 */
function startGameRefresh()
{
    var timer = 0;
    var autoRefresh = $('div.game input[name="auto_refresh"]');

    if (autoRefresh.length == 1) {
        timer = window.setInterval(refreshGame, parseInt(autoRefresh.val()) * 1000);
    }

    return timer;
}

/**
 * Execute auto AI move
 */
function autoAiMove()
{
    var aiMove = $('div.game button[name="ai_move"]');

    if (aiMove.length == 1) {
        aiMove.click();
    }
}

/**
 * @param {string}name
 */
function showAiChallenge(name)
{
    // hide all ai challenges
    $('#ai-challenges > div').hide();

    // show the selected one
    $('#ai-challenge-' + name).show();
}

$(document).ready(function() {
    var api = dic().apiManager();
    var notification = dic().notificationsManager();

    // initialize games list refresh if active
    var autoRefresh = $('div#games > input[name="auto_refresh"]');
    if (autoRefresh.length == 1) {
        var gamesTimer = window.setInterval(refreshGameList, parseInt(autoRefresh.val()) * 1000);
    }

    // activate auto AI move
    var autoAi = $('div.game input[name="auto_ai"]');
    if (autoAi.length == 1) {
        window.setTimeout(autoAiMove, parseInt(autoAi.val()) * 1000);
    }

    // card selector verification (play card)
    $('button[name="play_card"][value="0"]').click(function() {
        if ($('input[name="selected_card"]:checked').length == 0) {
            notification.displayError('No card was selected!');
            return false;
        }

        if ($('div.selected-card').parent().hasClass('unplayable')) {
            notification.displayError("Card can't be played.");
            return false;
        }

        return true;
    });

    // card selector verification (discard card)
    $('button[name="discard_card"]').click(function() {
        if ($('input[name="selected_card"]:checked').length == 0) {
            notification.displayError('No card was selected!');
            return false;
        }

        return true;
    });

    // hide radio buttons (selection is done via card)
    $('input[name="selected_card"]').hide();

    // set initial state for action buttons (partially visible)
    $('button[name="play_card"][value="0"], button[name="discard_card"], button[name="preview_card"]').css('opacity', 0.6);

    // card selection (via card)
    $('.my-hand div.card').click(function() {
        // active only on player's turn
        if ($('input[name="selected_card"]').length > 0) {
            var selectedCard = $('div.selected-card');

            // case 1: unselected card is selected
            if (!$(this).hasClass('selected-card')) {
                // unselect previously selected card
                $('input[name="selected_card"]:checked').removeAttr('checked');

                selectedCard.removeClass('selected-card');

                // select specified card
                $(this).parent().nextAll("input[name='selected_card']").attr('checked', 'checked');
                $(this).addClass('selected-card');

                if (!$(this).parent().hasClass('unplayable')) {
                    // case 1: card is playable -> show play and preview buttons
                    $('button[name="play_card"][value="0"], button[name="preview_card"]').animate({ opacity: 1 }, 'fast');
                }
                else {
                    // case 2: card is unplayable -> hide play and preview buttons
                    $('button[name="play_card"][value="0"], button[name="preview_card"]').animate({ opacity: 0.6 }, 'fast');
                }

                // show discard button
                $('button[name="discard_card"]').animate({opacity: 1}, 'fast');
            }
            // case 2: selected card is reselected
            else {
                // unselect selected card
                $('input[name="selected_card"]:checked').removeAttr('checked');
                selectedCard.removeClass('selected-card');

                // return action buttons to initial state
                $('button[name="play_card"][value="0"], button[name="discard_card"], button[name="preview_card"]').animate({opacity: 0.6}, 'fast');
            }
        }
    });

    // card selection (via card modes)
    $('select.card-modes').click(function() {
        var currentCard = $(this).prevAll('div').children('div.card');

        if (!currentCard.hasClass('selected-card')) {
            currentCard.click();
        }
    });

    // card preview processing
    $('button[name="preview_card"]').click(function() {
        var selectedCard = $('input[name="selected_card"]:checked');
        if (selectedCard.length == 0) {
            notification.displayError('No card was selected!');
            return false;
        }

        if ($('div.selected-card').parent().hasClass('unplayable')) {
            notification.displayError("Card can't be played.");
            return false;
        }

        var cardPosition = selectedCard.val();

        // store card position
        $(this).val(cardPosition);

        return true;
    });

    // initialize in-game refresh if active
    var gameRefreshTimer = 0;

    if ($('div.game input[name="auto_refresh"]').length == 1) {
        gameRefreshTimer = startGameRefresh();
    }

    $('input[name="chat_message"]').keypress(function(event) {
        // disable auto-refresh when user is typing chat message
        window.clearInterval(gameRefreshTimer);

        // sends in game chat message by pressing ENTER key
        if (event.keyCode == '13') {
            event.preventDefault();
            $('button[name="send_message"]').click();
        }
    });

    var gameNoteDialog = $('#game-note-dialog');

    gameNoteDialog.bind('dialogclose', function() {
        // enable auto refresh when user closes the game note
        gameRefreshTimer = startGameRefresh();
    });

    // open game note
    $('a#game-note').click(function(event) {
        event.preventDefault();
        // disable auto refresh when user opens the game note
        window.clearInterval(gameRefreshTimer);
        $('#game-note-dialog').dialog('open');
    });

    // game note handler
    gameNoteDialog.dialog({
        autoOpen: false,
        show: 'fade',
        hide: 'fade',
        title: 'Note',
        buttons: {
            Save: function() {
                var gameNote = $('textarea[name="content"]').val();

                // check user input
                if (gameNote.length > 1000) {
                    notification.displayError('Game note is too long');
                    return;
                }

                var gameId = $('input[name="current_game"]').val();

                api.saveGameNote(gameId, gameNote, function(result) {
                    // AJAX failed, display error message
                    if (result.error) {
                        notification.displayError(result.error);
                        return;
                    }

                    // update note button highlight
                    // case 1: note is empty (remove highlight)
                    if (gameNote == '') {
                        $('a#game-note').removeClass('marked_button');
                    }
                    // case 2: note is not empty (add highlight if not present)
                    else if (!$('a#game-note').hasClass('marked_button')) {
                        $('a#game-note').addClass('marked_button');
                    }

                    $('#game-note-dialog').dialog('close');
                });
            },
            Clear: function() {
                var gameId = $('input[name="current_game"]').val();

                api.clearGameNote(gameId, function(result) {
                    // AJAX failed, display error message
                    if (result.error) {
                        notification.displayError(result.error);
                        return;
                    }

                    // clear input field
                    $('textarea[name="content"]').val('');

                    // update note button highlight (remove highlight)
                    $('a#game-note').removeClass('marked_button');
                });
            },
            Close: function() {
                $(this).dialog('close');
            }
        }
    });

    // scroll standard chat
    $('div.chat-section div.scroll_max').scrollTo('max');

    // chat modal dialog
    var chatDialog = $('#chat-dialog');
    if (chatDialog.length > 0) {
        chatDialog.bind('dialogclose', function() {
            // enable auto-refresh when user closes the chat
            gameRefreshTimer = startGameRefresh();
        });

        // open chat
        $('button[name="show_chat"]').click(function(event) {
            event.preventDefault();

            // disable auto refresh when user opens the chat
            window.clearInterval(gameRefreshTimer);

            // reset chat notification for current player
            var gameId = $('input[name="current_game"]').val();

            api.resetChatNotification(gameId, function(result) {
                // AJAX failed, display error message
                if (result.error) {
                    notification.displayError(result.error);
                    return;
                }
            });


            var chatDialog = $('#chat-dialog');

            // scrolling must be done only after the dialog has been opened
            chatDialog.bind('dialogopen', function() {
                $('#chat-dialog div.scroll_max').delay(400).scrollTo('max');
            });
            chatDialog.dialog('open');
        });

        // chat handler
        chatDialog.dialog({
            autoOpen: false,
            show: 'fade',
            hide: 'fade',
            width: 500,
            height: 600,
            title: 'Chat',
            buttons: {
                B: function() {
                    addTags('[b]', '[/b]', 'chat_area');
                },
                I: function() {
                    addTags('[i]', '[/i]', 'chat_area');
                },
                L: function() {
                    addTags('[link]', '[/link]', 'chat_area');
                },
                U: function() {
                    addTags('[url]', '[/url]', 'chat_area');
                },
                Q: function() {
                    addTags('[quote]', '[/quote]', 'chat_area');
                },
                Send: function() {
                    var chatMessage = $('textarea[name="chat_area"]').val();

                    // check user input
                    if (chatMessage.length > 300) {
                        notification.displayError('Chat message is too long');
                        return;
                    }

                    var gameId = $('input[name="current_game"]').val();

                    api.sendChatMessage(gameId, chatMessage, function(result) {
                        // AJAX failed, display error message
                        if (result.error) {
                            notification.displayError(result.error);
                            return;
                        }

                        chatDialog.dialog('close');
                        refreshGame();
                    });
                },
                Close: function() {
                    $(this).dialog('close');
                }
            }
        });

        // open chat automatically if there are new messages
        var showChat = $('div.game button.marked_button[name="show_chat"]');
        if (showChat.length == 1) {
            showChat.click();
        }
    }

    // show/hide cheating menu
    $('button[name="show_cheats"]').click(function() {
        var showCheats = $('button[name="show_cheats"]');

        // case 1: button is in 'show' mode
        if (showCheats.html() == 'Cheat') {
            // update button mode
            showCheats.html('Hide');

            // update cheat menu status
            $('input[name="cheat_menu"]').val('yes');

            // show cheat menu
            $('div.game div#game-cheat-menu').slideDown('slow');
        }
        // case 2: button is in 'hide' mode
        else {
            // update button mode
            showCheats.html('Cheat');

            // update cheat menu status
            $('input[name="cheat_menu"]').val('no');

            // hide cheat menu
            $('div.game div#game-cheat-menu').slideUp('slow');
        }
    });

    // AI challenges selector initialization
    var aiSelector = $('select[name="selected_challenge"]');
    showAiChallenge(aiSelector.val());

    // AI challenges selector
    aiSelector.change(function() {
        showAiChallenge($(this).val());
    });

});
