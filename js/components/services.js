/***********************************
 * MArcomage JavaScript - services *
 ***********************************/

import $ from 'jquery';

export default function () {

    /**
     * Retrieve session data from cookies (or session string if cookies are disabled)
     * @param name
     * @returns {*}
     */
    function getSessionData(name)
    {
        let cookieValue = $.cookie(name);
        if (cookieValue !== null && cookieValue !== '') {
            return cookieValue;
        }

        return $('input[name="' + name + '"][type="hidden"]').val();
    }

    /**
     * Simple dependency injection container
     */
    function dic()
    {
        // initialize on first use
        if (typeof $.dic === 'undefined') {
            $.dic = {
                KEY_ENTER: 13,

                // local cache, used to store service objects
                cache: {},

                /**
                 *
                 * @param name
                 * @returns {object}
                 */
                getService: function(name)
                {
                    // lazy load
                    if (!this.cache[name]) {
                        let object = {};

                        // variable class name is processed by eval (class name is not user input)
                        eval('object = new ' + name + '();');

                        this.cache[name] = object;
                    }

                    return this.cache[name];
                },

                // sugar functions down below

                /**
                 * @returns {ApiManager}
                 */
                apiManager: function()
                {
                    return this.getService('ApiManager');
                },

                /**
                 * @returns {NotificationsManager}
                 */
                notificationsManager: function()
                {
                    return this.getService('NotificationsManager');
                },

                /**
                 * @returns {BodyData}
                 */
                bodyData: function()
                {
                    return this.getService('BodyData');
                },

                /**
                 * @returns {BBcode}
                 */
                bbCode: function()
                {
                    return this.getService('BBcode');
                }
            };
        }

        return $.dic;
    }

    // store DIC into global namespace so we could access it anywhere
    $.dic = dic();

    /**
     * API manager contains API connection related functionality
     * @constructor
     */
    function ApiManager()
    {
        /**
         * Execute generic call
         * @param {string}action
         * @param {object}data
         * @param {function}callback
         */
        this.executeCall = function(action, data, callback)
        {
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
        this.takeCard = function(deckId, cardId, callback)
        {
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
        this.removeCard = function(deckId, cardId, callback)
        {
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
        this.saveDeckNote = function(deckId, note, callback)
        {
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
        this.clearDeckNote = function(deckId, callback)
        {
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
        this.cardPreview = function(cardPos, mode, gameId, callback)
        {
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
        this.saveGameNote = function(gameId, note, callback)
        {
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
        this.clearGameNote = function(gameId, callback)
        {
            this.executeCall('clear_game_note', {
                game_id: gameId
            }, callback);
        };

        /**
         * Reset chat notification
         * @param {string}gameId
         * @param {function}callback
         */
        this.resetChatNotification = function(gameId, callback)
        {
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
        this.sendChatMessage = function(gameId, message, callback)
        {
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
        this.lookupCard = function(cardId, callback)
        {
            this.executeCall('card_lookup', {
                card_id: cardId
            }, callback);
        };

        /**
         * Check if there are any active games
         * @param {function}callback
         */
        this.activeGames = function(callback)
        {
            this.executeCall('active_games', {}, callback);
        };
    }

    /**
     * Notifications manager
     * @constructor
     */
    function NotificationsManager()
    {
        /**
         * Display error message
         * @param {string}message
         */
        this.displayError = function(message)
        {
            let error = $('#error-message');
            error.find('.modal-body > p').text(message);
            error.modal();
        };

        /**
         * Display info message
         * @param {string}heading
         * @param {string}message
         */
        this.displayInfo = function(heading, message)
        {
            let info = $('#info-message');
            info.find('.modal-title').text(heading);
            info.find('.modal-body').html(message);
            info.modal();
        };

        /**
         * Display confirmation dialog
         * @param {string}heading
         * @param {string}message
         * @param {function}callback
         */
        this.displayConfirm = function(heading, message, callback)
        {
            let confirm = $('#confirm-message');
            confirm.find('.modal-title').text(heading);
            confirm.find('.modal-body').html(message);
            confirm.modal();

            // remove previously bound events and data
            // this is necessary because this confirm dialog is shared
            confirm.find('button[name="confirm"]').unbind('click');
            confirm.unbind('hidden.bs.modal');
            confirm.find('input[name="confirmed"]').val('');

            // ok button callback
            confirm.find('button[name="confirm"]').click(function() {
                // store confirmed value for dismiss callback use
                confirm.find('input[name="confirmed"]').val('yes');

                // hide confirm dialog
                confirm.modal('hide');
            });

            // dismiss dialog callback
            confirm.on('hidden.bs.modal', function() {
                callback(confirm.find('input[name="confirmed"]').val() !== '');
            });
        };
    }

    /**
     * Body data
     * @constructor
     */
    function BodyData()
    {
        /**
         * @type {string}
         */
        this.cache = {};

        /**
         * @param {string}field
         * @returns {string}
         */
        this.getData = function(field)
        {
            // data is not cached yet
            if (!this.cache[field]) {
                let data = $('body').attr('data-' + field);
                this.cache[field] = (typeof data !== 'undefined') ? data : '';
            }

            return this.cache[field];
        };

        /**
         * Check if specified section is active
         * @param {string}section
         */
        this.isSectionActive = function(section)
        {
            return (this.getData('section') === section);
        };

        /**
         * Check if tutorial is active
         */
        this.isTutorialActive = function()
        {
            return (this.getData('tutorial') === 'yes');
        };
    }

    /**
     * BB code
     * @constructor
     */
    function BBcode()
    {
        /**
         * Adds a pair of tags to the highlighted text in the text area with given name
         * if no text is highlighted, append the beginning and ending tag to whatever's in the text area
         * @param {string}openingTag
         * @param {string}closingTag
         * @param {string}content
         */
        this.addTags = function(openingTag, closingTag, content)
        {
            let obj = document.getElementsByName(content).item(0);
            obj.focus();

            // Internet Explorer
            if (document.selection && document.selection.createRange) {
                let currentSelection = document.selection.createRange();

                if (currentSelection.parentElement() === obj) {
                    currentSelection.text = openingTag + currentSelection.text + closingTag;
                }
            }
            // Firefox
            else if (typeof(obj) !== 'undefined') {
                let length = parseInt(obj.value.length);
                let selStart = obj.selectionStart;
                let selEnd = obj.selectionEnd;

                obj.value = obj.value.substring(0, selStart) + openingTag + obj.value.substring(selStart, selEnd)
                    + closingTag + obj.value.substring(selEnd, length);
            }
            // other
            else {
                obj.value += openingTag + closingTag;
            }

            obj.focus();
        };
    }

}
