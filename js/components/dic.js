/**
 * MArcomage JavaScript - Dependency injector container
 */

import $ from 'jquery';

export default function () {
  /**
   * Simple dependency injection container
   */
  function dic() {
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
        getService(name) {
          // lazy load
          if (!this.cache[name]) {
            const object = {};

            // variable class name is processed by eval (class name is not user input)
            eval('object = new '.concat(name, '();'));

            this.cache[name] = object;
          }

          return this.cache[name];
        },

        // sugar functions down below

        /**
         * @returns {ApiManager}
         */
        apiManager() {
          return this.getService('ApiManager');
        },

        /**
         * @returns {NotificationsManager}
         */
        notificationsManager() {
          return this.getService('NotificationsManager');
        },

        /**
         * @returns {BodyData}
         */
        bodyData() {
          return this.getService('BodyData');
        },

        /**
         * @returns {BBcode}
         */
        bbCode() {
          return this.getService('BBcode');
        },
      };
    }

    return $.dic;
  }

  // store DIC into global namespace so we could access it anywhere
  $.dic = dic();
}
