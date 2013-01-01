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
			$db = $this->db;
			
			$deck_data = new CDeckData;
			
			$result = $db->Query('INSERT INTO `decks` (`Username`, `Deckname`, `Data`) VALUES (?, ?, ?)', array($username, $deckname, serialize($deck_data)));
			if ($result === false) return false;
			
			$deck = new CDeck($db->LastID(), $username, $deckname, $this);
			
			return $deck;
		}
		
		public function DeleteDeck($username, $deck_id)
		{
			$db = $this->db;

			$result = $db->Query('DELETE FROM `decks` WHERE `Username` = ? AND `DeckID` = ?', array($username, $deck_id));
			if ($result === false) return false;
			
			return true;
		}
		
		public function GetDeck($username, $deck_id)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Deckname` FROM `decks` WHERE `Username` = ? AND `DeckID` = ?', array($username, $deck_id));
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			$deckname = $data['Deckname'];
			
			$deck = new CDeck($deck_id, $username, $deckname, $this);
			$result = $deck->LoadDeck();
			if (!$result) return false;
			
			return $deck;
		}
		
		public function ListDecks($username)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `DeckID`, `Deckname`, `Modified`, (CASE WHEN `Ready` = TRUE THEN "yes" ELSE "no" END) as `Ready`, `Wins`, `Losses`, `Draws` FROM `decks` WHERE `Username` = ?', array($username));
			if ($result === false) return false;

			return $result;
		}
		
		public function ListReadyDecks($username)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `DeckID`, `Deckname` FROM `decks` WHERE `Username` = ? AND `Ready` = TRUE', array($username));
			if ($result === false) return false;

			return $result;
		}
		
		public function UpdateStatistics($player1, $player2, $deck_id1, $deck_id2, $winner)
		{
			// update player 1 deck statistics
			$deck1 = $this->GetDeck($player1, $deck_id1);
			if ($deck1)
			{
				if ($winner == $player1) $deck1->Wins++;
				elseif ($winner == $player2) $deck1->Losses++;
				else $deck1->Draws++;
				$deck1->UpdateStats();
			}

			// update player 2 deck statistics
			$deck2 = $this->GetDeck($player2, $deck_id2);
			if ($deck2)
			{
				if ($winner == $player2) $deck2->Wins++;
				elseif ($winner == $player1) $deck2->Losses++;
				else $deck2->Draws++;
				$deck2->UpdateStats();
			}
		}

		public function StarterDecks()
		{
			$starter_decks = $starter_data = array();

			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>54, 240, 71, 256, 250, 259, 261, 113, 247, 79, 57, 140, 7, 236, 387);
			$deck_data->Uncommon = array(1=>28, 189, 83, 10, 204, 211, 230, 36, 216, 201, 53, 96, 146, 164, 208);
			$deck_data->Rare = array(1=>32, 197, 75, 74, 151, 61, 69, 66, 232, 506, 291, 21, 126, 542, 181);
			$starter_data[1] = $deck_data;

			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>1, 289, 23, 149, 359, 18, 260, 119, 26, 275, 271, 176, 60, 122, 272);
			$deck_data->Uncommon = array(1=>146, 163, 162, 164, 175, 266, 5, 154, 49, 136, 109, 35, 174, 270, 89);
			$deck_data->Rare = array(1=>235, 21, 124, 663, 161, 192, 4, 167, 233, 156, 67, 70, 169, 141, 148);
			$starter_data[2] = $deck_data;

			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>454, 45, 1, 260, 79, 238, 140, 46, 274, 269, 160, 362, 26, 300, 91);
			$deck_data->Uncommon = array(1=>29, 267, 84, 19, 47, 191, 320, 123, 98, 3, 8, 58, 109, 96, 52);
			$deck_data->Rare = array(1=>115, 108, 127, 86, 110, 138, 181, 242, 121, 249, 4, 277, 293, 199, 128);
			$starter_data[3] = $deck_data;

			foreach ($starter_data as $i => $deck_data)
			{
				$deck_name = 'deck '.$i;
				$curent_deck = new CDeck(0, SYSTEM_NAME, $deck_name, $this);
				$curent_deck->LoadData($deck_data);

				$starter_decks[$deck_name] = $curent_deck;
			}

			return $starter_decks;
		}

		public function ChallengeDecks()
		{
			$challenge_decks = $challenge_data = array();

			// Grofgul's deck
			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>71,619,71,586,584,60,258,619,608,237,188,60,624,556,119);
			$deck_data->Uncommon = array(1=>617,405,375,123,154,388,618,146,432,388,118,617,174,617,388);
			$deck_data->Rare = array(1=>407,377,407,377,546,546,377,389,389,199,389,407,199,199,546);
			$deck_data->Tokens[1] = "Alliance";
			$challenge_data['Grofgul'] = $deck_data;

			// Marquis' deck
			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>608,445,481,484,538,402,570,114,112,578,578,578,445,484,538);
			$deck_data->Uncommon = array(1=>543,576,120,133,230,186,321,296,28,446,543,576,543,576,120);
			$deck_data->Rare = array(1=>612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612, 612);
			$deck_data->Tokens[1] = "Undead";
			$challenge_data['Marquis'] = $deck_data;

			// Demetrios' deck
			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>387,301,79,91,570,559,521,524,149,575,289,501,606,559,454);
			$deck_data->Uncommon = array(1=>14,3,84,37,29,497,162,175,560,35,523,505,525,525,523);
			$deck_data->Rare = array(1=>161,229,285,222,530,285,530,161,229,285,222,530,222,530,161);
			$challenge_data['Demetrios'] = $deck_data;

			// Sophie's deck
			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>417,18,278,275,395,490,537,582,586,587,582,587,537,537,278);
			$deck_data->Uncommon = array(1=>231,155,5,200,492,87,137,386,72,72,155,492,5,231,137);
			$deck_data->Rare = array(1=>295,499,143,517,64,401,67,294,148,21,209,231,143,517,499);
			$challenge_data['Sophie'] = $deck_data;

			// Myr's deck
			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>589,589,589,82,82,82,357,357,153,153,153,597,597,608,586);
			$deck_data->Uncommon = array(1=>172,172,109,109,53,53,53,425,425,564,338,338,11,174,155);
			$deck_data->Rare = array(1=>467,467,467,121,121,121,9,9,499,596,577,577,435,435,173);
			$deck_data->Tokens[1] = "Mage";
			$challenge_data['Myr'] = $deck_data;

			// Gilgamesh's deck
			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>664,642,69,32,75,66,644,74,401,456,192,643,21,663,339);
			$deck_data->Uncommon = array(1=>435,277,70,616,506,450,614,181,427,622,61,110,181,232,464);
			$deck_data->Rare = array(1=>654,125,235,426,615,138,637,239,4,611,234,233,428,9,141);
			$challenge_data['Gilgamesh'] = $deck_data;

			// Duroth's deck
			$deck_data = new CDeckData;
			$deck_data->Common = array(1=>250,250,250,250,440,440,645,645,645,645,628,628,628,628,628);
			$deck_data->Uncommon = array(1=>195,195,195,701,701,701,679,679,679,622,622,622,670,670,131);
			$deck_data->Rare = array(1=>615,637,127,86,167,61,239,126,665,128,506,428,577,6,92);
			$deck_data->Tokens[1] = "Alliance";
			$challenge_data['Duroth'] = $deck_data;

			foreach ($challenge_data as $deck_name => $deck_data)
			{
				$curent_deck = new CDeck(0, SYSTEM_NAME, $deck_name, $this);
				$curent_deck->LoadData($deck_data);

				$challenge_decks[$deck_name] = $curent_deck;
			}

			return $challenge_decks;
		}
	}
	
	
	class CDeck
	{
		private $DeckID;
		private $Username;
		private $Deckname;
		private $Decks;
		public $DeckData;
		public $Wins;
		public $Losses;
		public $Draws;
		
		public function __construct($deck_id, $username, $deckname, CDecks &$Decks)
		{
			$this->DeckID = $deck_id;
			$this->Username = $username;
			$this->Deckname = $deckname;
			$this->Decks = &$Decks;
			$this->DeckData = new CDeckData;
			$this->Wins = 0;
			$this->Losses = 0;
			$this->Draws = 0;
		}
		
		public function __destruct()
		{
			$this->DeckID = '';
			$this->Username = '';
			$this->Deckname = '';
			$this->Decks = false;
			$this->DeckData = false;
		}
		
		public function ID()
		{
			return $this->DeckID;
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

			$result = $db->Query('SELECT `Data`, `Wins`, `Losses`, `Draws` FROM `decks` WHERE `Username` = ? AND `DeckID` = ?', array($this->Username, $this->DeckID));
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			$this->DeckData = unserialize($data['Data']);
			$this->Wins = $data['Wins'];
			$this->Losses = $data['Losses'];
			$this->Draws = $data['Draws'];
			
			return true;
		}
		
		public function SaveDeck()
		{
			$db = $this->Decks->getDB();

			$result = $db->Query('UPDATE `decks` SET `Ready` = ?, `Data` = ?, `Wins` = ?, `Losses` = ?, `Draws` = ?, `Modified` = NOW() WHERE `Username` = ? AND `DeckID` = ?', array((($this->isReady()) ? 1 : 0), serialize($this->DeckData), $this->Wins, $this->Losses, $this->Draws, $this->Username, $this->DeckID));
			if ($result === false) return false;
			
			return true;
		}
		
		public function UpdateStats()
		{
			$db = $this->Decks->getDB();

			$result = $db->Query('UPDATE `decks` SET `Wins` = ?, `Losses` = ?, `Draws` = ? WHERE `Username` = ? AND `DeckID` = ?', array($this->Wins, $this->Losses, $this->Draws, $this->Username, $this->DeckID));
			if ($result === false) return false;
			
			return true;
		}
		
		public function RenameDeck($newdeckname)
		{
			$db = $this->Decks->getDB();

			$result = $db->Query('UPDATE `decks` SET `Deckname` = ?, `Modified` = NOW()  WHERE `Username` = ? AND `DeckID` = ?', array($newdeckname, $this->Username, $this->DeckID));
			if ($result === false) return false;
			
			$this->Deckname = $newdeckname;
			
			return true;
		}
		
		public function ResetStatistics()
		{
			$this->Wins = 0;
			$this->Losses = 0;
			$this->Draws = 0;
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
			$class = $card->Class;

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
			$this->DeckData->{$class}[$pos] = (int)$cardid;

			$classes = array('Common' => 0, 'Uncommon' => 1, 'Rare' => 2);

			return ($pos + 15 * $classes[$class]); // return slot number that was used to store newly added card (slot range 1 - 45)
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
			$class = $card->Class;
			
			// check if the card is present in the deck
			$pos = array_search($cardid, $this->DeckData->$class);
			if( $pos === false )
				return false;
			
			// success
			// remove the card from the deck
			$this->DeckData->{$class}[$pos] = 0;

			$classes = array('Common' => 0, 'Uncommon' => 1, 'Rare' => 2);

			return ($pos + 15 * $classes[$class]); // return slot number that became vacant
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
						$keywords = $carddb->GetCard($card_id)->Keywords;
						$words = explode(",", $keywords);
						
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

		public function AvgCostPerTurn() // calculate average cost per turn
		{
			global $carddb;

			// define a data structure for our needs
			$sub_array = array('Common' => 0, 'Uncommon' => 0, 'Rare' => 0);

			$sum = array('Bricks' => $sub_array ,'Gems' => $sub_array, 'Recruits' => $sub_array, 'Count' => $sub_array);
			$avg = array('Bricks' => $sub_array ,'Gems' => $sub_array, 'Recruits' => $sub_array);
			$res = array('Bricks' => 0 ,'Gems' => 0, 'Recruits' => 0);

			foreach ($sub_array as $class => $value)
			{
				foreach ($this->DeckData->$class as $index => $cardid)
				{
					if ($cardid != 0)
					{
						$card = $carddb->GetCard($cardid);
						$sum['Bricks'][$class]+= $card->Bricks;
						$sum['Gems'][$class]+= $card->Gems;
						$sum['Recruits'][$class]+= $card->Recruits;
						$sum['Count'][$class]+= 1;
					}
				}
			}

			foreach ($avg as $type => $value)
			{
				if ($sum['Count']['Common'] == 0) $avg[$type]['Common'] = 0;
				else $avg[$type]['Common'] = ($sum[$type]['Common'] * 0.65)/$sum['Count']['Common'];

				if ($sum['Count']['Uncommon'] == 0) $avg[$type]['Uncommon'] = 0;
				else $avg[$type]['Uncommon'] = ($sum[$type]['Uncommon'] * 0.29)/$sum['Count']['Uncommon'];

				if ($sum['Count']['Rare'] == 0) $avg[$type]['Rare'] = 0;
				else $avg[$type]['Rare'] = (($sum[$type]['Rare'] * 0.06)/$sum['Count']['Rare']);
			}

			foreach ($avg as $type => $value) $res[$type] = round($avg[$type]['Common'] + $avg[$type]['Uncommon'] + $avg[$type]['Rare'], 2);

			return $res;
		}

		public function FromCSV($file, $level)
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
				$all_cards = $carddb->GetList(array('class' => $rarity, 'level' => $level, 'level_op' => '<='));
				if (count(array_diff($cards, $all_cards)) > 0) return $rarity." cards data contains forbidden cards.";
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
		
		public function LoadData(CDeckData $deck_data)
		{
			$this->DeckData = $deck_data;
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
