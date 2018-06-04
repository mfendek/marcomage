/**
 * MArcomage JavaScript - BBcode
 */

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
      const obj = document.getElementsByName(content).item(0);
      obj.focus();

      if (document.selection && document.selection.createRange) {
        // Internet Explorer
        const currentSelection = document.selection.createRange();

        if (currentSelection.parentElement() === obj) {
          currentSelection.text = openingTag.concat(currentSelection.text, closingTag);
        }
      } else if (typeof (obj) !== 'undefined') {
        // Firefox
        const length = parseInt(obj.value.length, 10);
        const selStart = obj.selectionStart;
        const selEnd = obj.selectionEnd;

        obj.value = obj.value.substring(0, selStart).concat(
          openingTag,
          obj.value.substring(selStart, selEnd),
          closingTag,
          obj.value.substring(selEnd, length),
        );
      } else {
        // other
        obj.value = obj.value.concat(openingTag, closingTag);
      }

      obj.focus();
    };
  }

  window.BBcode = BBcode;
}
