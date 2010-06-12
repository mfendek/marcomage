// MArcomage JavaScript support functions

function SendMessage(e) // sends in game chat message when ENTER key is hit
{
	var keynum;

	if (window.event) { keynum = e.keyCode; } // IE
	else if (e.which) { keynum = e.which; } // Netscape/Firefox/Opera

	if (keynum == 13)
	{
		$("input[name=send_message]").click();
		return false;
	}

	return true;
}
 
$(document).ready(function() {
	// blocks ENTER key to prevent section redirects
	$("input, select").keypress(function(event) { if (event.keyCode == '13') { event.preventDefault(); } });
});
