<?php

	// UPDATE SCRIPT FOR REPLAYS
	// tranforms data format of `Current`

	require_once('../config.php');
	require_once('../CDatabase.php');
	require_once('../CReplay.php');

	$db = new CDatabase($server, $username, $password, $database);
	if( $db->status != 'SUCCESS' )
	{
		header("Content-Type: text/html");
		die("Unable to connect to database, aborting.");
	}

	if( false === date_default_timezone_set("Etc/UTC")
	||  false === $db->Query("SET time_zone='Etc/UTC'")
	&&  false === $db->Query("SET time_zone='+0:00'") )
	{
		header("Content-Type: text/html");
		die("Unable to configure time zone, aborting.");
	}

	echo "Updating replay data...<br /><br />";

	// get list of untransformed replays
	$list = $db->Query("SELECT `Player1`, `Player2`, `Data`, `GameID` FROM `replays` WHERE `Deleted` = FALSE ORDER BY `GameID` ASC");
	if ($list === false) exit('Failed to retrieve replays from DB.');

	foreach( $list as $data )
	{
		$game_id = $data['GameID'];
		$player1 = $data['Player1'];
		$player2 = $data['Player2'];
		$replay_data = unserialize(gzuncompress($data['Data']));
		$new_data = array();

		foreach( $replay_data as $turn => $turn_data )
		{
			$turn_data->Current = ($turn_data->Current == $player1) ? 1 : 2;
			$new_data[$turn] = $turn_data;
		}

		// save updated data
		$result = $db->Query("UPDATE `replays` SET `Data` = ? WHERE `GameID` = ?", array(gzcompress(serialize($new_data)), $game_id));
		if ($result === false) echo 'Failed to transform replay (GameID = '.$game_id.').<br />';
	}

	echo "<br /><br />Done.";
?>
