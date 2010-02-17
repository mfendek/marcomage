/* Adds a pair of tags to the highlighted text in the textarea with given ID. If
no text is highlighted, append the beginning and ending tag to whatever's in the
textarea. */
function addTags(Tag,fTag)
{
  var obj = document.getElementsByName("Content").item(0);
  obj.focus();

  if (document.selection && document.selection.createRange)  // Internet Explorer
  {
		sel = document.selection.createRange();
		if (sel.parentElement() == obj)  sel.text = Tag + sel.text + fTag;
  }
  else if (typeof(obj) != "undefined")  // Firefox
  {
		var longueur = parseInt(obj.value.length);
		var selStart = obj.selectionStart;
		var selEnd = obj.selectionEnd;

		obj.value = obj.value.substring(0,selStart) + Tag + obj.value.substring(selStart,selEnd) + fTag + obj.value.substring(selEnd,longueur);
  }
  else
	{
		obj.value += Tag + fTag;
	}
  obj.focus();
}