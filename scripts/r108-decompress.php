<?php
	require_once('CDatabase.php');

	$db = new CDatabase("localhost", "arcomage", "", "arcomage");

	// begin operation
	$db->Query("SET AUTOCOMMIT=0");

	$db->Query("START TRANSACTION");

	// cache `decks` table
	$result = $db->Query("SELECT `Username`, `Deckname`, `Ready`, `Data` FROM `decks`");

	$db->Query("ALTER TABLE `decks` MODIFY `Data` text COLLATE utf8_unicode_ci NOT NULL");

	while ($res = $result->Next())
	{
		$db->Query("UPDATE `decks` SET `Data` = '".$db->Escape(gzuncompress($res['Data']))."' WHERE `Username` = '".$db->Escape($res['Username'])."' AND `Deckname` = '".$db->Escape($res['Deckname'])."'");
	}

	// cache `games` table
	$result = $db->Query("SELECT `GameID`, `Player1`, `Player2`, `State`, `Data` FROM `games`");

	$db->Query("ALTER TABLE `games` MODIFY `Data` text COLLATE utf8_unicode_ci NOT NULL");

	while ($res = $result->Next())
	{
		$db->Query("UPDATE `games` SET `Data` = '".$db->Escape(gzuncompress($res['Data']))."' WHERE `GameID` = '".$res['GameID']."'");
	}

	$db->Query("COMMIT");
?>