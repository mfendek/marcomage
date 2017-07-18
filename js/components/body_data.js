/************************************
 * MArcomage JavaScript - Body data *
 ************************************/

export default function () {
  /**
   * Body data
   * @constructor
   */
  function BodyData() {
    /**
     * @type {string}
     */
    this.cache = {};

    /**
     * @param {string}field
     * @returns {string}
     */
    this.getData = function (field) {
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
    this.isSectionActive = function (section) {
      return (this.getData('section') === section);
    };

    /**
     * Check if tutorial is active
     */
    this.isTutorialActive = function () {
      return (this.getData('tutorial') === 'yes');
    };
  }

  window.BodyData = BodyData;
}
