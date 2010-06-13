// MArcomage JavaScript support functions

function TakeCard(username, session_id, deck, id) // add card to deck via AJAX
{
	var str = new String();
	var card = str.concat("#card_", id);

	$.post("AJAXhandler.php", { action: 'take', Username: username, SessionID: session_id, deckname: deck, card_id: id }, function(data){
		// process result
		var result = data.split(",");
		if (result.length == 1) { alert(result[0]); return false; } // AJAX failed, display error message
		var res_val = result[0];
		var tokens = result[1];
		var avg = result[2];

		var slot = str.concat("#slot_", res_val);

		// move selected card to deck
		$(card).removeAttr('onclick'); // disallow the card to be removed from the deck (prevent double clicks)
		$(card).animate({ opacity: 0 }, 'slow', function() {
			$(card).animate({ width: 'hide' }, 'slow', function() {
				$(slot).html($(card).html());
				$(slot).hide();
				$(slot).fadeIn('slow');
				$(slot).attr('onclick', str.concat("return Remove(", id, ")")); // allow a card to be removed from deck
				$(card).html(''); // remove card from card pool
			});
		});

		// update tokens when needed
		if (tokens != "no")
		{
			var token_vals = tokens.split(";");
			var token;

			$("#tokens > select").each(function(i) {
				token = document.getElementsByName(str.concat("Token", i + 1)).item(0);
				$(this).find("option").each(function(j) {
					if ($(this).attr("value") == token_vals[i]) { token.selectedIndex = j; };
				});
			});
		}

		// recalculate avg cost per turn
		var avg_vals = avg.split(";");
		$("#cost_per_turn > b").each(function(i) {
			$(this).html(avg_vals[i]);
		});
 });

	return false; // disable standard processing
}

function RemoveCard(username, session_id, deck, id) // remove card from deck via AJAX
{
	var str = new String();
	var card = str.concat("#card_", id);

	$.post("AJAXhandler.php", { action: 'remove', Username: username, SessionID: session_id, deckname: deck, card_id: id }, function(data){
		// process result
		var result = data.split(",");
		if (result.length == 1) { alert(result[0]); return true; } // AJAX failed, display error message
		var res_val = result[0];
		var avg = result[1];

		var slot = str.concat("#slot_", res_val);
		var empty = '<div class="karta no_class zero_cost with_bgimage"><div class="null">0</div><h5>Empty</h5><img src="img/cards/g0.jpg" width="80px" height="60px" alt="" /><p></p><div></div></div>';

		// move selected card to card pool
		$(slot).removeAttr('onclick'); // disallow the card to be removed from the deck (prevent double clicks)
		$(slot).find("noscript").remove(); // remove return card button
		$(card).html($(slot).html());
		$(card).hide();
		$(card).css('width', 'hide'); // hide card (set width and opacity to default values for the animation)
		$(card).css('opacity', 0);
		$(card).attr('onclick', str.concat("return Take(", id, ")")); // allow a card to be removed from deck
		$(slot).fadeOut('slow', function() {
			$(slot).html(empty);
			$(slot).show();
			$(card).animate({ width: 'show' }, 'slow', function() {
				$(card).animate({ opacity: 1 }, 'slow');
			});
		});

		// recalculate avg cost per turn
		var avg_vals = avg.split(";");
		$("#cost_per_turn > b").each(function(i) {
			$(this).html(avg_vals[i]);
		});
 });

	return false; // disable standard processing
}

$(document).ready(function() {

	// sends in game chat message when ENTER key is hit
	$("input[name='ChatMessage']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("input[name='send_message']").click(); }
	});

	// blocks ENTER key to prevent section redirects
	$("input[name!='ChatMessage'][type!='password'], select").keypress(function(event) { if (event.keyCode == '13') { event.preventDefault(); } });

});
