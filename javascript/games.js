/****************************************
 * MArcomage JavaScript - Games section *
 ****************************************/

function GamesRefresh() // refresh user screen within the games list section
{
	window.location.replace($('div#hosted_games > p > a.pushed').attr('href'));
}

function GameRefresh() // refresh user screen within the game
{
	window.location.replace($('a#game_refresh').attr('href'));
}

$(document).ready(function() {

	// sends in game chat message by pressing ENTER key
	$("input[name='ChatMessage']").keypress(function(event) {
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='send_message']").click(); }
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
			alert("Card can't be played.");
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
				$(this).effect('highlight');

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
			alert("Card can't be played.");
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
			var result = $.parseJSON(data);
			if (result.error) { alert(result.error); return false; } // AJAX failed, display error message

			// output preview information
			alert(result.info);
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
						var result = $.parseJSON(data);
						if (result.error) { alert(result.error); return false; } // AJAX failed, display error message

						// update note button highlight
						// case 1: note is empty (remove highlight)
						if (game_note == "")
							$("a#game_note").removeClass('marked_button');

						// case 2: note is not empty (add highlight if not present)
						else if (!$("a#game_note").hasClass('marked_button'))
							$("a#game_note").addClass('marked_button');

						$("#game_note_dialog").dialog("close");
					});
				}
			},
			Clear: function()
			{
				var username = GetSessionData('Username');
				var session_id = GetSessionData('SessionID');
				var game = $("input[name='CurrentGame']").val();

				$.post("AJAXhandler.php", { action: 'clear_note', Username: username, SessionID: session_id, game_id: game }, function(data){
					var result = $.parseJSON(data);
					if (result.error) { alert(result.error); return false; } // AJAX failed, display error message

					// clear input field
					$("textarea[name='Content']").val('');

					// update note button highlight (remove highlight)
					$("a#game_note").removeClass('marked_button');
				});
			},
			Back: function()
			{
				$(this).dialog("close");
			}
		}
	});

	// scroll standard chat
	$("div.chatsection > div.scroll_max").scrollTo('max');

});