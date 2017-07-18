/*********************************
 * MArcomage JavaScript - BBcode *
 *********************************/

export default function () {
  /**
   * BB code
   * @constructor
   */
  function BBcode() {
    /**
     * Adds a pair of tags to the highlighted text in the text area with given name
     * if no text is highlighted, append the beginning and ending tag to whatever's in the text area
     * @param {string}openingTag
     * @param {string}closingTag
     * @param {string}content
     */
    this.addTags = function (openingTag, closingTag, content) {
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

  window.BBcode = BBcode;
}
