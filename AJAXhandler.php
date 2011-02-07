<?php
/*
	AJAX handler
*/
?>
<?php

	// check required input data
	if (!isset($_POST['action']) OR $_POST['action'] == "") { echo 'Invalid action.'; exit; }
	if (!isset($_POST['Username']) OR $_POST['Username'] == "") { echo 'Invalid username.'; exit; }
	if (!isset($_POST['SessionID']) OR $_POST['SessionID'] == "") { echo 'Invalid session id.'; exit; }

	require_once('config.php');
	require_once('CDatabase.php');
	require_once('CLogin.php');
	require_once('CCard.php');
	require_once('CKeyword.php');
	require_once('CDeck.php');
	require_once('CGame.php');
	require_once('CGameAI.php');
	require_once('CStatistics.php');
	require_once('utils.php');

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
	$statistics = new CStatistics($db);

	$_POST['Username'] = postdecode($_POST['Username']);

	// validate session
	$session = $logindb->Login();
	if (!$session) { echo 'Invalid session.'; exit; }

	$user_name = $session->Username();

	if ($_POST['action'] == "take")
	{
		if (!isset($_POST['deck_id']) OR $_POST['deck_id'] == "") { echo 'Invalid deck ID.'; exit; }
		if (!isset($_POST['card_id']) OR $_POST['card_id'] == "") { echo 'Invalid card.'; exit; }

		$deck_id = $_POST['deck_id'];
		$card_id = $_POST['card_id'];
		$tokens = 'no'; // default results

		// validate deck
		$deck = $deckdb->GetDeck($user_name, $deck_id);
		if (!$deck) { echo 'Invalid deck'; exit; }

		// verify card
		if (!is_numeric($card_id)) { echo 'Invalid card.'; exit; }

		// add card, saving the deck on success
		$slot = $deck->AddCard($card_id);
		if ($slot)
		{
			// set tokens when deck is finished and player forgot to set them
			if ((count(array_diff($deck->DeckData->Tokens, array('none'))) == 0) AND $deck->isReady())
			{
				$deck->SetAutoTokens();
				$tokens = implode(";", $deck->DeckData->Tokens); // pass updated tokens to result
			}

			$deck->SaveDeck();

			// recalculate the average cost per turn label
			$avg = implode(";", $deck->AvgCostPerTurn());
		}
		else { echo 'Unable to add the chosen card to this deck.'; exit; }

		echo implode(",", array($slot, $tokens, $avg));
	}
	elseif($_POST['action'] == "remove")
	{
		if (!isset($_POST['deck_id']) OR $_POST['deck_id'] == "") { echo 'Invalid deck name.'; exit; }
		if (!isset($_POST['card_id']) OR $_POST['card_id'] == "") { echo 'Invalid card.'; exit; }

		$deck_id = $_POST['deck_id'];
		$card_id = $_POST['card_id'];

		// download deck
		$deck = $deckdb->GetDeck($user_name, $deck_id);
		if (!$deck) { echo 'Invalid deck'; exit; }

		// verify card
		if (!is_numeric($card_id)) { echo 'Invalid card.'; exit; }

		// remove card, saving the deck on success
		$slot = $deck->ReturnCard($card_id);
		if ($slot)
		{
			$deck->SaveDeck();
			// recalculate the average cost per turn label
			$avg = implode(";", $deck->AvgCostPerTurn());
		}
		else { echo 'Unable to remove the chosen card from this deck.'; exit; }

		echo implode(",", array($slot, $avg));
	}
	elseif($_POST['action'] == "preview")
	{
		if (!isset($_POST['cardpos']) OR $_POST['cardpos'] == "") { echo 'Invalid card position.'; exit; }
		if (!isset($_POST['game_id']) OR $_POST['game_id'] == "") { echo 'Invalid game id.'; exit; }

		$cardpos = $_POST['cardpos'];
		$mode = (isset($_POST['mode']) AND $_POST['mode'] != "") ? $_POST['mode'] : 0;
		$game_id = $_POST['game_id'];

		// download game
		$game = $gamedb->GetGame($game_id);
		if (!$game) { echo 'Invalid game.'; exit; }

		// verify inputs
		if (!is_numeric($cardpos)) { echo 'Invalid card position.'; exit; }
		if (!is_numeric($mode)) { echo 'Invalid mode.'; exit; }

		if ($game->GetGameMode('HiddenCards') == 'yes') { echo 'Action not allowed in this game mode.'; exit; }
		if ($user_name != $game->Name1() AND $user_name != $game->Name2()) { echo 'Action not allowed.'; exit; }

		$preview_data = $game->CalculatePreview($user_name, $cardpos, $mode);
		if (!is_array($preview_data))
			echo $preview_data;
		else
			echo $game->FormatPreview($preview_data);
	}
	elseif($_POST['action'] == "save_note")
	{
		if (!isset($_POST['note'])) { echo 'Invalid game note.'; exit; }
		if (!isset($_POST['game_id']) OR $_POST['game_id'] == "") { echo 'Invalid game id.'; exit; }

		$note = $_POST['note'];
		$game_id = $_POST['game_id'];

		// download game
		$game = $gamedb->GetGame($game_id);
		if (!$game) { echo 'Invalid game.'; exit; }

		// check access
		if ($user_name != $game->Name1() AND $user_name != $game->Name2()) { echo 'Action not allowed.'; exit; }

		// verify inputs
		if (strlen($note) > MESSAGE_LENGTH) { $error = "Game note is too long"; exit; }

		$game->SetNote($user_name, $note);
		$result = $game->SaveGame();

		if ($result) echo "Game note saved";
		else echo "Failed to save game note";
	}
	elseif($_POST['action'] == "clear_note")
	{
		if (!isset($_POST['game_id']) OR $_POST['game_id'] == "") { echo 'Invalid game id.'; exit; }

		$game_id = $_POST['game_id'];

		// download game
		$game = $gamedb->GetGame($game_id);
		if (!$game) { echo 'Invalid game.'; exit; }

		// check access
		if ($user_name != $game->Name1() AND $user_name != $game->Name2()) { echo 'Action not allowed.'; exit; }

		$game->ClearNote($user_name);
		$result = $game->SaveGame();

		if ($result) echo "Game note cleared";
		else echo "Failed to clear game note";
	}
?>
