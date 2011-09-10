/****************************************
 * MArcomage JavaScript - Cards section *
 ****************************************/

$(document).ready(function() {

	// purchase foil card version
	$("button[name='buy_foil']").click(function() {
    var str = new String();
    return confirm(str.concat($(this).html(), "?"))
	});

	// apply card filters by pressing ENTER key
	$("input[name='NameFilter']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='cards_filter']").click(); }
	});

});
