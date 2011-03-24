/******************************************
 * MArcomage JavaScript - Players section *
 ******************************************/

$(document).ready(function() {

	// apply player filters by pressing ENTER key
	$("input[name='pname_filter']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='filter_players']").click(); }
	});

	// admin actions confirmation
	$("button[name='change_access'], button[name='reset_password'], button[name='reset_avatar_remote'], button[name='reset_exp'], button[name='add_gold'], button[name='delete_player']").click(function() {
		var str = new String();
		return confirm(str.concat("Do you really want to ", $(this).html(), "?"));
	});

});
