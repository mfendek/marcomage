/***********************************************
 * MArcomage JavaScript - Registration section *
 ***********************************************/

$(document).ready(function() {

	// set focus on login name
	$("input[name='NewUsername']").focus();

	// login name input handling
	$("input[name='NewUsername']").keypress(function(event){
		if (event.keyCode == '13')
		{
			event.preventDefault();

			// login name is specified - move cursor to the next input
			if ($("input[name='NewUsername']").val() != '')
			{
				$("input[name='NewPassword']").focus();
			}
		}
	});

	// new password input handling
	$("input[name='NewPassword']").keypress(function(event){
		if (event.keyCode == '13')
		{
			event.preventDefault();

			// new password is specified - move cursor to the next input
			if ($("input[name='NewPassword']").val() != '')
			{
				$("input[name='NewPassword2']").focus();
			}
		}
	});

	// new password confirmation input handling
	$("input[name='NewPassword2']").keypress(function(event){
		if (event.keyCode == '13')
		{
			event.preventDefault();

			// new password is specified - execute register
			if ($("input[name='NewPassword2']").val() != '')
			{
				$("button[name='Register']").click();
			}
		}
	});

});
