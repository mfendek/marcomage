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
	
	$decks = new CDecks($db);
	
	echo "Correcting deck data...\n";

	$result = $db->Query("SELECT `Username`, `Deckname` FROM `decks` ORDER BY `Username`");

	while( $data = $result->Next() )
	{
		$deck = $decks->GetDeck($data['Username'], $data['Deckname']);

		// corruption: duplicit card ids
		// solution: replace extra occurences of the card with '0'


		// first, check for corruption and only print messages
		$found = false;
		foreach( array("Common","Uncommon","Rare") as $rarity )
		{
			$a = &$deck->DeckData->$rarity;
			foreach( $a as $key => $value )
			{
				$arr = array_keys($a, $value);
				if( $value != 0 and count($arr) >= 2 )
				{
					echo $data['Username']." ".$data['Deckname']." ".$rarity." ".$key." ".$value."\n";
					$found = true;
				}
			}
		}

		if( !$found )
			continue;

		// now correct the corruption and print as much as possible
		foreach( array("Common","Uncommon","Rare") as $rarity )
		{
			$a = &$deck->DeckData->$rarity;

			// print before
			echo "- $rarity :";
			foreach( $a as $value )
				echo " $value";
			echo "\n";
			
			// remove duplicates
			foreach( $a as $key => $value )
			{
				$arr = array_keys($a, $value);
				if( $value != 0 and count($arr) >= 2 )
					for( $i = 1; $i < count($arr); $i++ )
						$a[$arr[$i]] = 0;
			}

			// print after
			echo "+ $rarity :";
			foreach( $a as $value )
				echo " $value";
			echo "\n";
		}


		$deck->SaveDeck();
	};

	echo "Done.";
?>
