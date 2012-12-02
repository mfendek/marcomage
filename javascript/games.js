/****************************************
 * MArcomage JavaScript - Games section *
 ****************************************/

function GamesRefresh() // refresh user screen within the games list section
{
	window.location.replace($('div#hosted_games > p > a.pushed').attr('href'));
}

function GameRefresh() // refresh user screen within the game
{
	// case 1: it is not player's turn in current game and the next game button is available - go to next game
	if ($("div#game button[name='discard_card']").length == 0 && $("div#game button[name='active_game']").length > 0)
	{
		$("div#game button[name='active_game']").click();
	}
	// case 2: stay in current game and refresh screen
	else
	{
		window.location.replace($('a#game_refresh').attr('href'));
	}
}

function StartGameRefresh()
{
	var timer = 0;

	if ($("div#game > input[name='Autorefresh']").length == 1)
	{
		timer = window.setTimeout('GameRefresh()', parseInt($("div#game > input[name='Autorefresh']").val()) * 1000);
	}

	return timer;
}

function autoAiMove() // execute auto AI move
{
	if ($("div#game button[name='ai_move']").length == 1)
	{
		$("div#game button[name='ai_move']").click();
	}
}

$(document).ready(function() {

	// initialize games list refresh if active
	var games_timer = 0;

	if ($("div#games > input[name='Autorefresh']").length == 1)
	{
		games_timer = window.setTimeout('GamesRefresh()', parseInt($("div#games > input[name='Autorefresh']").val()) * 1000);
	}

	// activate auto AI move
	if ($("div#game > input[name='AutoAi']").length == 1)
	{
		window.setTimeout('autoAiMove()', parseInt($("div#game > input[name='AutoAi']").val()) * 1000);
	}

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
				$(this).css('border-color', '#000000');

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
				$("div.selected_card").css('border-color', '#ffffff');
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

	// initialize in-game refresh if active
	var game_timer = 0;

	if ($("div#game > input[name='Autorefresh']").length == 1)
	{
		game_timer = StartGameRefresh();
	}

	$("input[name='ChatMessage']").keypress(function(event) {
		// disable auto-refresh when user is typing chat message
		window.clearTimeout(game_timer);

		// sends in game chat message by pressing ENTER key
		if (event.keyCode == '13') { event.preventDefault(); $("button[name='send_message']").click(); }
	});

	$("#game_note_dialog").bind( "dialogclose", function() {
		// enable autorefresh when user closes the game note
		game_timer = StartGameRefresh();
	});

	// open game note
	$("a#game_note").click(function(event) {
		 event.preventDefault();
		 // disable autorefresh when user opens the game note
		 window.clearTimeout(game_timer);
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

	$("#chat_dialog").bind( "dialogclose", function() {
		// enable autorefresh when user closes the chat
		game_timer = StartGameRefresh();
	});

	// open chat
	$("button[name='show_chat']").click(function(event) {
		event.preventDefault();

		// disable autorefresh when user opens the chat
		window.clearTimeout(game_timer);

		// reset chat notification for current player
		var username = GetSessionData('Username');
		var session_id = GetSessionData('SessionID');
		var game = $("input[name='CurrentGame']").val();

		$.post("AJAXhandler.php", { action: 'reset_chat_notification', Username: username, SessionID: session_id, game_id: game }, function(data){
			var result = $.parseJSON(data);
			if (result.error) { alert(result.error); return false; } // AJAX failed, display error message
		});

		// scrolling must be done only after the dialog has been opened
		$("#chat_dialog").bind( "dialogopen", function() {
			$("#chat_dialog > div.scroll_max").delay(400).scrollTo('max');
		});
		$("#chat_dialog").dialog("open");
	});

	// chat handler
	$("#chat_dialog").dialog({
		autoOpen: false,
		show: "fade",
		hide: "fade",
		width: 500,
		height: 600,
		buttons: {
			B: function()
			{
				AddTags('[b]', '[/b]', 'chat_area');
			},
			I: function()
			{
				AddTags('[i]', '[/i]', 'chat_area');
			},
			L: function()
			{
				AddTags('[link]', '[/link]', 'chat_area');
			},
			U: function()
			{
				AddTags('[url]', '[/url]', 'chat_area');
			},
			Q: function()
			{
				AddTags('[quote]', '[/quote]', 'chat_area');
			},
			Send: function()
			{
				var chat_message = $("textarea[name='chat_area']").val();

				// check user input
				if (chat_message.length > 300) alert('Chat message is too long');
				else
				{
					var username = GetSessionData('Username');
					var session_id = GetSessionData('SessionID');
					var game = $("input[name='CurrentGame']").val();

					$.post("AJAXhandler.php", { action: 'send_chat_message', Username: username, SessionID: session_id, game_id: game, message: chat_message }, function(data){
						var result = $.parseJSON(data);
						if (result.error) { alert(result.error); return false; } // AJAX failed, display error message
						else
						{
							$(this).dialog("close");
							GameRefresh();
						}
					});
				}
			},
			Back: function()
			{
				$(this).dialog("close");
			}
		}
	});

	// open chat automatically if there are new messages
	if ($("div#game button.marked_button[name='show_chat']").length == 1)
	{
		$("div#game button.marked_button[name='show_chat']").click();
	}

});
