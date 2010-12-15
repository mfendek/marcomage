// MArcomage JavaScript support functions

function TakeCard(id) // add card to deck via AJAX
{
	var str = new String();
	var card = str.concat("#card_", id);
	var username = GetSessionData('Username');
	var session_id = GetSessionData('SessionID');
	var deck = $("input[name='CurrentDeck']").val();

	$.post("AJAXhandler.php", { action: 'take', Username: username, SessionID: session_id, deck_id: deck, card_id: id }, function(data){
		// process result
		var result = data.split(",");
		if (result.length == 1) { alert(result[0]); return false; } // AJAX failed, display error message
		var res_val = result[0];
		var tokens = result[1];
		var avg = result[2];

		var slot = str.concat("#slot_", res_val);

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

	$.post("AJAXhandler.php", { action: 'remove', Username: username, SessionID: session_id, deck_id: deck, card_id: id }, function(data){
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
		$(card).removeClass('taken'); // unmark card as taken
		$(card).find(".karta").css("opacity", 0.6);
		$(card).attr('onclick', str.concat("return TakeCard(", id, ")")); // allow a card to be removed from deck
		$(slot).fadeOut('slow', function() {
			$(slot).html(empty);
			$(slot).show();
			$(card).find(".karta").animate({ opacity: 1 }, 'slow');
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

function ResumeReplay() // replay slideshow -> resume replay
{
	$("button[name='slideshow']").addClass('pushed');
	timer = window.setTimeout("$(window.location).attr('href', $('a#next').attr('href'))", 5000);
}

function PauseReplay() // replay slideshow -> pause replay
{
	$("button[name='slideshow']").removeClass('pushed');
	window.clearTimeout(timer);
}

$(document).ready(function() {

	// executes forum search when ENTER key is hit
	$("input[name='phrase']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='forum_search']").click(); }
	});

	// apply player filters (players section)
	$("input[name='pname_filter']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='filter_players']").click(); }
	});

	// apply card filters (concepts section)
	$("input[name='card_name']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='concepts_filter']").click(); }
	});

	// sends in game chat message when ENTER key is hit
	$("input[name='ChatMessage']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='send_message']").click(); }
	});

	// apply card filters (cards and deck edit sections)
	$("input[name='NameFilter']").keypress(function(event) {
		if (event.keyCode == '13')
		{
			event.preventDefault();
			$("button[name='filter']").click(); // deck edit section
			$("button[name='cards_filter']").click(); // cards section
		}
	});

	// blocks ENTER key to prevent section redirects
	$("input[name!='ChatMessage'][name!='NameFilter'][name!='phrase'][name!='card_name'][name!='pname_filter'][type!='password'], select").keypress(function(event) { if (event.keyCode == '13') { event.preventDefault(); } });

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

	// replay slideshow initialization
	var timer; // global timer

	if ($("a#next").length == 1) // apply only in replay section
	{
		ResumeReplay();
	}

	$("button[name='slideshow']").click(function() {
		if ($("button[name='slideshow']").hasClass('pushed'))
		{
			PauseReplay();
		}
		else
		{
			ResumeReplay();
		}
	});

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

	// card concept delete confirmation
	$("button[name='delete_concept']").click(function() {
		if (confirm("All of the card concept data will be deleted. Are you sure you want to continue?"))
		{
			// skip standard confirmation
			$("button[name='delete_concept']").attr('name', 'delete_concept_confirm');
			return true;
		}
		else return false;
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

	// message delete confirmation
	$("button[name='message_delete']").click(function() {
		if (confirm("Current message will be deleted. Are you sure you want to continue?"))
		{
			// skip standard confirmation
			$("button[name='message_delete']").attr('name', 'message_delete_confirm');
			return true;
		}
		else return false;
	});

	// mass message delete confirmation
	$("button[name='Delete_mass']").click(function() {
		return confirm("All selected messages will be deleted. Are you sure you want to continue?");
	});

	// open game note
	$("a#game_note").click(function(event) {
		 event.preventDefault();
		 $("#game_note_dialog").dialog("open");
	});

	// game note handler
	$("#game_note_dialog").dialog({
		autoOpen: false,
		show: "fade",
		hide: "fade",
		buttons: {
			Save: function()
			{
				var game_note = $("textarea[name='Content']").val();

				// check user input
				if (game_note.length > 1000) alert('Game note is too long');
				else
				{
					var username = GetSessionData('Username');
					var session_id = GetSessionData('SessionID');
					var game = $("input[name='CurrentGame']").val();

					$.post("AJAXhandler.php", { action: 'save_note', Username: username, SessionID: session_id, game_id: game, note: game_note }, function(data){
						if (data == "Game note saved")
						{
							// update note button highlight
							// case 1: note is empty (remove highlight)
							if (game_note == "")
								$("a#game_note").removeClass('marked_button');

							// case 2: note is not empty (add highlight if not present)
							else if (!$("a#game_note").hasClass('marked_button'))
								$("a#game_note").addClass('marked_button');

							$("#game_note_dialog").dialog("close");
						}
						else alert(data); // print error message
					});
				}
			},
			Clear: function()
			{
				var username = GetSessionData('Username');
				var session_id = GetSessionData('SessionID');
				var game = $("input[name='CurrentGame']").val();

				$.post("AJAXhandler.php", { action: 'clear_note', Username: username, SessionID: session_id, game_id: game }, function(data){
					if (data == "Game note cleared")
					{
						// clear input field
						$("textarea[name='Content']").val('');

						// update note button highlight (remove highlight)
						$("a#game_note").removeClass('marked_button');
					}
					else alert(data); // print error message
				});
			},
			Back: function()
			{
				$(this).dialog("close");
			}
		}
	});
});
