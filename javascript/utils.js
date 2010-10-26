// MArcomage JavaScript support functions

function TakeCard(id) // add card to deck via AJAX
{
	var str = new String();
	var card = str.concat("#card_", id);
	var username = GetSessionData('Username');
	var session_id = GetSessionData('SessionID');
	var deck = $("input[name='CurrentDeck']").val();

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
			$(card).animate({ width: 'hide', height: 'hide' }, 'slow', function() {
				$(slot).html($(card).html());
				$(slot).hide();
				$(slot).fadeIn('slow');
				$(slot).attr('onclick', str.concat("return RemoveCard(", id, ")")); // allow a card to be removed from deck
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
					if ($(this).val() == token_vals[i]) { token.selectedIndex = j; };
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

function RemoveCard(id) // remove card from deck via AJAX
{
	var str = new String();
	var card = str.concat("#card_", id);
	var username = GetSessionData('Username');
	var session_id = GetSessionData('SessionID');
	var deck = $("input[name='CurrentDeck']").val();

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
		$(card).attr('onclick', str.concat("return TakeCard(", id, ")")); // allow a card to be removed from deck
		$(slot).fadeOut('slow', function() {
			$(slot).html(empty);
			$(slot).show();
			$(card).animate({ width: 'show', height: 'show' }, 'slow', function() {
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

function GetSessionData(name) // retrieve session data from cookies (or session string if cookies are disabled)
{
	var str = new String();
	var cookie_val = $.cookie(name);
	if (cookie_val != null && cookie_val != "") return cookie_val;
	else return $(str.concat("input[name='", name,"'][type='hidden']")).val();
}

$(document).ready(function() {

	// sends in game chat message when ENTER key is hit
	$("input[name='ChatMessage']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='send_message']").click(); }
	});

	// blocks ENTER key to prevent section redirects
	$("input[name!='ChatMessage'][type!='password'], select").keypress(function(event) { if (event.keyCode == '13') { event.preventDefault(); } });

	// show/hide card pool
	$("button[name='card_pool_switch']").click(function() {
		if ($("button[name='card_pool_switch']").html() == 'Show card pool') // show card pool
		{
			$("button[name='card_pool_switch']").html('Expanding...'); // block switch button while animating

			// repair card pool state if necessary
			$("#card_pool").hide();
			$("#card_pool").css('height', 'hide');
			$("#card_pool").css('opacity', 0);

			// expand card pool
			$("#card_pool").animate({ height: 'show' }, 'slow', function() {
				$("#card_pool").animate({ opacity: 1 }, 'slow', function() {
					$("#card_pool").show();

					// update switch button and hidden data element
					$("button[name='card_pool_switch']").html('Hide card pool');
					$("input[name='CardPool']").val('yes');
				});
			});
		}
		else if ($("button[name='card_pool_switch']").html() == 'Hide card pool') // hide card pool
		{
			$("button[name='card_pool_switch']").html('Collapsing...'); // block switch button while animating
			$("#card_pool").show(); // repair card pool state if necessary

			// collapse card pool
			$("#card_pool").animate({ opacity: 0 }, 'slow', function() {
				$("#card_pool").animate({ height: 'hide' }, 'slow', function() {
					$("#card_pool").hide();

					// update switch button and hidden data element
					$("button[name='card_pool_switch']").html('Show card pool');
					$("input[name='CardPool']").val('no');
				});
			});
		}

		return false;
	});

	// card selector verification (play card)
	$("button[name='play_card'][value='0']").click(function() {
		if ($("input[name='selected_card']:checked").length == 0)
		{
			alert('No card was selected!');
			return false;
		}

		if ($("div.selected_card").parent().hasClass('unplayable'))
		{
			alert('Insufficient resources!');
			return false;
		}

		return true;
	});

	// card selector verification (discard card)
	$("button[name='discard_card']").click(function() {
		if ($("input[name='selected_card']:checked").length == 0)
		{
			alert('No card was selected!');
			return false;
		}

		return true;
	});

	// hide radio buttons (selection is done via card)
	$("input[name='selected_card']").hide();

	// set initial state for action buttons (partially visible)
	$("button[name='play_card'][value='0'],button[name='discard_card'],button[name='preview_card']").css('opacity', 0.6);

	// card selection (via card)
	$("tr.hand:first-child div.karta").click(function() {
		if ($("input[name='selected_card']").length > 0) // active only on player's turn
		{
			if (!$(this).hasClass("selected_card")) // case 1: unselected card is selected
			{
				// unselect previously selected card
				$("input[name='selected_card']:checked").removeAttr("checked");
				$("div.selected_card").removeClass("selected_card");

				// select specified card
				$(this).parent().nextAll("input[name='selected_card']").attr('checked', 'checked');
				$(this).addClass("selected_card");

				if (!$(this).parent().hasClass('unplayable'))
				{
					// case 1: card is playable -> show play and preview buttons
					$("button[name='play_card'][value='0'],button[name='preview_card']").animate({ opacity: 1 }, 'fast');
				}
				else
				{
					// case 2: card is unplayable -> hide play and preview buttons
					$("button[name='play_card'][value='0'],button[name='preview_card']").animate({ opacity: 0.6 }, 'fast');
				}

				// show discard button
				$("button[name='discard_card']").animate({ opacity: 1 }, 'fast');
			}
			else // case 2: selected card is reselected
			{
				// unselect selected card
				$("input[name='selected_card']:checked").removeAttr("checked");
				$("div.selected_card").removeClass("selected_card");

				// return action buttons to initial state
				$("button[name='play_card'][value='0'],button[name='discard_card'],button[name='preview_card']").animate({ opacity: 0.6 }, 'fast');
			}
		}
	});

	// card selection (via card modes)
	$("select.card_modes").click(function() {
		var cur_card = $(this).prevAll("div").children("div.karta");

		if (!cur_card.hasClass("selected_card")) { cur_card.click(); }
	});

	// card preview processing
	$("button[name='preview_card']").click(function() {
		if ($("input[name='selected_card']:checked").length == 0)
		{
			alert('No card was selected!');
			return false;
		}

		if ($("div.selected_card").parent().hasClass('unplayable'))
		{
			alert('Insufficient resources!');
			return false;
		}

		var str = new String();
		var position = $("input[name='selected_card']:checked").val();
		var modes = str.concat("select[name='card_mode[", position, "]']");
		var card_mode = $(modes).val();
		var username = GetSessionData('Username');
		var session_id = GetSessionData('SessionID');
		var game = $("input[name='CurrentGame']").val();

		$.post("AJAXhandler.php", { action: 'preview', Username: username, SessionID: session_id, cardpos: position, mode: card_mode, game_id: game }, function(data){
			alert(data);
		});

		return true;
	});

	// highlight unplayable card in case of mouse hover
	$("div.unplayable > div.karta").mouseenter(function() {
		$(this).animate({ opacity: 1 }, 'fast');
	});

	// return highlighted unplayable card to former state in case of mouse leave
	$("div.unplayable > div.karta").mouseleave(function() {
		$(this).animate({ opacity: 0.6 }, 'fast');
	});
});
