<?php

	require_once('../CDatabase.php');
	require_once('../CGame.php');
	require_once('../CDeck.php');

	$db = new CDatabase("localhost", "arcomage", "", "arcomage");

	$gamedb = new CGames($db);

	echo "Updating game data...\n";

	$result = $db->Query("SELECT `GameID` FROM `games` ORDER BY `GameID`");

	while( $data = $result->Next() )
	{
		$game = $gamedb->GetGame($data['GameID']);

		$p1 = &$game->GameData->Player[$game->Name1()];
		$p2 = &$game->GameData->Player[$game->Name2()];

		if ($game->State != "waiting") // update started games
		{
			// update decks used in games
			$p1->Deck->Tokens = array(1 => 'none', 'none', 'none');
			$p2->Deck->Tokens = array(1 => 'none', 'none', 'none');

			// update game - add token counters
			$p1->TokenNames = array(1 => 'none', 'none', 'none');
			$p2->TokenNames = array(1 => 'none', 'none', 'none');

			$new_tokens = array_fill_keys(array_keys(array(1 => 'none', 'none', 'none')), 0);

			$p1->TokenValues = $p2->TokenValues = $p1->TokenChanges = $p2->TokenChanges = $new_tokens;
		}
		// update waiting games - add tokens to deck
		else $p1->Deck->Tokens = array(1 => 'none', 'none', 'none');

		$game->SaveGame();
	};

	echo "Done.";
?>
