/*******************************************
 * MArcomage JavaScript - Settings section *
 *******************************************/

$(document).ready(function() {

	// purchase item (MArcomage shop)
	$("button[name='buy_item']").click(function() {
		var selected = $("input[name='selected_item']:checked");

		if (selected.length == 0)
		{
			alert('Invalid item selection.');
			return false;
		}
		else // request confirmation
		{
			var str = new String();
			return confirm(str.concat("Do you really want to purchase ", selected.next().html(), "?"))
		}
	});

	// birthdate datepicker (settings)
	$("input[name='Birthdate']").datepicker(
		{
			dateFormat: "dd-mm-yy",
			maxDate: 0,
			showButtonPanel: true,
			changeYear: true,
			showAnim: "fadeIn"
		}
	);

});
