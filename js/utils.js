/********************************************
 * MArcomage JavaScript - support functions *
 ********************************************/

'use strict';

/**
 * Refresh user screen (top-level sections only)
 */
function refresh()
{
    // do not use window.location.reload() because it may cause redundant POST request
    // do not use direct assigning to window.location.href because each reload will be stored in browsing history
    // do not use window.location.href as a source, because it may contain garbage
    window.location.replace($('div#menu-center > a.pushed').attr('href'));
}

/**
 * Retrieve session data from cookies (or session string if cookies are disabled)
 * @param name
 * @returns {*}
 */
function getSessionData(name)
{
    var cookieValue = $.cookie(name);
    if (cookieValue != null && cookieValue != '') {
        return cookieValue;
    }

    return $('input[name="' + name + '"][type="hidden"]').val();
}

/**
 * Adds a pair of tags to the highlighted text in the text area with given name
 * if no text is highlighted, append the beginning and ending tag to whatever's in the text area
 * @param {string}openingTag
 * @param {string}closingTag
 * @param {string}content
 */
function addTags(openingTag, closingTag, content) {
    var obj = document.getElementsByName(content).item(0);
    obj.focus();

    // Internet Explorer
    if (document.selection && document.selection.createRange) {
        var currentSelection = document.selection.createRange();

        if (currentSelection.parentElement() == obj) {
            currentSelection.text = openingTag + currentSelection.text + closingTag;
        }
    }
    // Firefox
    else if (typeof(obj) != 'undefined') {
        var length = parseInt(obj.value.length);
        var selStart = obj.selectionStart;
        var selEnd = obj.selectionEnd;

        obj.value = obj.value.substring(0, selStart) + openingTag + obj.value.substring(selStart, selEnd)
            + closingTag + obj.value.substring(selEnd, length);
    }
    // other
    else {
        obj.value += openingTag + closingTag;
    }

    obj.focus();
}

/**
 * Simple dependency injection container
 */
function dic()
{
    // initialize on first use
    if (typeof $.dic == 'undefined') {
        $.dic = {
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
                    this.cache[name] = new window[name];
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
            }
        };
    }

    return $.dic;
}

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
        var username = getSessionData('username');
        var sessionId = getSessionData('session_id');

        if (typeof(username) != 'undefined') {
            data['username'] = getSessionData('username');
        }

        if (typeof(sessionId) != 'undefined') {
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
        var error = $('#error-message');
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
        var info = $('#info-message');
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
        var confirm = $('#confirm-message');
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
            callback(confirm.find('input[name="confirmed"]').val() != '');
        });
    };
}

