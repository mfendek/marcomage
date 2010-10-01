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
	require_once('CDeck.php');
	require_once('utils.php');

	$db = new CDatabase($server, $username, $password, $database);

	date_default_timezone_set("Etc/UTC");
	$db->Query("SET time_zone='Etc/UTC'");

	$logindb = new CLogin($db);
	$carddb = new CCards();
	$deckdb = new CDecks($db);

	$_POST['Username'] = postdecode($_POST['Username']);

	// validate session
	$session = $logindb->Login();
	if (!$session) { echo 'Invalid session.'; exit; }

	$user_name = $session->Username();

	if ($_POST['action'] == "take")
	{
		if (!isset($_POST['deckname']) OR $_POST['deckname'] == "") { echo 'Invalid deck name.'; exit; }
		if (!isset($_POST['card_id']) OR $_POST['card_id'] == "") { echo 'Invalid card.'; exit; }

		$deck_name = $_POST['deckname'];
		$card_id = $_POST['card_id'];
		$tokens = 'no'; // default results

		// validate deck
		$deck = $deckdb->GetDeck($user_name, $deck_name);
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
		if (!isset($_POST['deckname']) OR $_POST['deckname'] == "") { echo 'Invalid deck name.'; exit; }
		if (!isset($_POST['card_id']) OR $_POST['card_id'] == "") { echo 'Invalid card.'; exit; }

		$deck_name = $_POST['deckname'];
		$card_id = $_POST['card_id'];

		// download deck
		$deck = $deckdb->GetDeck($user_name, $deck_name);
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
?>
