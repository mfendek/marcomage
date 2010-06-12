// MArcomage JavaScript support functions

$(document).ready(function() {

	// sends in game chat message when ENTER key is hit
	$("input[name=ChatMessage]").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("input[name=send_message]").click(); }
	});

	// blocks ENTER key to prevent section redirects
	$("input[name!=ChatMessage][type!=password], select").keypress(function(event) { if (event.keyCode == '13') { event.preventDefault(); } });

});
