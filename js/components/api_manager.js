/**************************************
 * MArcomage JavaScript - API manager *
 **************************************/

export default function () {
  /**
   * Retrieve session data from cookies (or session string if cookies are disabled)
   * @param name
   * @returns {*}
   */
  function getSessionData(name) {
    let cookieValue = $.cookie(name);
    if (cookieValue !== null && cookieValue !== '') {
      return cookieValue;
    }

    return $('input[name="' + name + '"][type="hidden"]').val();
  }

  /**
   * API manager contains API connection related functionality
   * @constructor
   */
  function ApiManager() {
    /**
     * Execute generic call
     * @param {string}action
     * @param {object}data
     * @param {function}callback
     */
    this.executeCall = function (action, data, callback) {
      // add mandatory data
      data['action'] = action;

      // include session data if available
      let username = getSessionData('username');
      let sessionId = getSessionData('session_id');

      if (typeof(username) !== 'undefined') {
        data['username'] = getSessionData('username');
      }

      if (typeof(sessionId) !== 'undefined') {
        data['session_id'] = getSessionData('session_id');
      }

      // execute call
      $.post('?m=ajax', data, callback);
    };

    /**
     * Take card from card pool and add to deck
     * @param {string}deckId
     * @param {int}cardId
     * @param {function}callback
     */
    this.takeCard = function (deckId, cardId, callback) {
      this.executeCall('take_card', {
        deck_id: deckId,
        card_id: cardId
      }, callback);
    };

    /**
     * Remove card from deck and add to card pool
     * @param {string}deckId
     * @param {int}cardId
     * @param {function}callback
     */
    this.removeCard = function (deckId, cardId, callback) {
      this.executeCall('remove_card', {
        deck_id: deckId,
        card_id: cardId
      }, callback);
    };

    /**
     * Save deck note
     * @param {string}deckId
     * @param {string}note
     * @param {function}callback
     */
    this.saveDeckNote = function (deckId, note, callback) {
      this.executeCall('save_deck_note', {
        deck_id: deckId,
        note: note
      }, callback);
    };

    /**
     * Clear deck note
     * @param {string}deckId
     * @param {function}callback
     */
    this.clearDeckNote = function (deckId, callback) {
      this.executeCall('clear_deck_note', {
        deck_id: deckId
      }, callback);
    };

    /**
     * Execute card preview
     * @param {string}cardPos
     * @param {string}mode
     * @param {string}gameId
     * @param {function}callback
     */
    this.cardPreview = function (cardPos, mode, gameId, callback) {
      this.executeCall('preview_card', {
        cardpos: cardPos,
        mode: mode,
        game_id: gameId
      }, callback);
    };

    /**
     * Save game note
     * @param {string}gameId
     * @param {string}note
     * @param {function}callback
     */
    this.saveGameNote = function (gameId, note, callback) {
      this.executeCall('save_game_note', {
        game_id: gameId,
        note: note
      }, callback);
    };

    /**
     * Clear game note
     * @param {string}gameId
     * @param {function}callback
     */
    this.clearGameNote = function (gameId, callback) {
      this.executeCall('clear_game_note', {
        game_id: gameId
      }, callback);
    };

    /**
     * Reset chat notification
     * @param {string}gameId
     * @param {function}callback
     */
    this.resetChatNotification = function (gameId, callback) {
      this.executeCall('reset_chat_notification', {
        game_id: gameId
      }, callback);
    };

    /**
     * Send chat message
     * @param {string}gameId
     * @param {string}message
     * @param {function}callback
     */
    this.sendChatMessage = function (gameId, message, callback) {
      this.executeCall('send_chat_message', {
        game_id: gameId,
        message: message
      }, callback);
    };

    /**
     * Lookup card html code
     * @param {int}cardId
     * @param {function}callback
     */
    this.lookupCard = function (cardId, callback) {
      this.executeCall('card_lookup', {
        card_id: cardId
      }, callback);
    };

    /**
     * Check if there are any active games
     * @param {function}callback
     */
    this.activeGames = function (callback) {
      this.executeCall('active_games', {}, callback);
    };
  }

  window.ApiManager = ApiManager;
}
