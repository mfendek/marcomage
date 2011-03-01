/*******************************************
 * MArcomage JavaScript - Messages section *
 *******************************************/

$(document).ready(function() {

	// apply message filters by pressing ENTER key
	$("input[name='name_filter']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='message_filter']").click(); }
	});

	// message delete confirmation
	$("button[name='message_delete']").click(function() {
		if (confirm("Current message will be deleted. Are you sure you want to continue?"))
		{
			// skip standard confirmation
			$("button[name='message_delete']").attr('name', 'message_delete_confirm');
			return true;
		}
		else return false;
	});

	// mass message delete confirmation
	$("button[name='Delete_mass']").click(function() {
		return confirm("All selected messages will be deleted. Are you sure you want to continue?");
	});

});