$(document).ready(function() {
    var api = dic().apiManager();
    var notification = dic().notificationsManager();
    var confirmed = false;

    // login box auto focus
    var username = $('#login-inputs input[name="username"]');
    if (username.length > 0) {
        // set focus on login name
        username.focus();

        // login name input handling
        username.keypress(function(event) {
            if (event.keyCode == '13') {
                event.preventDefault();

                // login name is specified - move cursor to the next input
                if ($('input[name="username"]').val() != '') {
                    $('input[name="password"]').focus();
                }
            }
        });

        // password input handling
        $('input[name="password"]').keypress(function(event) {
            if (event.keyCode == '13') {
                event.preventDefault();

                // password is specified - execute login
                if ($('input[name="password"]').val() != '') {
                    $('button[name="login"]').click();
                }
            }
        });

        // check if both login inputs are filled
        $('button[name="login"]').click(function() {
            if ($('input[name="username"]').val() == '' || $('input[name="password"]').val() == '') {
                notification.displayInfo('Mandatory input required', 'Please input your login name and password');
                return false;
            }
        });
    }

    // blocks ENTER key to prevent section redirects
    $('input[type!="password"], input[name!="username"], input[name!="new_username"], select').keypress(function(event) {
        if (event.keyCode == '13') {
            event.preventDefault();
        }
    });

    // BBcode buttons handling
    $('div.bb-code-buttons > button').click(function() {
        // get target element name
        var target = $(this).parent().attr('id');
        switch ($(this).attr('name')) {
            case 'bold':
                addTags('[b]', '[/b]', target);
                break;
            case 'italics':
                addTags('[i]', '[/i]', target);
                break;
            case 'link':
                addTags('[link]', '[/link]', target);
                break;
            case 'url':
                addTags('[url]', '[/url]', target);
                break;
            case 'quote':
                addTags('[quote]', '[/quote]', target);
                break;
            case 'card':
                addTags('[card]', '[/card]', target);
                break;
            case 'keyword':
                addTags('[keyword]', '[/keyword]', target);
                break;
            case 'concept':
                addTags('[concept]', '[/concept]', target);
                break;
        }
    });

    // element title tooltip
    $('[title]').tooltip({
        classes: {
            'ui-tooltip': 'ui-corner-all ui-widget-shadow'
        },
        placement: 'auto bottom'
    });

    // process details row click
    $('.responsive-table .row.details').click(function() {
        // navigate to row details
        if ($(this).find('a.profile').length > 0) {
            window.location.assign($(this).find('a.profile').attr('href'));
        }
    });

    // looked up cards will be stored here
    var cardLookupManager = {
        cache: {},

        currentLookUp: -1,

        /**
         * @param {Object} triggerElem
         * @param {string} data
         */
        showCard: function(triggerElem, data)
        {
            // position the lookup display
            var cardLookup = $('#card-lookup');
            var parentCard = (triggerElem.parents('.card').length > 0) ? triggerElem.parents('.card') : triggerElem;
            var target = parentCard.offset();

            var widthOffset = (parentCard.outerWidth() - parentCard.innerWidth()) / 2;
            var heightOffset = parentCard.outerHeight();

            cardLookup.css({ 'top': target.top + heightOffset, 'left': target.left - widthOffset });

            // pass card html to lookup display
            cardLookup.html(data);
            cardLookup.fadeIn('fast');
        },

        hideCard: function()
        {
            cardLookupManager = this;

            $('#card-lookup').fadeOut('fast');
        },

        /**
         * @param {int}cardId
         * @param {object}trigger
         */
        lookupCard: function(cardId, trigger)
        {
            cardLookupManager = this;

            // case 1: card is already present a the cache
            if (cardLookupManager.cache[cardId]) {
                // display card
                cardLookupManager.showCard(trigger, cardLookupManager.cache[cardId]);
            }
            // case 2: card is not cached
            else {
                // store current card id to prevent conflicts based on delayed requests
                var currentCard = cardId;

                api.lookupCard(cardId, function(result) {
                    // AJAX failed, display error message
                    if (result.error) {
                        console.log(result.error);
                        return;
                    }

                    // cache card data
                    cardLookupManager.cache[cardId] = result.data;

                    // display card if current card has not changed in the meantime
                    if (currentCard == cardId) {
                        cardLookupManager.showCard(trigger, result.data);
                    }
                });
            }
        },

        /**
         *
         * @param {int}cardId
         * @param {object}trigger
         */
        startLookup: function(cardId, trigger)
        {
            cardLookupManager = this;
            cardLookupManager.currentLookUp = cardId;

            // delay the lookup render to prevent accidental triggers
            setTimeout(function() {
                // proceed only if user has not changed focus to something else
                if (cardLookupManager.currentLookUp == cardId) {
                    cardLookupManager.lookupCard(cardId, trigger);
                }
            }, 500);
        }
    };

    // card lookup
    $('[class*="card-lookup-"]').hover(function() {
        // extract card id
        var lookupTrigger = $(this);
        var cardId = parseInt(lookupTrigger.attr('class').replace('card-lookup-', ''));

        cardLookupManager.startLookup(cardId, lookupTrigger);
    }, function() {
        var lookupTrigger = $(this);
        var cardId = parseInt(lookupTrigger.attr('class').replace('card-lookup-', ''));

        // lookup has been replaced in the meantime
        if (cardLookupManager.currentLookUp != cardId) {
            return;
        }

        // reset to default state
        cardLookupManager.currentLookUp = -1;

        cardLookupManager.hideCard(cardId);
    });

    // dismiss error message
    $('button[name="error-message-dismiss"]').click(function() {
        $('#error-message').modal('hide');
    });

    // dismiss info message
    $('button[name="info-message-dismiss"]').click(function() {
        $('#info-message').modal('hide');
    });

    // start discussion confirmation
    $('button[name="find_card_thread"], button[name="find_concept_thread"], button[name="find_deck_thread"], button[name="find_replay_thread"]').click(function() {
        // action was already approved
        if (confirmed) {
            return true;
        }

        var triggerButton = $(this);

        // request confirmation
        notification.displayConfirm('Action confirmation', 'Are you sure you want to start a discussion?', function(result) {
            if (result) {
                // pass confirmation
                confirmed = true;
                triggerButton.click();
            }
        });

        return false;
    });

    // scroll to top of current page
    $('button[name="back_to_top"]').click(function() {
        $('html, body').animate({ scrollTop: 0 }, 'slow');

        return false;
    });

});
