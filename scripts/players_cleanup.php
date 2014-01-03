<?php

	/* ---------------------------------------------- *
	 * | MARCOMAGE PHP SCRIPT - deletes dead users  | *
	 * ---------------------------------------------- */
	/*

	- deletes dead users (no activity in more than 12 weeks and zero score) and their related data
	- deletes 50 users at a time
	- provides debug information about delete procedure

	*/

	require_once('../config.php');
	require_once('../CDatabase.php');
	require_once('../CLogin.php');
	require_once('../CAward.php');
	require_once('../CScore.php');
	require_once('../CDeck.php');
	require_once('../CGame.php');
	require_once('../CReplay.php');
	require_once('../CSettings.php');
	require_once('../CChat.php');
	require_once('../CPlayer.php');
	require_once('../CMessage.php');
	require_once('../utils.php');

	$db = new CDatabase($server, $username, $password, $database);
	if( $db->status != 'SUCCESS' )
	{
	    header("Content-type: text/html");
	    die("Unable to connect to database, aborting.");
	}

	if( false === date_default_timezone_set("Etc/UTC")
	||  false === $db->query("SET time_zone='Etc/UTC'")
	&&  false === $db->query("SET time_zone='+0:00'") )
	{
		header("Content-type: text/html");
		die("Unable to configure time zone, aborting.");
	}

	$logindb = new CLogin($db);
	$scoredb = new CScores($db);
	$deckdb = new CDecks($db);
	$gamedb = new CGames($db);
	$replaydb = new CReplays($db);
	$settingdb = new CSettings($db);
	$playerdb = new CPlayers($db);
	$messagedb = new CMessage($db);

	echo "Deleting player data..."."\n<br />\n<br />";

	$result = $db->query('SELECT `Username` FROM (SELECT `Username` FROM `logins` WHERE `Last Query` < NOW() - INTERVAL 12 WEEK) as `logins` INNER JOIN (SELECT `Username` FROM `scores` WHERE `Wins` + `Losses` + `Draws` = 0) as `scores` USING (`Username`) LIMIT 50');

	foreach ($result as $data)
	{
		$username = $data['Username'];
		echo 'Deleting player '.htmlencode($username)."...\n<br />";
		$res = $playerdb->deletePlayer($username);
		echo (($res) ? 'SUCCESS' : 'FAILURE')."\n<br />\n<hr />";
	}

	echo "\n<br />\n<hr />Done.";
?>
