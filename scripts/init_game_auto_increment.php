<?php

	// Initialize game id auto-increment
	// this is needed because auto-increment drops to max id + 1 when mysql is restarted
	// this is a problem because games get deleted, but replays are kept and they share ids

	require_once('../config.php');
	require_once('../CDatabase.php');

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

	echo "Setting game auto increment...<br /><br />";

	// get max id from replays
	$max = $db->Query("SELECT MAX(`GameID`) as `max` FROM `replays`");
	if ($max === false || empty($max[0]['max'])) exit('Failed to retrieve max id from replays from DB.');
	$ai = $max[0]['max'] + 1;

	// set auto increment
	$result = $db->Query("ALTER TABLE `games` AUTO_INCREMENT = " . $ai . "");
	if ($result === false) exit('Failed to initialize game auto increment.');

	echo "<br /><br />Game auto-increment set to " . $ai;
?>
