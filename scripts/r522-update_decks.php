<?php

	require_once('../CDatabase.php');
	require_once('../CDeck.php');
	
	$db = new CDatabase("localhost", "arcomage", "", "arcomage");
	
	$decks = new CDecks($db);
	
	echo "Updating deck data...\n";

	$result = $db->Query("SELECT `Username`, `Deckname` FROM `decks` ORDER BY `Username`");

	while( $data = $result->Next() )
	{
		$deck = $decks->GetDeck($data['Username'], $data['Deckname']);

		$deck->DeckData->Tokens = array(1 => 'none', 'none', 'none');

		$deck->SaveDeck();
	};

	echo "Done.";
?>
