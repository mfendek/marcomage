<?php

	require_once('../CDatabase.php');
	require_once('../CGame.php');
	require_once('../CDeck.php');

	class CGameData
	{
		public $Player;
		public $Current;
		public $Round;
		public $Winner;
		public $Outcome;
		public $Timestamp;
	}

	$db = new CDatabase("localhost", "arcomage", "", "arcomage");

	date_default_timezone_set("Etc/UTC");
	$db->Query("SET time_zone='Etc/UTC'");

	$gamedb = new CGames($db);

	echo "Updating game data...\n";

	$result = $db->Query("SELECT `GameID`, `State`, `Player1`, `Player2`, `Data`, `Note1`, `Note2` FROM `games` ORDER BY `GameID`");

	while( $data = $result->Next() )
	{
		$game_id = $data['GameID'];
		$player1 = $data['Player1'];
		$player2 = $data['Player2'];
		$game = new CGame($game_id, $player1, $player2, $gamedb);

		$game_data = new CGameData;
		$game_data = unserialize($data['Data']);

		$game->State = $data['State'];
		$game->Current = $game_data->Current;
		$game->Round = ($data['State'] != 'waiting') ? $game_data->Round : 1;
		$game->Winner = $game_data->Winner;
		$game->Outcome = $game_data->Outcome;
		$game->LastAction = date('Y-m-d G:i:s', $game_data->Timestamp);
		$game->GameData = $game_data->Player;
		$game->SetNote($player1, $data['Note1']);
		$game->SetNote($player2, $data['Note2']);

		$game->SaveGame();
	};

	echo "Done.";
?>
