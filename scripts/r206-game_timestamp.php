<?php

	require_once('CDatabase.php');
	require_once('CGame.php');
	
	$db = new CDatabase("localhost", "arcomage", "", "arcomage");
	$games = new CGames($db);

	echo "Correcting game data...";

	$result = $db->Query("SELECT `GameID` FROM `games` ORDER BY `GameID`");

	while( $data = $result->Next() )
	{
		$game = $games->GetGame($data['GameID']);
		$game->GameData->Timestamp = time();
		$game->SaveGame();
	};

	echo "Done.";
	echo "\n";
?>
