/******************************************
 * MArcomage JavaScript - Players section *
 ******************************************/

$(document).ready(function() {

	// apply player filters by pressing ENTER key
	$("input[name='pname_filter']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='filter_players']").click(); }
	});

});
