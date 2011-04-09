<?php
/*
	AJAX handler
*/
?>
<?php
	// redirect errors to our own logfile
	error_reporting(-1); // everything
	ini_set("display_errors", "Off");
	ini_set("error_log", "logs/arcomage-error-".strftime('%Y%m%d').".log");

	do { // dummy scope

	// check required input data
	if (!isset($_POST['action']) OR $_POST['action'] == "") { $error = 'Invalid action.'; break; }
	if (!isset($_POST['Username']) OR $_POST['Username'] == "") { $error = 'Invalid username.'; break; }
	if (!isset($_POST['SessionID']) OR $_POST['SessionID'] == "") { $error = 'Invalid session id.'; break; }

	require_once('config.php');
	require_once('CDatabase.php');
	require_once('CLogin.php');
	require_once('CCard.php');
	require_once('CKeyword.php');
	require_once('CDeck.php');
	require_once('CGame.php');
	require_once('CGameAI.php');
	require_once('CChat.php');
	require_once('CReplay.php');
	require_once('CStatistics.php');
	require_once('CPlayer.php');
	require_once('utils.php');
	require_once('Access.php');

	$db = new CDatabase($server, $username, $password, $database);
	if( $db->status != 'SUCCESS' )
	{
	    header("Content-type: text/html");
	    die("Unable to connect to database, aborting.");
	}

	if( false === date_default_timezone_set("Etc/UTC")
	||  false === $db->Query("SET time_zone='Etc/UTC'")
	&&  false === $db->Query("SET time_zone='+0:00'") )
	{
		header("Content-type: text/html");
		die("Unable to configure time zone, aborting.");
	}

	$logindb = new CLogin($db);
	$carddb = new CCards();
	$keyworddb = new CKeywords();
	$deckdb = new CDecks($db);
	$gamedb = new CGames($db);
	$replaydb = new CReplays($db);
	$statistics = new CStatistics($db);
	$playerdb = new CPlayers($db);

	$_POST['Username'] = postdecode($_POST['Username']);

	// validate session
	$session = $logindb->Login();
	if (!$session) { $error = 'Invalid session.'; break; }

	$user_name = $session->Username();
	$player = $playerdb->GetPlayer($user_name);

	if ($_POST['action'] == "take")
	{
		if (!isset($_POST['deck_id']) OR $_POST['deck_id'] == "") { $error = 'Invalid deck ID.'; break; }
		if (!isset($_POST['card_id']) OR $_POST['card_id'] == "") { $error = 'Invalid card.'; break; }

		$deck_id = $_POST['deck_id'];
		$card_id = $_POST['card_id'];
		$tokens = 'no'; // default results

		// validate deck
		$deck = $deckdb->GetDeck($user_name, $deck_id);
		if (!$deck) { $error = 'Invalid deck.'; break; }

		// verify card
		if (!is_numeric($card_id)) { $error = 'Invalid card.'; break; }

		// add card, saving the deck on success
		$slot = $deck->AddCard($card_id);
		if ($slot)
		{
			// set tokens when deck is finished and player forgot to set them
			if ((count(array_diff($deck->DeckData->Tokens, array('none'))) == 0) AND $deck->isReady())
			{
				$deck->SetAutoTokens();
				$tokens = $deck->DeckData->Tokens; // pass updated tokens to result
			}

			$deck->SaveDeck();

			// recalculate the average cost per turn label
			$avg = array_values($deck->AvgCostPerTurn());
		}
		else { $error = 'Unable to add the chosen card to this deck.'; break; }

		$result = array('slot' => $slot, 'tokens' => $tokens, 'avg' => $avg);
	}
	elseif($_POST['action'] == "remove")
	{
		if (!isset($_POST['deck_id']) OR $_POST['deck_id'] == "") { $error = 'Invalid deck name.'; break; }
		if (!isset($_POST['card_id']) OR $_POST['card_id'] == "") { $error = 'Invalid card.'; break; }

		$deck_id = $_POST['deck_id'];
		$card_id = $_POST['card_id'];

		// download deck
		$deck = $deckdb->GetDeck($user_name, $deck_id);
		if (!$deck) { $error = 'Invalid deck.'; break; }

		// verify card
		if (!is_numeric($card_id)) { $error = 'Invalid card.'; break; }

		// remove card, saving the deck on success
		$slot = $deck->ReturnCard($card_id);
		if ($slot)
		{
			$deck->SaveDeck();
			// recalculate the average cost per turn label
			$avg = array_values($deck->AvgCostPerTurn());
		}
		else { $error = 'Unable to remove the chosen card from this deck.'; break; }

		$result = array('slot' => $slot, 'avg' => $avg);
	}
	elseif($_POST['action'] == "preview")
	{
		if (!isset($_POST['cardpos']) OR $_POST['cardpos'] == "") { $error = 'Invalid card position.'; break; }
		if (!isset($_POST['game_id']) OR $_POST['game_id'] == "") { $error = 'Invalid game id.'; break; }

		$cardpos = $_POST['cardpos'];
		$mode = (isset($_POST['mode']) AND $_POST['mode'] != "") ? $_POST['mode'] : 0;
		$game_id = $_POST['game_id'];

		// download game
		$game = $gamedb->GetGame($game_id);
		if (!$game) { $error = 'Invalid game.'; break; }

		// verify inputs
		if (!is_numeric($cardpos)) { $error = 'Invalid card position.'; break; }
		if (!is_numeric($mode)) { $error = 'Invalid mode.'; break; }

		if ($game->GetGameMode('HiddenCards') == 'yes') { $error = 'Action not allowed in this game mode.'; break; }
		if ($user_name != $game->Name1() AND $user_name != $game->Name2()) { $error = 'Action not allowed.'; break; }

		$preview_data = $game->CalculatePreview($user_name, $cardpos, $mode);
		if (!is_array($preview_data))
			$error = $preview_data;
		else
			$result = array('info' => $game->FormatPreview($preview_data));
	}
	elseif($_POST['action'] == "save_note")
	{
		if (!isset($_POST['note'])) { $error = 'Invalid game note.'; break; }
		if (!isset($_POST['game_id']) OR $_POST['game_id'] == "") { $error = 'Invalid game id.'; break; }

		$note = $_POST['note'];
		$game_id = $_POST['game_id'];

		// download game
		$game = $gamedb->GetGame($game_id);
		if (!$game) { $error = 'Invalid game.'; break; }

		// check access
		if ($user_name != $game->Name1() AND $user_name != $game->Name2()) { $error = 'Action not allowed.'; break; }

		// verify inputs
		if (strlen($note) > MESSAGE_LENGTH) { $error = 'Game note is too long.'; break; }

		$game->SetNote($user_name, $note);
		$result = $game->SaveGame();

		if ($result) $result = array('info' => 'Game note saved.');
		else $error = 'Failed to save game note.';
	}
	elseif($_POST['action'] == "clear_note")
	{
		if (!isset($_POST['game_id']) OR $_POST['game_id'] == "") { $error = 'Invalid game id.'; break; }

		$game_id = $_POST['game_id'];

		// download game
		$game = $gamedb->GetGame($game_id);
		if (!$game) { $error = 'Invalid game.'; break; }

		// check access
		if ($user_name != $game->Name1() AND $user_name != $game->Name2()) { $error = 'Action not allowed.'; break; }

		$game->ClearNote($user_name);
		$result = $game->SaveGame();

		if ($result) $result = array('info' => 'Game note cleared');
		else $error = 'Failed to clear game note.';
	}
	elseif($_POST['action'] == "send_chat_message")
	{
		// check access rights
		if (!$access_rights[$player->Type()]["chat"]) { $error = 'Access denied.'; break; }

		if (!isset($_POST['message'])) { $error = 'Invalid chat message.'; break; }
		if (!isset($_POST['game_id']) OR $_POST['game_id'] == "") { $error = 'Invalid game id.'; break; }

		$msg = $_POST['message'];
		$game_id = $_POST['game_id'];

		// download game
		$game = $gamedb->GetGame($game_id);
		if (!$game) { $error = 'Invalid game.'; break; }

		// check access
		if ($user_name != $game->Name1() AND $user_name != $game->Name2()) { $error = 'Action not allowed.'; break; }

		// verify user input
		if (trim($msg) == '') { $error = 'Unable to send empty chat message.'; break; }
		if (strlen($msg) > CHAT_LENGTH) { $error = 'Chat message is too long.'; break; }

		// check if chat is allowed (can't chat with a computer player)
		if ($game->GetGameMode('AIMode') == 'yes') { $error = 'Chat not allowed!'; break; }

		$result = $game->SaveChatMessage($msg, $user_name);

		if ($result) $result = array('info' => 'Chat message sent.');
		else $error = 'Failed to send chat message.';
	}
	elseif($_POST['action'] == "reset_chat_notification")
	{
		if (!isset($_POST['game_id']) OR $_POST['game_id'] == "") { $error = 'Invalid game id.'; break; }

		$game_id = $_POST['game_id'];

		// download game
		$game = $gamedb->GetGame($game_id);
		if (!$game) { $error = 'Invalid game.'; break; }

		// check access
		if ($user_name != $game->Name1() AND $user_name != $game->Name2()) { $error = 'Action not allowed.'; break; }

		// check if chat is allowed (can't chat with a computer player)
		if ($game->GetGameMode('AIMode') == 'yes') { $error = 'Chat not allowed!'; break; }

		$result = $game->ResetChatNotification($user_name);

		if ($result) $result = array('info' => 'Chat notification reset.');
		else $error = 'Failed reset chat notification.';
	}
	else
		$error = 'Invalid request.';

	} while(0); // end dummy scope

	// error handler
	if (isset($error)) $result = array('error' => $error);

	// output result
	echo json_encode($result);
?>
