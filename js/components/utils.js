/**
 * MArcomage JavaScript - support functions
 */

import $ from 'jquery';

export default function () {
  /**
   * Refresh user screen (top-level sections only)
   */
  function refresh() {
    // do not use window.location.reload() because it may cause redundant POST request
    // do not use direct assigning to window.location.href
    // because each reload will be stored in browsing history
    // do not use window.location.href as a source, because it may contain garbage
    window.location.replace($('.inner-navbar__menu-center > a.pushed').attr('href'));
  }

  $(document).ready(() => {
    const dic = $.dic;
    const api = dic.apiManager();
    const notification = dic.notificationsManager();
    let confirmed = false;

    // login box auto focus (ommited in case of registration)
    const username = $('.outer-navbar__login-box input[name="username"]');
    if (username.length > 0 && !dic.bodyData().isSectionActive('registration')) {
      // set focus on login name
      username.focus();

      // login name input handling
      username.keypress((event) => {
        if (event.keyCode === dic.KEY_ENTER) {
          event.preventDefault();

          // login name is specified - move cursor to the next input
          if ($('input[name="username"]').val() !== '') {
            $('input[name="password"]').focus();
          }
        }
      });

      // password input handling
      $('input[name="password"]').keypress((event) => {
        if (event.keyCode === dic.KEY_ENTER) {
          event.preventDefault();

          // password is specified - execute login
          if ($('input[name="password"]').val() !== '') {
            $('button[name="login"]').click();
          }
        }
      });

      // check if both login inputs are filled
      $('button[name="login"]').click(() => {
        if ($('input[name="username"]').val() === '' || $('input[name="password"]').val() === '') {
          notification.displayInfo('Mandatory input required', 'Please input your login name and password');
          return false;
        }

        return true;
      });
    }

    // blocks ENTER key to prevent section redirects
    $('input[type!="password"], input[name!="username"], input[name!="new_username"], select').keypress((event) => {
      if (event.keyCode === dic.KEY_ENTER) {
        event.preventDefault();
      }
    });

    // BBcode buttons handling
    $('.bb-code-buttons > button').click(function () {
      const bbCode = dic.bbCode();

      // get target element name
      const target = $(this).parent().attr('id');
      switch ($(this).attr('name')) {
        case 'bold':
          bbCode.addTags('[b]', '[/b]', target);
          break;
        case 'italics':
          bbCode.addTags('[i]', '[/i]', target);
          break;
        case 'link':
          bbCode.addTags('[link]', '[/link]', target);
          break;
        case 'url':
          bbCode.addTags('[url]', '[/url]', target);
          break;
        case 'quote':
          bbCode.addTags('[quote]', '[/quote]', target);
          break;
        case 'card':
          bbCode.addTags('[card]', '[/card]', target);
          break;
        case 'keyword':
          bbCode.addTags('[keyword]', '[/keyword]', target);
          break;
        case 'concept':
          bbCode.addTags('[concept]', '[/concept]', target);
          break;
        default:
          break;
      }
    });

    // element title tooltip
    $('[title]').tooltip({
      classes: {
        'ui-tooltip': 'ui-corner-all ui-widget-shadow',
      },
      placement: 'auto bottom',
    });

    // process details row click
    $('.responsive-table .table-row--details').click(function () {
      // navigate to row details
      if ($(this).find('a.details-link').length > 0) {
        window.location.assign($(this).find('a.details-link').attr('href'));
      }
    });

    // looked up cards will be stored here
    let cardLookupManager = {
      cache: {},

      currentLookUp: -1,

      /**
       * @param {Object} triggerElem
       * @param {string} data
       */
      showCard(triggerElem, data) {
        // position the lookup display
        const cardLookup = $('#card-lookup-hint');
        const parentCard = (triggerElem.parents('.card').length > 0) ? triggerElem.parents('.card') : triggerElem;
        const target = parentCard.offset();

        // default lookup position is below the card
        let topPosition = target.top + parentCard.outerHeight();

        // pass card html to lookup display
        cardLookup.html(data);

        // in the case there is not enough space below the parent card
        // display the card lookup above
        if (parentCard.offset().top + parentCard.outerHeight() >
            (($(window).scrollTop() + $(window).height()) - cardLookup.outerHeight())) {
          topPosition = target.top - cardLookup.outerHeight();
        }

        cardLookup.css({ top: topPosition, left: target.left });
        cardLookup.fadeIn('fast');
      },

      hideCard() {
        cardLookupManager = this;

        $('#card-lookup-hint').fadeOut('fast');
      },

      /**
       * @param {int}cardId
       * @param {object}trigger
       */
      lookupCard(cardId, trigger) {
        cardLookupManager = this;

        if (cardLookupManager.cache[cardId]) {
          // case 1: card is already present a the cache
          // display card
          cardLookupManager.showCard(trigger, cardLookupManager.cache[cardId]);
        } else {
          // case 2: card is not cached
          // store current card id to prevent conflicts based on delayed requests
          const currentCard = cardId;

          api.lookupCard(cardId, (result) => {
            // AJAX failed, display error message
            if (result.error) {
              return;
            }

            // cache card data
            cardLookupManager.cache[cardId] = result.data;

            // display card if current card has not changed in the meantime
            if (currentCard === cardId) {
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
      startLookup(cardId, trigger) {
        cardLookupManager = this;
        cardLookupManager.currentLookUp = cardId;

        // delay the lookup render to prevent accidental triggers
        setTimeout(() => {
          // proceed only if user has not changed focus to something else
          if (cardLookupManager.currentLookUp === cardId) {
            cardLookupManager.lookupCard(cardId, trigger);
          }
        }, 500);
      },
    };

    // card lookup
    $('[data-card-lookup]').hover(function () {
      // extract card id
      const lookupTrigger = $(this);
      const cardId = parseInt(lookupTrigger.attr('data-card-lookup'), 10);

      cardLookupManager.startLookup(cardId, lookupTrigger);
    }, function () {
      const lookupTrigger = $(this);
      const cardId = parseInt(lookupTrigger.attr('data-card-lookup'), 10);

      // lookup has been replaced in the meantime
      if (cardLookupManager.currentLookUp !== cardId) {
        return;
      }

      // reset to default state
      cardLookupManager.currentLookUp = -1;

      cardLookupManager.hideCard(cardId);
    });

    // dismiss error message
    $('button[name="error-message-dismiss"]').click(() => {
      $('#error-message').modal('hide');
    });

    // dismiss info message
    $('button[name="info-message-dismiss"]').click(() => {
      $('#info-message').modal('hide');
    });

    // start discussion confirmation
    $('button[name="find_card_thread"], button[name="find_concept_thread"], button[name="find_deck_thread"], button[name="find_replay_thread"]').click(function () {
      // action was already approved
      if (confirmed) {
        return true;
      }

      const triggerButton = $(this);

      // request confirmation
      notification.displayConfirm('Action confirmation', 'Are you sure you want to start a discussion?', (result) => {
        if (result) {
          // pass confirmation
          confirmed = true;
          triggerButton.click();
        }
      });

      return false;
    });

    // scroll to top of current page
    $('button[name="back_to_top"]').click(() => {
      $('html, body').animate({ scrollTop: 0 }, 'slow');

      return false;
    });

    // localize timestamp
    $('[data-timestamp]').each(function () {
      // extract timestamp
      let timestamp = $(this).attr('data-timestamp');
      const format = $(this).attr('data-date-format');
      timestamp = timestamp.split(' ');

      // extract date and time
      let date = timestamp[0];
      let time = timestamp[1];
      date = date.split('-');
      time = time.split(':');

      // determine UTC datetime
      const datetime = new Date();
      datetime.setUTCFullYear(date[0]);
      // JavaScript month numbering starts from 0, not from 1
      datetime.setUTCMonth(date[1] - 1, date[2]);
      datetime.setUTCHours(time[0]);
      datetime.setUTCMinutes(time[1]);
      datetime.setUTCSeconds(time[2]);

      // format timestamp to local format and time zone
      if (format === 'date') {
        $(this).text(datetime.toLocaleDateString());
      } else if (format === 'date-time') {
        $(this).text(datetime.toLocaleString());
      }
    });

    // print button
    $('button[name="print"]').click(() => {
      window.print();

      return false;
    });

    $('.toggle-dialog__button').change(function () {
      if (this.checked) {
        const name = $(this).attr('name');
        const checkboxes = $('.toggle-dialog__button');

        // close all other opened dialogs
        if (checkboxes.filter(':checked').length > 0) {
          checkboxes.each(function () {
            if (name !== $(this).attr('name')) {
              $(this).prop('checked', false);
            }
          });
        }
      }
    });
  });
}
