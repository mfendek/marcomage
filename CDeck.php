<?php
/*
	CDeck - the representation of a player's deck
*/
?>
<?php
	class CDecks
	{
		private $db;
		
		public function __construct(CDatabase &$database)
		{
			$this->db = &$database;
		}
		
		public function GetDB()
		{
			return $this->db;
		}
		
		public function CreateDeck($username, $deckname)
		{
			$deck = new CDeck($username, $deckname, $this);
			
			$db = $this->db;
			$result = $db->Query('INSERT INTO `decks` (`Username`, `Deckname`, `Data`) VALUES ("'.$db->Escape($username).'", "'.$db->Escape($deckname).'", "'.$db->Escape(serialize($deck->DeckData)).'")');
			if (!$result) return false;
			
			return $deck;
		}
		
		public function DeleteDeck($username, $deckname)
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `decks` WHERE `Username` = "'.$db->Escape($username).'" AND `Deckname` = "'.$db->Escape($deckname).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function GetDeck($username, $deckname)
		{
			$db = $this->db;
			$result = $db->Query('SELECT 1 FROM `decks` WHERE `Username` = "'.$db->Escape($username).'" AND `Deckname` = "'.$db->Escape($deckname).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$deck = new CDeck($username, $deckname, $this);
			$deck->LoadDeck();
			
			return $deck;
		}
		
		public function ListDecks($username)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Deckname`, `Modified`, (CASE WHEN `Ready` = TRUE THEN "yes" ELSE "no" END) as `Ready` FROM `decks` WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) return false;
			
			$decks = array();
			while( $data = $result->Next() )
				$decks[] = $data;
			return $decks;
		}
		
		public function ListReadyDecks($username)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Deckname` FROM `decks` WHERE `Username` = "'.$db->Escape($username).'" AND `Ready` = TRUE');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Deckname'];
			return $names;
		}
	}
	
	
	class CDeck
	{
		private $Username;
		private $Deckname;
		private $Decks;
		public $DeckData;
		
		public function __construct($username, $deckname, CDecks &$Decks)
		{
			$this->Username = $username;
			$this->Deckname = $deckname;
			$this->Decks = &$Decks;
			$this->DeckData = new CDeckData;
		}
		
		public function __destruct()
		{
			$this->Username = '';
			$this->Deckname = '';
			$this->Decks = false;
			$this->DeckData = false;
		}
		
		public function Username()
		{
			return $this->Username;
		}
		
		public function Deckname()
		{
			return $this->Deckname;
		}
		
		public function LoadDeck()
		{
			$db = $this->Decks->getDB();
			$result = $db->Query('SELECT `Data` FROM `decks` WHERE `Username` = "'.$db->Escape($this->Username).'" AND `Deckname` = "'.$db->Escape($this->Deckname).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$this->DeckData = unserialize($data['Data']);
			
			return true;
		}
		
		public function SaveDeck()
		{
			$db = $this->Decks->getDB();
			$result = $db->Query('UPDATE `decks` SET `Ready` = '.($this->isReady() ? 'TRUE' : 'FALSE').', `Data` = "'.$db->Escape(serialize($this->DeckData)).'" WHERE `Username` = "'.$db->Escape($this->Username).'" AND `Deckname` = "'.$db->Escape($this->Deckname).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function RenameDeck($newdeckname)
		{
			$db = $this->Decks->getDB();
			$result = $db->Query('UPDATE `decks` SET `Deckname` = "'.$db->Escape($newdeckname).'" WHERE `Username` = "'.$db->Escape($this->Username).'" AND `Deckname` = "'.$db->Escape($this->Deckname).'"');
			if (!$result) return false;
			
			$this->Deckname = $newdeckname;
			
			return true;
		}

		/**
		 * Removes all cards and resets tokens.
		 * Zeroes out all three class arrays and sets the token options to 'none'.
		 * @return bool true if the operation succeeds, false if it fails
		*/
		public function ResetDeck()
		{
			$this->DeckData->Common   = array(1=> 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			$this->DeckData->Uncommon = array(1=> 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			$this->DeckData->Rare     = array(1=> 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			$this->DeckData->Tokens   = array(1=> 'none', 'none', 'none');
			
			return true;
		}
		
		/**
		 * Adds the card to an appropriate empty slot in the deck.
		 * Looks up the specified card's data and attempts to insert the card into an empty slot in $DeckData's corresponding array.
		 * Will fail if the operation is invalid (bad card id, no room in deck, card already present, etc).
		 * @param int $cardid the id of the card to insert
		 * @return bool true if the operation succeeds, false if it fails
		*/
		public function AddCard($cardid)
		{
			global $carddb;

			// retrieve the card's data
			$card = $carddb->GetCard($cardid);
			$class = $card->GetClass();

			// verify if the card id is valid
			if( $cardid == 0 or $class == 'None' )
				return false;

			// check if the card isn't already in the deck
			$pos = array_search($cardid, $this->DeckData->$class);
			if( $pos !== false )
				return false;

			// check if the deck's corresponding section isn't already full
			if( $this->DeckData->Count($class) == 15 )
				return false;

			// success
			// find an empty spot in the section
			$pos = array_search(0, $this->DeckData->$class);
			if( $pos === false )
				return false; // should never happen!

			// record the new card
			$this->DeckData->{$class}[$pos] = $cardid;

			return true;
		}
		
		/**
		 * Removes the card from the deck.
		 * Looks up the specified card's data and attempts to remove the card from $DeckData's corresponding array.
		 * Will fail if the card is not found in the deck.
		 * @param int $cardid the id of the card to remove
		 * @return bool true if the operation succeeds, false if it fails
		*/
		public function ReturnCard($cardid)
		{
			global $carddb;

			// retrieve the card's data
			$card = $carddb->GetCard($cardid);
			$class = $card->GetClass();
			
			// check if the card is present in the deck
			$pos = array_search($cardid, $this->DeckData->$class);
			if( $pos === false )
				return false;
			
			// success
			// remove the card from the deck
			$this->DeckData->{$class}[$pos] = 0;

			return true;
		}
		
		public function isReady()
		{
			return (($this->DeckData->Count('Common') == 15) && ($this->DeckData->Count('Uncommon') == 15) && ($this->DeckData->Count('Rare') == 15));
		}
		
		public function SetAutoTokens() // find and set token keywords most present in the deck
		{
			global $carddb;
			
			$tokens = count($this->DeckData->Tokens);
			
			// initialize token keyword counter array
			$token_keywords = $carddb->TokenKeywords();
			$token_values = array_fill(0, count($token_keywords), 0);
			
			$distict_keywords = array_combine($token_keywords, $token_values);
			
			// count token keywords
			foreach (array('Common', 'Uncommon', 'Rare') as $rarity)
				foreach ($this->DeckData->$rarity as $card_id)
					if ($card_id > 0)
					{
						$keywords = $carddb->GetCard($card_id)->GetKeywords();
						$words = preg_split("/\. ?/", $keywords, -1, PREG_SPLIT_NO_EMPTY);
						
						foreach($words as $word)
						{
							$word = preg_split("/ \(/", $word, 0); // remove parameter if present
							$word = $word[0];
							
							if (in_array($word, $token_keywords)) $distict_keywords[$word]++;
						}
					}
			
			// get most present token keywords
			arsort($distict_keywords);
			
			$distict_keywords = array_diff($distict_keywords, array(0)); // remove keywords with zero presence
			
			$new_tokens = array_keys(array_slice($distict_keywords, 0, $tokens));
			
			// add empty tokens when there are not enough token keywords
			if (count($new_tokens) < $tokens) $new_tokens = array_pad($new_tokens, $tokens, 'none');
			
			// adjust array keys
			$new_tokens = array_combine(array_keys(array_fill(1, count($new_tokens), 0)), $new_tokens);
			
			$this->DeckData->Tokens = $new_tokens;
			
			return "Success";
		}
		
		public function ToCSV()
		{
			$data = '';
			
			$data.= $this->Deckname."\n";
			$data.= implode(",", $this->DeckData->Common)."\n";
			$data.= implode(",", $this->DeckData->Uncommon)."\n";
			$data.= implode(",", $this->DeckData->Rare)."\n";
			$data.= implode(",", $this->DeckData->Tokens)."\n";
			
			return $data;
		}
		
		public function FromCSV($file)
		{
			global $carddb;
			
			// load data
			$lines = explode("\n", $file);
			
			$newname = trim($lines[0]);
			$deck_cards = array();
			$deck_cards['Common'] = explode(",", $lines[1]);
			$deck_cards['Uncommon'] = explode(",", $lines[2]);
			$deck_cards['Rare'] = explode(",", $lines[3]);
			$tokens = explode(",", $lines[4]);
			
			// check deckname
			if (strlen($newname) > 20) return "Deck name is too long.";
			
			// check if the deckname can be used (will not violate deck name uniqueness)
			$list = $this->Decks->ListDecks($this->Username);
			$pos = array_search($newname, $list);
			if (($this->Deckname != $newname) AND ($pos !== false)) return 'Cannot change deck name, it is already used by another deck.';
			if (trim($newname) == '') return 'Cannot change deck name, invalid input.';
			
			// check deck cards
			foreach ($deck_cards as $rarity => $card_ids)
			{
				if (count($card_ids) != 15) return $rarity." cards data is corrupted.";
				
				$cards = array_diff($card_ids, array(0)); // remove empty slots
				
				// check for duplicates
				if (count($cards) != count(array_unique($cards))) return $rarity." cards data contains duplicates.";
				
				// check ids
				$all_cards = $carddb->GetList(array('class' => $rarity));
				if (count(array_diff($cards, $all_cards)) > 0) return $rarity." cards data contians non ".$rarity." cards.";
			}
			
			// check tokens
			if (count($tokens) != 3) return "Token data is corrupted.";
			
			// check for duplicates
			$non_empty = array_diff($tokens, array("none")); // remove empty tokens
			
			if (count($non_empty) != count(array_unique($non_empty))) return " Token data contains duplicates.";
			
			// check token names
			$all_tokens = array_merge($carddb->TokenKeywords(), array("none"));
			
			if (count(array_diff($tokens, $all_tokens)) > 0) return "Token data contains non token keywords.";
			
			// import verfied data
			
			$this->RenameDeck($newname);
			
			// adjust key numbering
			$card_keys = array_keys(array_fill(1, 15, 0));
			
			$this->DeckData->Common = array_combine($card_keys, $deck_cards['Common']);
			$this->DeckData->Uncommon = array_combine($card_keys, $deck_cards['Uncommon']);
			$this->DeckData->Rare = array_combine($card_keys, $deck_cards['Rare']);
			$this->DeckData->Tokens = array_combine(array(1, 2, 3), $tokens);
			
			return "Success";
		}
		
	}
	
	
	class CDeckData
	{
		public $Common;
		public $Uncommon;
		public $Rare;
		public $Tokens;
		
		public function __construct()
		{
			$this->Common = array(1=>0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			$this->Uncommon = array(1=>0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			$this->Rare = array(1=>0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
			$this->Tokens = array(1 => 'none', 'none', 'none');
		}
		
		public function Count($class)
		{
			if (($class != 'Common') && ($class != 'Uncommon') && ($class != 'Rare')) return -1;
			
			$n = 0;
			foreach ($this->$class as $val)
				if ($val != 0) $n++;
			
			return $n;
		}
	}
?>
