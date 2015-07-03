/****************************************
 * MArcomage JavaScript - Decks section *
 ****************************************/

function TakeCard(id) // add card to deck via AJAX
{
	var str = new String();
	var card = str.concat("#card_", id);
	var username = GetSessionData('Username');
	var session_id = GetSessionData('SessionID');
	var deck = $("input[name='CurrentDeck']").val();

	$.post("AJAXhandler.php", { action: 'take', Username: username, SessionID: session_id, deck_id: deck, card_id: id }, function(data){
		var result = $.parseJSON(data);
		if (result.error) { alert(result.error); return false; } // AJAX failed, display error message

		var slot = str.concat("#slot_", result.slot);

		// move selected card to deck
		$(card).removeAttr('onclick'); // disallow the card to be removed from the deck (prevent double clicks)
		$(card).find(".karta").animate({ opacity: 0.6 }, 'slow', function() {
			$(slot).html($(card).html());
			$(card).addClass('taken'); // mark card as taken
			$(slot).find(".karta").css("opacity", 1);
			$(slot).hide();
			$(slot).fadeIn('slow');
			$(slot).attr('onclick', str.concat("return RemoveCard(", id, ")")); // allow a card to be removed from deck
		});

		// update tokens when needed
		if (result.tokens != "no")
		{
			var token;

			$("#tokens > select").each(function(i) {
				token = document.getElementsByName(str.concat("Token", i + 1)).item(0);
				$(this).find("option").each(function(j) {
					if ($(this).val() == result.tokens[i + 1]) { token.selectedIndex = j; };
				});
			});
		}

		// recalculate avg cost per turn
		$("#cost_per_turn > b").each(function(i) {
			$(this).html(result.avg[i]);
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

	$.post("AJAXhandler.php", { action: 'remove', Username: username, SessionID: session_id, deck_id: deck, card_id: id }, function(data){
		var result = $.parseJSON(data);
		if (result.error) { alert(result.error); return false; } // AJAX failed, display error message

		var slot = str.concat("#slot_", result.slot);
		var empty = '<div class="karta no_class zero_cost with_bgimage"><div class="null">0</div><h5>Empty</h5><img src="img/cards/card_0.png" width="80px" height="60px" alt="" /><p></p><div></div></div>';

		// move selected card to card pool
		$(slot).removeAttr('onclick'); // disallow the card to be removed from the deck (prevent double clicks)
		$(slot).find("noscript").remove(); // remove return card button
		$(card).removeClass('taken'); // unmark card as taken
		$(card).find(".karta").css("opacity", 0.6);
		$(card).attr('onclick', str.concat("return TakeCard(", id, ")")); // allow a card to be removed from deck
		$(slot).fadeOut('slow', function() {
			$(slot).html(empty);
			$(slot).show();
			$(card).find(".karta").animate({ opacity: 1 }, 'slow');
		});

		// recalculate avg cost per turn
		$("#cost_per_turn > b").each(function(i) {
			$(this).html(result.avg[i]);
		});
 });

	return false; // disable standard processing
}

$(document).ready(function() {

	// apply card filters by pressing ENTER key
	$("input[name='NameFilter']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='filter']").click(); }
	});

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

	// deck reset confirmation
	$("button[name='reset_deck_prepare']").click(function() {
		if (confirm("All cards will be removed from the deck, all token counters will be reset and deck statistics will be reset as well. Are you sure you want to continue?"))
		{
			// skip standard confirmation
			$("button[name='reset_deck_prepare']").attr('name', 'reset_deck_confirm');
			return true;
		}
		else return false;
	});

	// deck statistics reset confirmation
	$("button[name='reset_stats_prepare']").click(function() {
		if (confirm("Deck statistics will be reset. Are you sure you want to continue?"))
		{
			// skip standard confirmation
			$("button[name='reset_stats_prepare']").attr('name', 'reset_stats_confirm');
			return true;
		}
		else return false;
	});

	// deck share confirmation
	$("button[name='share_deck']").click(function() {
		return confirm("Are you sure you want to share this deck to other players?");
	});

	// import shared deck confirmation
	$("button[name='import_shared_deck']").click(function() {

		// extract target deck name
		var target_deck_id = $("select[name='SelectedDeck']").val();
		var target_deck = $("select[name='SelectedDeck'] >  option[value='" + target_deck_id + "']").text();

		// extract source deck name
		var source_deck = $(this).parent().parent().find("a.deck").text();

		return confirm("Are you sure you want to import " + source_deck + " into " + target_deck + "?");
	});

	// open deck note
	$("a#deck_note").click(function(event) {
		 event.preventDefault();
		 $("#deck_note_dialog").dialog("open");
	});

	// deck note handler
	$("#deck_note_dialog").dialog({
		autoOpen: false,
		show: "fade",
		hide: "fade",
		buttons: {
			Save: function()
			{
				var deck_note = $("textarea[name='Content']").val();

				// check user input
				if (deck_note.length > 1000) alert('Deck note is too long');
				else
				{
					var username = GetSessionData('Username');
					var session_id = GetSessionData('SessionID');
					var deck = $("input[name='CurrentDeck']").val();

					$.post("AJAXhandler.php", { action: 'save_dnote', Username: username, SessionID: session_id, deck_id: deck, note: deck_note }, function(data){
						var result = $.parseJSON(data);
						if (result.error) { alert(result.error); return false; } // AJAX failed, display error message

						// update note button highlight
						// case 1: note is empty (remove highlight)
						if (deck_note == "")
							$("a#deck_note").removeClass('marked_button');

						// case 2: note is not empty (add highlight if not present)
						else if (!$("a#deck_note").hasClass('marked_button'))
							$("a#deck_note").addClass('marked_button');

						$("#deck_note_dialog").dialog("close");
					});
				}
			},
			Clear: function()
			{
				var username = GetSessionData('Username');
				var session_id = GetSessionData('SessionID');
				var deck = $("input[name='CurrentDeck']").val();

				$.post("AJAXhandler.php", { action: 'clear_dnote', Username: username, SessionID: session_id, deck_id: deck }, function(data){
					var result = $.parseJSON(data);
					if (result.error) { alert(result.error); return false; } // AJAX failed, display error message

					// clear input field
					$("textarea[name='Content']").val('');

					// update note button highlight (remove highlight)
					$("a#deck_note").removeClass('marked_button');
				});
			},
			Back: function()
			{
				$(this).dialog("close");
			}
		}
	});

});