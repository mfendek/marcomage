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
	||  false === $db->Query("SET time_zone='Etc/UTC'")
	&&  false === $db->Query("SET time_zone='+0:00'") )
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

	$result = $db->Query('SELECT `Username` FROM (SELECT `Username` FROM `logins` WHERE (UNIX_TIMESTAMP(`Last Query`) < UNIX_TIMESTAMP() - 60*60*24*7*12)) as `logins` INNER JOIN (SELECT `Username` FROM `scores` WHERE `Wins` + `Losses` + `Draws` = 0) as `scores` USING (`Username`) LIMIT 50');

	while( $data = $result->Next() )
	{
		$username = $data['Username'];
		echo 'Deleting player '.htmlencode($username)."...\n<br />";
		$res = $playerdb->DeletePlayer($username);
		echo implode("\n<br />", $res)."\n<br />\n<hr />";
	}

	echo "\n<br />\n<hr />Done.";
?>
