/******************************************
 * MArcomage JavaScript - Webpage section *
 ******************************************/

$(document).ready(function() {

	// set focus on login name
	$("input[name='Username']").focus();

	// login name input handling
	$("input[name='Username']").keypress(function(event){
		if (event.keyCode == '13')
		{
			event.preventDefault();

			// login name is specified - move cursor to the next input
			if ($("input[name='Username']").val() != '')
			{
				$("input[name='Password']").focus();
			}
		}
	});

	// password input handling
	$("input[name='Password']").keypress(function(event){
		if (event.keyCode == '13')
		{
			event.preventDefault();

			// password is specified - execute login
			if ($("input[name='Password']").val() != '')
			{
				$("button[name='Login']").click();
			}
		}
	});

});
