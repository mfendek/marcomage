/****************************************
 * MArcomage JavaScript - Games section *
 ****************************************/

import $ from 'jquery';

export default function () {
  /**
   * Refresh user screen within the games list section
   */
  function refreshGameList() {
    let dic = $.dic;
    let api = dic.apiManager();

    // check if there are any active games available
    api.activeGames(function (result) {
      // AJAX failed, display error message
      if (result.error) {
        console.log(result.error);
        return;
      }

      // active games are available
      if (result['active_games']) {
        // refresh game screen
        window.location.reload();
      }
    });
  }

  /**
   * Refresh user screen within the game
   */
  function refreshGame() {
    let dic = $.dic;
    let api = dic.apiManager();
    let nextGame = $('.game button[name="next_game"]');

    // case 1: it is not player's turn in current game and the next game button is available - go to next game
    if ($('.game button[name="discard_card"]').length === 0 && nextGame.length > 0) {
      nextGame.click();
    }
    // case 2: stay in current game and refresh screen
    else {
      // check if there are any active games available
      api.activeGames(function (result) {
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
  function startGameRefresh() {
    let timer = 0;
    let autoRefresh = $('.game input[name="auto_refresh"]');

    if (autoRefresh.length === 1) {
      timer = window.setInterval(refreshGame, parseInt(autoRefresh.val()) * 1000);
    }

    return timer;
  }

  /**
   * Execute auto AI move
   */
  function autoAiMove() {
    let aiMove = $('.game button[name="ai_move"]');

    if (aiMove.length === 1) {
      aiMove.click();
    }
  }

  $(document).ready(function () {
    let dic = $.dic;

    if (!$.dic.bodyData().isSectionActive('games')) {
      return;
    }

    let api = dic.apiManager();
    let notification = dic.notificationsManager();

    // initialize games list refresh if active
    let autoRefresh = $('input[name="game_list_auto_refresh"]');
    if (autoRefresh.length === 1) {
      let gamesTimer = window.setInterval(refreshGameList, parseInt(autoRefresh.val()) * 1000);
    }

    // activate auto AI move
    let autoAi = $('.game input[name="auto_ai"]');
    if (autoAi.length === 1) {
      window.setTimeout(autoAiMove, parseInt(autoAi.val()) * 1000);
    }

    // card selector verification (play card)
    $('button[name="play_card"][value="0"]').click(function () {
      if ($('input[name="selected_card"]:checked').length === 0) {
        notification.displayError('No card was selected!');
        return false;
      }

      if ($('.selected-card').parent().hasClass('unplayable')) {
        notification.displayError("Card can't be played.");
        return false;
      }

      return true;
    });

    // card selector verification (discard card)
    $('button[name="discard_card"]').click(function () {
      if ($('input[name="selected_card"]:checked').length === 0) {
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
    $('.my-hand .card').click(function () {
      // active only on player's turn
      if ($('input[name="selected_card"]').length > 0) {
        let selectedCard = $('div.selected-card');

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
            $('button[name="play_card"][value="0"], button[name="preview_card"]').animate({opacity: 1}, 'fast');
          }
          else {
            // case 2: card is unplayable -> hide play and preview buttons
            $('button[name="play_card"][value="0"], button[name="preview_card"]').animate({opacity: 0.6}, 'fast');
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
    $('.game__card-modes').click(function () {
      let currentCard = $(this).prevAll('div').children('.card');

      if (!currentCard.hasClass('selected-card')) {
        currentCard.click();
      }
    });

    // card preview processing
    $('button[name="preview_card"]').click(function () {
      let selectedCard = $('input[name="selected_card"]:checked');
      if (selectedCard.length === 0) {
        notification.displayError('No card was selected!');
        return false;
      }

      if ($('.selected-card').parent().hasClass('unplayable')) {
        notification.displayError("Card can't be played.");
        return false;
      }

      let cardPosition = selectedCard.val();

      // store card position
      $(this).val(cardPosition);

      return true;
    });

    // initialize in-game refresh if active
    let gameRefreshTimer = 0;

    if ($('.game input[name="auto_refresh"]').length === 1) {
      gameRefreshTimer = startGameRefresh();
    }

    $('input[name="chat_message"]').keypress(function (event) {
      // disable auto-refresh when user is typing chat message
      window.clearInterval(gameRefreshTimer);

      // sends in game chat message by pressing ENTER key
      if (event.keyCode === dic.KEY_ENTER) {
        event.preventDefault();
        $('button[name="send_message"]').click();
      }
    });

    let gameNoteDialog = $('#game-note-dialog');

    // dismiss dialog callback
    gameNoteDialog.on('hidden.bs.modal', function () {
      // enable auto refresh when user closes the game note
      gameRefreshTimer = startGameRefresh();
    });

    // open game note
    $('#game-note').click(function (event) {
      event.preventDefault();
      // disable auto refresh when user opens the game note
      window.clearInterval(gameRefreshTimer);
      $('#game-note-dialog').modal();
    });

    // save game note button
    $('button[name="game-note-dialog-save"]').click(function () {
      let gameNote = $('textarea[name="content"]').val();

      // check user input
      if (gameNote.length > 1000) {
        notification.displayError('Game note is too long');
        return;
      }

      let gameId = $('input[name="current_game"]').val();

      api.saveGameNote(gameId, gameNote, function (result) {
        // AJAX failed, display error message
        if (result.error) {
          notification.displayError(result.error);
          return;
        }

        // update note button highlight
        // case 1: note is empty (remove highlight)
        if (gameNote === '') {
          $('#game-note').removeClass('marked-button');
        }
        // case 2: note is not empty (add highlight if not present)
        else if (!$('#game-note').hasClass('marked-button')) {
          $('#game-note').addClass('marked-button');
        }

        $('#game-note-dialog').modal('hide');
      });
    });

    // clear game note button
    $('button[name="game-note-dialog-clear"]').click(function () {
      let gameId = $('input[name="current_game"]').val();

      api.clearGameNote(gameId, function (result) {
        // AJAX failed, display error message
        if (result.error) {
          notification.displayError(result.error);
          return;
        }

        // clear input field
        $('textarea[name="content"]').val('');

        // update note button highlight (remove highlight)
        $('#game-note').removeClass('marked-button');
      });

      // hide note dialog
      $('#game-note-dialog').modal('hide');
    });

    // scroll standard chat
    $('.chat-board .scroll-max').scrollTo('max');

    // chat modal dialog
    let chatDialog = $('#chat-dialog');
    if (chatDialog.length > 0) {
      chatDialog.on('hidden.bs.modal', function () {
        // enable auto-refresh when user closes the chat
        gameRefreshTimer = startGameRefresh();
      });

      // open chat
      $('button[name="show_chat"]').click(function (event) {
        event.preventDefault();

        // disable auto refresh when user opens the chat
        window.clearInterval(gameRefreshTimer);

        // reset chat notification for current player
        let gameId = $('input[name="current_game"]').val();

        api.resetChatNotification(gameId, function (result) {
          // AJAX failed, display error message
          if (result.error) {
            notification.displayError(result.error);
            return;
          }
        });


        let chatDialog = $('#chat-dialog');

        // scrolling must be done only after the dialog has been opened
        chatDialog.on('shown.bs.modal', function () {
          $('#chat-dialog').find('.scroll-max').delay(400).scrollTo('max');

          // switch focus to chat area so user can start typing right away
          $('textarea[name="chat_area"]').focus();
        });

        chatDialog.modal();
      });

      // send message button
      $('button[name="chat-dialog-send"]').click(function () {
        let chatMessage = $('textarea[name="chat_area"]').val();

        // check user input
        if (chatMessage.length > 300) {
          notification.displayError('Chat message is too long');
          return;
        }

        let gameId = $('input[name="current_game"]').val();

        api.sendChatMessage(gameId, chatMessage, function (result) {
          // AJAX failed, display error message
          if (result.error) {
            notification.displayError(result.error);
            return;
          }

          chatDialog.modal('hide');
          refreshGame();
        });
      });

      let bbCode = dic.bbCode();

      // BB code buttons
      $('button[name="chat-dialog-bold"]').click(function () {
        bbCode.addTags('[b]', '[/b]', 'chat_area');
      });

      $('button[name="chat-dialog-italic"]').click(function () {
        bbCode.addTags('[i]', '[/i]', 'chat_area');
      });

      $('button[name="chat-dialog-link"]').click(function () {
        bbCode.addTags('[link]', '[/link]', 'chat_area');
      });

      $('button[name="chat-dialog-url"]').click(function () {
        bbCode.addTags('[url]', '[/url]', 'chat_area');
      });

      $('button[name="chat-dialog-quote"]').click(function () {
        bbCode.addTags('[quote]', '[/quote]', 'chat_area');
      });

      // open chat automatically if there are new messages
      let showChat = $('.game button.marked-button[name="show_chat"]');
      if (showChat.length === 1) {
        showChat.click();
      }
    }

    // show/hide cheating menu
    $('button[name="show_cheats"]').click(function () {
      let showCheats = $('button[name="show_cheats"]');

      // case 1: button is in 'show' mode
      if (showCheats.html() === 'Cheat') {
        // update button mode
        showCheats.html('Hide');

        // update cheat menu status
        $('input[name="cheat_menu"]').val('yes');

        // show cheat menu
        $('#game-cheat-menu').slideDown('slow');
      }
      // case 2: button is in 'hide' mode
      else {
        // update button mode
        showCheats.html('Cheat');

        // update cheat menu status
        $('input[name="cheat_menu"]').val('no');

        // hide cheat menu
        $('#game-cheat-menu').slideUp('slow');
      }
    });

    // select AI challenge
    $('#ai-challenges').find('.image-list__item').click(function () {
      let challengeName = $(this).attr('id').replace('ai-challenge-', '');
      $('select[name="selected_challenge"]').val(challengeName);
    });
  });
}
