<?php

	require_once('CDatabase.php');
	require_once('CLogin.php');
	require_once('CScore.php');
	require_once('CCard.php');
	require_once('CDeck.php');
	require_once('CGame.php');
	require_once('CChat.php');
	require_once('CPlayer.php');
	
	$db = new CDatabase("localhost", "arcomage", "", "arcomage");
	
	$cards = new CCards($db);
	$players = new CPlayers($db);
	$chats = new CChats($db);
	
	echo "Correcting game data...";

	$result = $db->Query("SELECT `GameID` FROM `games` ORDER BY `GameID`");

	while( $data = $result->Next() )
	{
		$game = $players->Games->GetGame($data['GameID']);
		$p1 = &$game->GameData->Player[$game->Name1()]->Deck;
		$p2 = &$game->GameData->Player[$game->Name2()]->Deck;

		foreach( array("p1","p2") as $player )
		foreach( array("Common","Uncommon","Rare") as $rarity )
		{
			$a = &$$player->$rarity;
	
			// corruption: [0] nonzero, [3] missing or zero
			if( isset($a[0]) and (!isset($a[3]) or $a[3] == 0) )
			{
				$a[3] = $a[0];
				unset($a[0]);
				ksort($a);
			}

			// corruption: [0] nonzero, [12] missing or zero
			if( isset($a[0]) and (!isset($a[12]) or $a[12] == 0) )
			{
				$a[12] = $a[0];
				unset($a[0]);
				ksort($a);
			}

		}

		$game->SaveGame();
	};

	echo "Done.";
?>
