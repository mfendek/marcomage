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
			$result = $db->Query('INSERT INTO `decks` (`Username`, `Deckname`, `Ready`, `Data`) VALUES ("'.$db->Escape($username).'", "'.$db->Escape($deckname).'", 0, "'.$db->Escape(serialize($deck->DeckData)).'")');
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
			$result = $db->Query('SELECT `Deckname` FROM `decks` WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Deckname'];
			return $names;
		}
		
		public function ListReadyDecks($username)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Deckname` FROM `decks` WHERE `Username` = "'.$db->Escape($username).'" AND `Ready` = 1');
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
			$result = $db->Query('UPDATE `decks` SET `Ready` = '.($this->isReady() ? '1' : '0').', `Data` = "'.$db->Escape(serialize($this->DeckData)).'"  WHERE `Username` = "'.$db->Escape($this->Username).'" AND `Deckname` = "'.$db->Escape($this->Deckname).'"');
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
		
		public function isReady()
		{
			return (($this->DeckData->Count('Common') == 15) && ($this->DeckData->Count('Uncommon') == 15) && ($this->DeckData->Count('Rare') == 15));
		}
		
		public function CalculateKeywords() // find token keywords most present in the deck
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
			
			return $new_tokens;
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
