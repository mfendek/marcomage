function setFocus(elemName)
{
  var obj = document.getElementsByName(elemName).item(0);
  obj.focus();
}

function SendMessage(e) // sends in game chat message when ENTER key is hit
{
	var keynum;

	if (window.event) { keynum = e.keyCode; } // IE
	else if (e.which) { keynum = e.which; } // Netscape/Firefox/Opera

	if (keynum == 13)
	{
		var button = document.getElementsByName('send_message').item(0);
		button.click();
		return false;
	}

	return true;
}

function BlockEnter(e) // blocks ENTER key to prevent section redirects
{
	var keynum;

	if (window.event) { keynum = e.keyCode; } // IE
	else if (e.which) { keynum = e.which; } // Netscape/Firefox/Opera

	if (keynum == 13) return false;

	return true;
}
