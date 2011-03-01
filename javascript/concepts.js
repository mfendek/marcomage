/*******************************************
 * MArcomage JavaScript - Concepts section *
 *******************************************/

$(document).ready(function() {

	// apply card filters by pressing ENTER key
	$("input[name='card_name']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='concepts_filter']").click(); }
	});

	// card concept delete confirmation
	$("div#concepts_edit button[name='delete_concept']").click(function() {
		if (confirm("All of the card concept data will be deleted. Are you sure you want to continue?"))
		{
			// skip standard confirmation
			$("button[name='delete_concept']").attr('name', 'delete_concept_confirm');
			return true;
		}
		else return false;
	});

});
