/********************************************
 * MArcomage JavaScript - support functions *
 ********************************************/

function Refresh() // refresh user screen (top-level sections only)
{
	// do not use window.location.reload() because it may cause redundant POST request
	// do not use direct assigning to window.location.href because each reload will be stored in browsing history
	// do not use window.location.href as a source, because it may contain garbage
	window.location.replace($('div#menu_center > a.pushed').attr('href'));
}

function GetSessionData(name) // retrieve session data from cookies (or session string if cookies are disabled)
{
	var str = new String();
	var cookie_val = $.cookie(name);
	if (cookie_val != null && cookie_val != "") return cookie_val;
	else return $(str.concat("input[name='", name,"'][type='hidden']")).val();
}

function AddTags(Tag,fTag,content)
{
	// adds a pair of tags to the highlighted text in the textarea with given name
	// if no text is highlighted, append the beginning and ending tag to whatever's in the textarea

  var obj = document.getElementsByName(content).item(0);
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

$(document).ready(function() {

	// blocks ENTER key to prevent section redirects
	$("input[type!='password'], select").keypress(function(event) { if (event.keyCode == '13') { event.preventDefault(); } });

	// BBcode buttons handling
	$("div.BBcodeButtons > button").click(function() {
      // get target element name
      var target = $(this).parent().attr('id');
			switch($(this).attr('name'))
			{
				case 'bold':
					AddTags('[b]', '[/b]', target);
					break;
				case 'italics':
					AddTags('[i]', '[/i]', target);
					break;
				case 'link':
					AddTags('[link]', '[/link]', target);
					break;
				case 'url':
					AddTags('[url]', '[/url]', target);
					break;
				case 'quote':
					AddTags('[quote]', '[/quote]', target);
					break;
			}
	});

});
