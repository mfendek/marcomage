/****************************************
 * MArcomage JavaScript - Cards section *
 ****************************************/

$(document).ready(function() {

	// apply card filters by pressing ENTER key
	$("input[name='NameFilter']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='cards_filter']").click(); }
	});

});
