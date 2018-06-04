/**
 * MArcomage JavaScript - Notifications manager
 */

import $ from 'jquery';

export default function () {
  /**
   * Notifications manager
   * @constructor
   */
  function NotificationsManager() {
    /**
     * Display error message
     * @param {string}message
     */
    this.displayError = function (message) {
      const error = $('#error-message');
      error.find('.modal-body > p').text(message);
      error.modal();
    };

    /**
     * Display info message
     * @param {string}heading
     * @param {string}message
     */
    this.displayInfo = function (heading, message) {
      const info = $('#info-message');
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
    this.displayConfirm = function (heading, message, callback) {
      const confirm = $('#confirm-message');
      confirm.find('.modal-title').text(heading);
      confirm.find('.modal-body').html(message);
      confirm.modal();

      // remove previously bound events and data
      // this is necessary because this confirm dialog is shared
      confirm.find('button[name="confirm"]').unbind('click');
      confirm.unbind('hidden.bs.modal');
      confirm.find('input[name="confirmed"]').val('');

      // ok button callback
      confirm.find('button[name="confirm"]').click(() => {
        // store confirmed value for dismiss callback use
        confirm.find('input[name="confirmed"]').val('yes');

        // hide confirm dialog
        confirm.modal('hide');
      });

      // dismiss dialog callback
      confirm.on('hidden.bs.modal', () => {
        callback(confirm.find('input[name="confirmed"]').val() !== '');
      });
    };
  }

  window.NotificationsManager = NotificationsManager;
}
