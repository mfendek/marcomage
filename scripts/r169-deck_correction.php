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
	
	echo "Correcting deck data...";

	$result = $db->Query("SELECT `Username`, `Deckname` FROM `decks` ORDER BY `Username`");

	while( $data = $result->Next() )
	{
		$deck = $players->Decks->GetDeck($data['Username'], $data['Deckname']);

		// corruption: [0] exists, [12] missing or zero
		// solution: write [0] -> [12], then discard [0]

		foreach( array("Common","Uncommon","Rare") as $rarity )
		{
			$a = &$deck->DeckData->$rarity;
			if( isset($a[0]) and (!isset($a[12]) or $a[12] == 0) )
			{
				$a[12] = $a[0];
				unset($a[0]);
				ksort($a);
			}
		}

		$deck->SaveDeck();
	};

	echo "Done.";
?>
