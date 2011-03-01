/****************************************
 * MArcomage JavaScript - Forum section *
 ****************************************/

$(document).ready(function() {

	// executes forum search by pressing the ENTER key
	$("input[name='phrase']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='forum_search']").click(); }
	});

	// forum thread delete confirmation
	$("button[name='thread_delete']").click(function() {
		if (confirm("Current thread and all its posts will be deleted. Are you sure you want to continue?"))
		{
			// skip standard confirmation
			$("button[name='thread_delete']").attr('name', 'thread_delete_confirm');
			return true;
		}
		else return false;
	});

	// forum post delete confirmation
	$("button[name='delete_post']").click(function() {
		if (confirm("Current post will be deleted. Are you sure you want to continue?"))
		{
			// skip standard confirmation
			$("button[name='delete_post']").attr('name', 'delete_post_confirm');
			return true;
		}
		else return false;
	});

});
