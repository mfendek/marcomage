<?php

	// UPDATE SCRIPT FOR GAMES
	// tranforms old game data format to new one

	require_once('../config.php');
	require_once('../CDatabase.php');
	require_once('../CDeck.php');
	require_once('../CGame.php');
	require_once('../CGameAI.php');
	require_once('../CChat.php');

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

	$gamedb = new CGames($db);

	echo "Updating game data...<br /><br />";

	// get list of untransformed games
	$list = $db->Query("SELECT `Player1`, `Player2`, `Data`, `State`, `GameID` FROM `games` ORDER BY `GameID` ASC");
	if ($list === false) exit('Failed to retrieve games from DB.');

	foreach( $list as $data )
	{
		// get game data
		$game_id = $data['GameID'];
		$player1 = $data['Player1'];
		$player2 = $data['Player2'];
		$state = $data['State'];
		$old_data = $data['Data'];
		$old_data = unserialize($old_data);

		$new_data[1] = $old_data[$player1];
		$new_data[2] = ($state != 'waiting') ? $old_data[$player2] : '';// waiting games don't have player2 data

		// save updated data
		$result = $db->Query('UPDATE `games` SET `Data` = ? WHERE `GameID` = ?', array(serialize($new_data), $game_id));
		if ($result === false) echo 'Failed to save transformed game data (GameID = '.$game_id.').<br />';
	}

	echo "<br /><br />Done.";
?>
