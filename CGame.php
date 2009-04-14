<?php
/*
	CGame - representation of a game between two players
*/
?>
<?php
	class CGames
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
		
		public function CreateGame($player1, $player2, $deck1)
		{
			$db = $this->db;
			$result = $db->Query('SELECT IFNULL(MAX(`GameID`)+1, 1) as `max` FROM `games`');
			$data = $result->Next();
			$gameid = (int)$data['max'];
			
			$game = new CGame($gameid, $player1, $player2, $this);
			$game->GameData->Player[$player1]->Deck = $deck1;
			$game->GameData->Player[$player2]->Deck = 0;
			
			$result = $db->Query('INSERT INTO `games` (`GameID`, `Player1`, `Player2`, `State`, `Data`) VALUES ("'.$game->ID().'", "'.$db->Escape($game->Name1()).'", "'.$db->Escape($game->Name2()).'", "'.$db->Escape($game->State).'", "'.$db->Escape(serialize($game->GameData)).'")');
			if (!$result) return false;
			
			return $game;
		}
		
		public function DeleteGame($gameid)
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `games` WHERE `GameID` = '.$db->Escape($gameid));
			if (!$result) return false;
			
			return true;
		}

		public function DeleteGame2($player1, $player2)
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `games` WHERE `Player1` = "'.$db->Escape($player1).'" AND `Player2` = "'.$db->Escape($player2).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function GetGame($gameid)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `games` WHERE `GameID` = '.$db->Escape($gameid));
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$players = $result->Next();
			$player1 = $players['Player1'];
			$player2 = $players['Player2'];
			
			$game = new CGame($gameid, $player1, $player2, $this);
			$game->LoadGame();
			
			return $game;
		}

		public function GetGame2($player1, $player2)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `GameID` FROM `games` WHERE `Player1` = "'.$db->Escape($player1).'" AND `Player2` = "'.$db->Escape($player2).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$gameid = $data['GameID'];
			
			$game = new CGame($gameid, $player1, $player2, $this);
			$game->LoadGame();
			
			return $game;
		}
		
		public function ListChallengesFrom($player)
		{
			// $player is on the left side and $Status = "waiting"
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `games` WHERE `Player1` = "'.$db->Escape($player).'" AND `State` = "waiting"');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
		
		public function ListChallengesTo($player)
		{
			// $player is on the right side and $Status = "waiting"
			$db = $this->db;
			$result = $db->Query('SELECT `Player1` FROM `games` WHERE `Player2` = "'.$db->Escape($player).'" AND `State` = "waiting"');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
		
		public function ListActiveGames($player)
		{
			// $player is either on the left or right side and Status != 'waiting' or 'P? over'
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `games` WHERE (`Player1` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P2 over"))');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
		
		public function ListEndedGames($player)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `games` WHERE (`Player1` = "'.$db->Escape($player).'" AND `State` = "P1 over") OR (`Player2` = "'.$db->Escape($player).'" AND `State` = "P2 over")');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
	}
	
	
	class CGame
	{
		private $Games;
		private $GameID;
		private $Player1;
		private $Player2;
		public $State; // 'waiting' / 'in progress' / 'finished' / 'P1 over' / 'P2 over'
		public $GameData;
		
		public function __construct($gameid, $player1, $player2, CGames $Games)
		{
			$this->GameID = $gameid;
			$this->Player1 = $player1;
			$this->Player2 = $player2;
			$this->State = "waiting";
			$this->Games = &$Games;
			$this->GameData = new CGameData;
			$this->GameData->Player[$player1] = new CGamePlayerData;
			$this->GameData->Player[$player2] = new CGamePlayerData;
		}
		
		public function __destruct()
		{
		}
		
		public function ID()
		{
			return $this->GameID;
		}
		
		public function Name1()
		{
			return $this->Player1;
		}
		
		public function Name2()
		{
			return $this->Player2;
		}
		
		public function LoadGame()
		{
			$db = $this->Games->getDB();
			$result = $db->Query('SELECT `State`, `Data` FROM `games` WHERE `Player1` = "'.$db->Escape($this->Player1).'" AND `Player2` = "'.$db->Escape($this->Player2).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$this->State = $data['State'];
			$this->GameData = unserialize($data['Data']);
			
			return true;
		}
		
		public function SaveGame()
		{
			$db = $this->Games->getDB();
			$result = $db->Query('UPDATE `games` SET `State` = "'.$db->Escape($this->State).'", `Data` = "'.$db->Escape(serialize($this->GameData)).'" WHERE `Player1` = "'.$db->Escape($this->Player1).'" AND `Player2` = "'.$db->Escape($this->Player2).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function StartGame()
		{
			$this->State = 'in progress';
			
			$this->GameData->Current = ((mt_rand(0,1) == 1) ? $this->Player1 : $this->Player2);
			$this->GameData->Round = 1;
			$this->GameData->Winner = '';
			$this->GameData->Outcome = '';
			$this->GameData->Timestamp = time();
			
			$p1 = &$this->GameData->Player[$this->Player1];
			$p2 = &$this->GameData->Player[$this->Player2];
			
			$p1->LastCard[1] = $p2->LastCard[1] = 0;
			$p1->LastMode[1] = $p2->LastMode[1] = 0;
			$p1->LastAction[1] = $p2->LastAction[1] = 'play';
			$p1->NewCards = $p2->NewCards = null;
			$p1->DisCards[0] = $p1->DisCards[1] = $p2->DisCards[0] = $p2->DisCards[1] = null; //0 - cards discarded from my hand, 1 - discarded from opponents hand
			$p1->Changes = $p2->Changes = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
			$p1->Tower = $p2->Tower = 30;
			$p1->Wall = $p2->Wall = 20;
			$p1->Quarry = $p2->Quarry = 3;
			$p1->Magic = $p2->Magic = 3;
			$p1->Dungeons = $p2->Dungeons = 3;
			$p1->Bricks = $p2->Bricks = 15;
			$p1->Gems = $p2->Gems = 5;
			$p1->Recruits = $p2->Recruits = 10;
			
			// initialize tokens
			$p1->TokenNames = $p1->Deck->Tokens;
			$p2->TokenNames = $p2->Deck->Tokens;
			
			$p1->TokenValues = $p1->TokenChanges = array_fill_keys(array_keys($p1->TokenNames), 0);
			$p2->TokenValues = $p2->TokenChanges = array_fill_keys(array_keys($p2->TokenNames), 0);
			
			$p1->Hand = $this->DrawHand_initial($p1->Deck);
			$p2->Hand = $this->DrawHand_initial($p2->Deck);
		}
		
		public function SurrenderGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->GameData->Winner = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$this->GameData->Outcome = 'Opponent has surrendered';
			$this->SaveGame();
			
			return 'OK';
		}
		
		public function AbortGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->GameData->Winner = '';
			$this->GameData->Outcome = 'Aborted';
			$this->SaveGame();
			
			return 'OK';
		}
		
		public function FinishGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->GameData->Winner = ($this->Player1 == $playername) ? $this->Player1 : $this->Player2;
			$opponent = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$this->GameData->Outcome = $opponent.' has fled the battlefield';
			$this->SaveGame();
			
			return 'OK';
		}
		
		public function PlayCard($playername, $cardpos, $mode, $action)
		{
			global $carddb;
			
			$data = &$this->GameData;
			
			// only allow discarding if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			// only allow action when it's the players' turn
			if ($data->Current != $playername) return 'Action only allowed on your turn!';
			
			// anti-hack
			if (($cardpos < 1) || ($cardpos > 8)) return 'Wrong card position!';
			if (($action != 'play') && ($action != 'discard')) return 'Invalid action!';
			
			// prepare basic information
			$opponent = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$mydata = &$data->Player[$playername];
			$hisdata = &$data->Player[$opponent];
			
			// find out what card is at that position
			$cardid = $mydata->Hand[$cardpos];
			$card = $carddb->GetCard($cardid);
			
			// process card history
			$mylastcardindex = count($mydata->LastCard);
			$hislastcardindex = count($hisdata->LastCard);
			
			// prepare supplementary information
			$mylast_card = $carddb->GetCard($mydata->LastCard[$mylastcardindex]);
			$mylast_action = $mydata->LastAction[$mylastcardindex];
			$hislast_card = $carddb->GetCard($hisdata->LastCard[$hislastcardindex]);
			$hislast_action = $hisdata->LastAction[$hislastcardindex];
			
			//we need to store this information, because some cards will need it to make their effect, however after effect this information is not stored
			$mychanges = $mydata->Changes;
			$hischanges = $hisdata->Changes;
			$mynewflags = $mydata->NewCards;
			$hisnewflags = $hisdata->NewCards;
			$discarded_cards[0] = $mydata->DisCards[0];
			$discarded_cards[1] = $mydata->DisCards[1];
			
			// clear newcards flag, changes indicator and discarded cards here, if required
			if (!($mylast_card->IsPlayAgainCard() and $mylast_action == 'play'))
			{
				$mydata->NewCards = null;
				$mydata->Changes = $hisdata->Changes = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				$mydata->DisCards[0] = $mydata->DisCards[1] = null;
				$mydata->TokenChanges = $hisdata->TokenChanges = array_fill_keys(array_keys($mydata->TokenNames), 0);
			}
			
			// by default, opponent goes next (but this may change via card)
			$nextplayer = $opponent;
			
			// next card drawn will be decided randomly unless this changes
			$nextcard = -1;
			
			// default production factor
			$bricks_production = 1;
			$gems_production = 1;
			$recruits_production = 1;
			
			// branch here according to $action
			if ($action == 'play')
			{
				// verify mode (depends on card and on those two special cards)
				if( $mode < 0
				||  $mode > $card->CardData->Modes
				||  $mode == 0 && $card->CardData->Modes > 0
				||  $cardpos == $mode && ($cardid == 269 || $cardid == 298)
				  ) return 'Bad mode!';
				
				// verify if there are enough resources
				if (($mydata->Bricks < $card->CardData->Bricks) || ($mydata->Gems < $card->CardData->Gems) || ($mydata->Recruits < $card->CardData->Recruits)) return 'Insufficient resources!';
				
				$mydata->Bricks-= $card->CardData->Bricks;
				$mydata->Gems-= $card->CardData->Gems;
				$mydata->Recruits-= $card->CardData->Recruits;
				
				//create a copy of interesting game attributes
				$mydata_temp = $hisdata_temp = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				
				foreach ($mydata_temp as $attribute => $value)
				{
					$mydata_temp[$attribute] = $mydata->$attribute;
					$hisdata_temp[$attribute] = $hisdata->$attribute;
				}
				
				// create a copy of token counters
				$mytokens_temp = $mydata->TokenValues;
				$histokens_temp = $hisdata->TokenValues;
				
				//create a copy of both players' hands and newcards flags (for difference computations only)
				$myhand = $mydata->Hand;
				$hishand = $hisdata->Hand;
				$mynewcards = $mydata->NewCards;
				$hisnewcards = $hisdata->NewCards;
				
				switch ($cardid)
				{
					case   0: break;
					case   1: if ($mydata->Wall == 0) $mydata->Wall+= 10; else $mydata->Wall+= 3; break;
					case   2: if ($mydata->Tower < 15) $mydata->Tower+= 7; else $mydata->Tower+= 2; break;
					case   3: $hisdata->Quarry-= 1; break;
					case   4: $mydata->Wall+= 75; break;
					case   5: $mydata->Quarry+= 1; $mydata->Magic+= 1; $mydata->Dungeons+= 1; $hisdata->Quarry-= 1; $hisdata->Magic-= 1; $hisdata->Dungeons-= 1; break;
					case   6: $this->Attack(50, $hisdata->Tower, $hisdata->Wall); break;
					case   7: $this->Attack(7, $hisdata->Tower, $hisdata->Wall); break;
					case   8: $mydata->Gems+= 7; $hisdata->Gems-= 7; break;
					case   9: $mydata->Magic+= 2; $nextcard = $this->DrawCard($carddb->GetList("Rare", "Mage"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  10: $this->Attack(21, $hisdata->Tower, $hisdata->Wall); break;
					case  11: $mydata->Gems+= 25; if (($mylast_card->HasKeyword("Mage")) and ($mylast_action == 'play')) $mydata->Gems+= 8; break;
					case  12: $mydata->Dungeons+= 1; break;
					case  13: $this->Attack(3, $hisdata->Tower, $hisdata->Wall); break;
					case  14: $hisdata->Magic-= 1; break;
					case  15: $mydata->Quarry-= 1; $mydata->Magic-= 1; $mydata->Dungeons-= 1; $hisdata->Quarry-= 1; $hisdata->Magic-= 1; $hisdata->Dungeons-= 1; break;
					case  16: $mydata->Tower+= 3; $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; $nextcard = $this->DrawCard($carddb->GetList("", "Mage"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  17: $mydata->Tower-= 15; $mydata->Wall-= 30; $hisdata->Tower-= 15; $hisdata->Wall-= 30; break;
					case  18: $mydata->Tower+= 5; break;
					case  19: $mydata->Wall+= 21; break;
					case  20: $this->Attack(2, $hisdata->Tower, $hisdata->Wall); $hisdata->Gems-= 1; break;
					case  21: $mydata->Tower+= 30; break;
					case  22: $mydata->Quarry+= 1; break;
					case  23: $tmp = ( ($mychanges['Bricks'] < 0 or $mychanges['Gems'] < 0 or $mychanges['Recruits'] < 0) and !($mylast_card->IsPlayAgainCard() and $mylast_action == 'play') ) ? 2 : 1; $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; break;
					case  24: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); break;
					case  25: $this->Attack(14, $hisdata->Tower, $hisdata->Wall); break;
					case  26: $this->Attack(1, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; $hisdata->Bricks-= 1; $hisdata->Gems-= 1; $hisdata->Recruits-= 1; break;
					case  27: $tmp = $this->KeywordCount($mydata->Hand, "Holy"); $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; break;
					case  28: $this->Attack(29, $hisdata->Tower, $hisdata->Wall); $mydata->Gems-= 7; $mydata->Magic-= 1; break;
					case  29: $hisdata->Dungeons-= 1; break;
					case  30: $mydata->Dungeons-= 1; $mydata->Magic+= 1; $mydata->Recruits-= 10; $mydata->Gems+= 25; break;
					case  31: $tmp = $this->KeywordCount($hisdata->Hand, "Holy"); $tmp = min(4, $tmp); $this->Attack(22 + $tmp, $hisdata->Tower, $hisdata->Wall); $hisdata->Bricks-= $tmp; $hisdata->Gems-= $tmp; $hisdata->Recruits-= $tmp; break;
					case  32: $this->Attack(16, $hisdata->Tower, $hisdata->Wall); break;
					case  33: $mydata->Wall+=5; $nextcard = $this->DrawCard($carddb->GetList("Rare", "Beast"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  34: $mydata->Magic+= 1; break;
					case  35: $mydata->Tower+= 15; $mydata->Gems+= 5; break;
					case  36: $this->Attack(16, $hisdata->Tower, $hisdata->Wall); break;
					case  37: $hisdata->Gems-= 12; break;
					case  38: $tmp = $this->KeywordCount($mydata->Hand, "Brigand") + 4; $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; $hisdata->Bricks-= $tmp; $hisdata->Gems-= $tmp; $hisdata->Recruits-= $tmp; break;
					case  39: $mydata->Tower+= 2; $mydata->Wall+= 2; break;
					case  40: $hisdata->Recruits-= 20; break;
					case  41: if ($mode == 1) $mydata->Wall+=8 ; elseif ($mode == 2) $hisdata->Wall-= 9; break;
					case  42: $this->Attack(2, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits+= 1; break;
					case  43: $mydata->Magic-= 1; $mydata->Gems+= 25; break;
					case  44: $tmp = 3 * $this->KeywordCount($mydata->Hand, "Holy"); $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; break;
					case  45: $this->Attack(4, $hisdata->Tower, $hisdata->Wall); $hisdata->Recruits-= 1; break;
					case  46: $this->Attack(6, $hisdata->Tower, $hisdata->Wall); break;
					case  47: $mydata->Wall+= 31; break;
					case  48: $mydata->Tower+= 4; $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; $nextcard = $this->DrawCard($carddb->GetList("", "Restoration"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  49: $mydata->Bricks+= 10; if (($mylast_card->HasKeyword("Mage")) and ($mylast_action == 'play')) $mydata->Bricks+= 5; break;
					case  50: $mydata->Recruits+= 10; if (($mylast_card->HasKeyword("Mage")) and ($mylast_action == 'play')) $mydata->Recruits+= 5; break;
					case  51: $temp_array = array(); for ($i = 1; $i <= 8; $i++) if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("any")) $temp_array[] = $i; if( count($temp_array) > 0 ) { $position = $temp_array[array_rand($temp_array)]; $mydata->Bricks+= min($carddb->GetCard($hisdata->Hand[$position])->GetResources("Bricks"),5); $mydata->Gems+= min($carddb->GetCard($hisdata->Hand[$position])->GetResources("Gems"),5); $mydata->Recruits+= min($carddb->GetCard($hisdata->Hand[$position])->GetResources("Recruits"),5); $hisdata->Hand[$position] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $position, 'DrawCard_random'); $hisdata->NewCards[$position] = 1; }; break;
					case  52: $this->Attack(12, $hisdata->Tower, $hisdata->Wall); $hisdata->Bricks-= 3; $hisdata->Gems-= 3; $hisdata->Recruits-= 3; break;
					case  53: $this->Attack((6 + $mydata->Magic), $hisdata->Tower, $hisdata->Wall); break;
					case  54: $hisdata->Wall-= 11; break;
					case  55: $mydata->Tower+= 5; $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; $nextcard = $this->DrawCard($carddb->GetList("", "Nature"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  56: $mydata->Tower+= 10; break;
					case  57: $mydata->Wall+= 7; break;
					case  58: $mydata->Tower+= 6; $mydata->Bricks+= 2; $mydata->Gems+= 2; $mydata->Recruits+= 2; $nextcard = $this->DrawCard($carddb->GetList("", "Destruction"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  59: $hisdata->Tower-= 9; break;
					case  60: $this->Attack(4, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= 3; break;
					case  61: $this->Attack(50, $hisdata->Tower, $hisdata->Wall); break;
					case  62: $hisdata->Tower-= 15; break;
					case  63: $mydata->Quarry-= 1; $mydata->Magic-= 1; $mydata->Dungeons-= 1; $hisdata->Tower-= 24; break;
					case  64: $mydata->Tower+= 25; $mydata->Quarry+= 1; $mydata->Magic+= 1; $mydata->Dungeons+= 1; $hisdata->Tower+= 25; $hisdata->Quarry+= 1; $hisdata->Magic+= 1; $hisdata->Dungeons+= 1; break;
					case  65: $mydata->Tower+= 7; $mydata->Bricks+= 2; $mydata->Gems+= 2; $mydata->Recruits+= 2; $nextcard = $this->DrawCard($carddb->GetList("", "Illusion"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  66: $this->Attack(18, $hisdata->Tower, $hisdata->Wall); break;
					case  67: $mydata->Tower+= 40; break;
					case  68: $this->Attack(3, $hisdata->Tower, $hisdata->Wall);  $mydata->Recruits+= 2; $nextcard = $this->DrawCard(array_merge($carddb->GetList("Uncommon", "Barbarian"), $carddb->GetList("Rare", "Barbarian")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  69: $this->Attack(12, $hisdata->Tower, $hisdata->Wall); break;
					case  70: $mydata->Tower+= 20; $mydata->Magic+= 1; break;
					case  71: $this->Attack(3, $hisdata->Tower, $hisdata->Wall); $mydata->Wall+= 5; break;
					case  72: $mydata->Tower+= 10; break;
					case  73: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); $hisdata->Bricks-= 5; break;
					case  74: $this->Attack(25, $hisdata->Tower, $hisdata->Wall); break;
					case  75: $this->Attack(15, $hisdata->Tower, $hisdata->Wall); break;
					case  76: $temp_array = array("Bricks", "Gems", "Recruits"); $mydata->$temp_array[array_rand($temp_array)]-= 1; $hisdata->Tower-= 3; break;
					case  77: $this->Attack(6, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks-= 1; $mydata->Gems-= 1; $mydata->Recruits-= 1; break;
					case  78: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 3; break;
					case  79: $hisdata->Bricks-= 10; break;
					case  80: $my_fac = $mydata->Quarry + $mydata->Magic + $mydata->Dungeons; $his_fac = $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons; if (($my_fac) >= ($his_fac)) { $mydata->Quarry-= 2; $mydata->Magic-= 2; $mydata->Dungeons-= 2; } if (($my_fac) <= ($his_fac)) { $hisdata->Quarry-= 2; $hisdata->Magic-= 2; $hisdata->Dungeons-= 2; } break;
					case  81: $mydata->Quarry+= 2; $mydata->Magic+= 2; $mydata->Dungeons+= 2; $hisdata->Quarry+= 2; $hisdata->Magic+= 2; $hisdata->Dungeons+= 2; $mydata->Bricks= 0; $mydata->Gems= 0; $mydata->Recruits= 0; $hisdata->Bricks= 0; $hisdata->Gems= 0; $hisdata->Recruits= 0; break;
					case  82: $this->Attack($mydata->Magic, $hisdata->Tower, $hisdata->Wall); break;
					case  83: $this->Attack(15, $hisdata->Tower, $hisdata->Wall); $mydata->Dungeons+= 1; $hisdata->Dungeons+= 1; break;
					case  84: $this->Attack(8, $hisdata->Tower, $hisdata->Wall); $hisdata->Gems-= 8; break;
					case  85: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); $hisdata->Gems-= 8; break;
					case  86: $this->Attack(40, $hisdata->Tower, $hisdata->Wall); $hisdata->Gems-= 20; break;
					case  87: $mydata->Tower+= 80; break;
					case  88: $tmp = ($this->KeywordCount($mydata->Hand, "Undead") * 8) + 40; $this->Attack($tmp, $hisdata->Tower, $hisdata->Wall); break;
					case  89: $this->Attack(100, $hisdata->Tower, $hisdata->Wall); break;
					case  90: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 6; break;
					case  91: $hisdata->Recruits-= 6; break;
					case  92: if ($mode == 1) $this->Attack(50, $hisdata->Tower, $hisdata->Wall); elseif ($mode == 2) $mydata->Wall+= 60; elseif ($mode == 3) $hisdata->Wall-= 70; break;
					case  93: if ($mode == 1) $this->Attack(25, $hisdata->Tower, $hisdata->Wall); elseif ($mode == 2) $mydata->Wall+= 32; elseif ($mode == 3) $hisdata->Wall-= 35; break;
					case  94: if ($mode == 1) $this->Attack(12, $hisdata->Tower, $hisdata->Wall); elseif ($mode == 2) $hisdata->Tower-= 7; break;
					case  95: $hisdata->Tower-= 3; break;
					case  96: $this->Attack(1, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 3; break;
					case  97: $this->Attack(1, $hisdata->Tower, $hisdata->Wall); $last_card = $mydata->LastCard[$mylastcardindex]; if ((($carddb->GetCard($last_card)->GetResources("Bricks") + $carddb->GetCard($last_card)->GetResources("Gems") + $carddb->GetCard($last_card)->GetResources("Recruits")) == 0) and ($mylast_action == 'play')) $nextcard = $this->DrawCard(array_merge($carddb->GetList("Uncommon", "", "Zero"), $carddb->GetList("Rare", "", "Zero")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  98: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); $hisdata->Magic-= 1; break;
					case  99: $mydata->Dungeons+= 1; $mydata->Magic+= 1; $hisdata->Dungeons+= 1; $hisdata->Magic+= 1; $hisdata->Gems-= 15; $hisdata->Recruits-= 10; break;
					case 100: $mydata->Quarry+= 5; $hisdata->Quarry+= 5; break;
					case 101: $tempnum = $this->KeywordCount($mydata->Hand, "Undead"); $hisdata->Bricks-= $tempnum; $hisdata->Gems-= $tempnum; $hisdata->Recruits-= $tempnum; break;
					case 102: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits+= 20; break;
					case 103: $tempnum = $this->KeywordCount($mydata->Hand, "Undead"); $mydata->Bricks+= $tempnum; $mydata->Gems+= $tempnum; $mydata->Recruits+= $tempnum; if ($tempnum > 6) $mydata->Magic+= 1; break;
					case 104: $mydata->Wall+= 10; break;
					case 105: $nextcard = $mydata->LastCard[$mylastcardindex]; break;
					case 106: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); break;
					case 107: $mydata->Bricks+= 2; $mydata->Recruits+= 2; break;
					case 108: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); $hisdata->Bricks-= 7; $hisdata->Gems-= 7; $hisdata->Recruits-= 7; $nextcard = $mydata->LastCard[$mylastcardindex]; break;
					case 109: $this->Attack(8, $hisdata->Tower, $hisdata->Wall); $hisdata->Bricks-= 3; $hisdata->Gems-= 3; $hisdata->Recruits-= 3; break;
					case 110: $this->Attack(35, $hisdata->Tower, $hisdata->Wall); $hisdata->Recruits-= 10; break;
					case 111: $this->Attack(7, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 2; break;
					case 112: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); break;
					case 113: $this->Attack(7, $hisdata->Tower, $hisdata->Wall); break;
					case 114: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); break;
					case 115: $this->Attack(30, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks= 0; $mydata->Gems= 0; $mydata->Recruits= 0; $hisdata->Bricks= 0; $hisdata->Gems= 0; $hisdata->Recruits= 0; break;
					case 116: $mydata->Tower+= 5; $hisdata->Tower-= 5; break;
					case 117: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); $mydata->Gems-= 3; break;
					case 118: $mydata->Quarry+= 5; $mydata->Magic+= 5; $mydata->Dungeons+= 5; break;
					case 119: $bricks_production*= 2; $gems_production*= 2; $recruits_production*= 2; break;
					case 120: $this->Attack(11, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 5; $hisdata->Gems-= 5; break;
					case 121: $this->Attack(30, $hisdata->Tower, $hisdata->Wall); $mydata->Magic+= 1; break;
					case 122: $this->Attack(3, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 1; break;
					case 123: $mydata->Tower+= 7; $bricks_production*= 2; $gems_production*= 2; $recruits_production*= 2; break;
					case 124: $this->Attack(70, $hisdata->Tower, $hisdata->Wall); $mydata->Tower-= 30; $mydata->Wall-= 50; break;
					case 125: $mydata->Tower+= 15; $mydata->Wall+= 15; $mydata->Bricks+= 15; $mydata->Gems+= 15; $mydata->Recruits+= 15; break;
					case 126: $this->Attack(40, $hisdata->Tower, $hisdata->Wall); break;
					case 127: $this->Attack(60, $hisdata->Tower, $hisdata->Wall); $hisdata->Recruits-= 15; break;
					case 128: $this->Attack(45, $hisdata->Tower, $hisdata->Wall); $hisdata->Gems-= 10; break;
					case 129: $mydata->Bricks+= 7; $mydata->Gems+= 7; $mydata->Recruits+= 7; $hisdata->Bricks+= 10; $hisdata->Gems+= 10; $hisdata->Recruits+= 10; break;
					case 130: $this->Attack(30, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall <= 0) $mydata->Recruits+= 20; break;
					case 131: $nextcard = $this->DrawCard($carddb->GetList("", "Dragon"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 132: if (($mydata->Quarry + $mydata->Magic + $mydata->Dungeons) >= ($hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons)) { $mydata->Bricks-= 15; $mydata->Gems-= 15; $mydata->Recruits-= 15; } $mydata->Quarry+= 1; $mydata->Magic+= 1; $mydata->Dungeons+= 1; break;
					case 133: $tempnum = $this->KeywordCount($mydata->Hand, "Undead"); $this->Attack((($tempnum * 4) + 4), $hisdata->Tower, $hisdata->Wall); break;
					case 134: $mydata->Dungeons+= 2; $nextcard = $this->DrawCard($carddb->GetList("Rare", "Beast"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 135: if ($mydata->Bricks > $hisdata->Bricks) { $this->Attack(10, $hisdata->Tower, $hisdata->Wall); } else { $mydata->Bricks+= 8; } break;
					case 136: $this->Attack(9, $hisdata->Tower, $hisdata->Wall); $bricks_production*= 2; $gems_production*= 2; $recruits_production*= 2; break;
					case 137: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); $mydata->Tower+= 10; break;
					case 138: $this->Attack(50, $hisdata->Tower, $hisdata->Wall); $hisdata->Bricks-= 50; $hisdata->Gems-= 50; $hisdata->Recruits-= 50; break;
					case 139: if ($mydata->Recruits > $hisdata->Recruits) { $this->Attack(10, $hisdata->Tower, $hisdata->Wall); } else { $mydata->Recruits+= 7; } break;
					case 140: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); $hisdata->Bricks-= 2; $hisdata->Gems-= 2; $hisdata->Recruits-= 2; break;
					case 141: $mydata->Tower+= 10; break;
					case 142: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); $mydata->Wall+= 10; $mydata->Quarry-= 1; $hisdata->Wall+= 10; $hisdata->Quarry-= 1; break;
					case 143: $mydata->Bricks+= 60; break;
					case 144: $this->Attack(30, $hisdata->Tower, $hisdata->Wall); $nextcard = $this->DrawCard(array_merge($carddb->GetList("Uncommon", "Undead"), $carddb->GetList("Rare", "Undead")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 145: $nextcard = $this->DrawCard(array_merge($carddb->GetList("Common", "Undead"), $carddb->GetList("Uncommon", "Undead")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 146: $mydata->Bricks+= 5; $mydata->Gems+= 2; $mydata->Recruits+= 1; break;
					case 147: $this->Attack(6, $hisdata->Tower, $hisdata->Wall); $mydata->Gems-= 3; $hisdata->Gems-= 3; break;
					case 148: $mydata->Wall+= 50; $hisdata->Wall+= 50; break;
					case 149: $mydata->Wall+= 10; break;
					case 150: $this->Attack(12, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks-= 5; $mydata->Gems-= 5; $mydata->Recruits-= 5; break;
					case 151: $hisdata->Tower-= 7; break;
					case 152: $mydata->Hand[$cardpos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $tmp = $mydata->Hand; $mydata->Hand = $hisdata->Hand; $hisdata->Hand = $tmp; $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $hisdata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 153: if ($mydata->Gems > $hisdata->Gems) { $this->Attack(10, $hisdata->Tower, $hisdata->Wall); } else { $mydata->Gems+= 7; } break;
					case 154: $tempnum = $this->KeywordCount($mydata->Hand, "Alliance"); if ($tempnum > 3) { $mydata->Wall+= 50; $mydata->Bricks+= 8; } else $mydata->Wall+= 40; break;
					case 155: $mydata->Tower+= 20; break;
					case 156: $mydata->Wall+= $mydata->Gems; $mydata->Tower+= $mydata->Bricks; $mydata->Gems= 0; $mydata->Bricks= 0; break;
					case 157: $hisdata->Tower-= 15; $hisdata->Wall-= 15; $hisdata->Bricks-= 10; $hisdata->Gems-= 10; $hisdata->Recruits-= 10; break;
					case 158: $hisdata->Bricks-= 50; break;
					case 159: $nextcard = $this->DrawCard($mydata->Deck->Rare, $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 160: $this->Attack(1, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits+= 1;  $mydata->Gems+= 2; $hisdata->Recruits-= 1; $hisdata->Gems-= 2; break;
					case 161: $hisdata->Tower-= 20; $hisdata->Wall-= 20; $hisdata->Quarry-= 2; $hisdata->Magic-= 2; $hisdata->Dungeons-= 2; $hisdata->Bricks-= 20; $hisdata->Gems-= 20; $hisdata->Recruits-= 20; break;
					case 162: $mydata->Magic+= 1; break;
					case 163: $mydata->Quarry+= 1; break;
					case 164: $mydata->Dungeons+= 1; break;
					case 165: if ($mydata->Dungeons < $hisdata->Dungeons) { $mydata->Dungeons+= 1; } else { $mydata->Recruits+= 11; } break;
					case 166: $this->Attack(33, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 5; $mydata->Recruits+= 5; break;
					case 167: $this->Attack(200, $hisdata->Tower, $hisdata->Wall); break;
					case 168: $nextcard = $hisdata->LastCard[$hislastcardindex]; break;
					case 169: $mydata->Tower-= 20; $mydata->Tower = max(1, $mydata->Tower); $mydata->Wall+= 70; break;
					case 170: $mydata->Bricks+= 15; $mydata->Gems+= 15; $mydata->Recruits+= 15; $hisdata->Bricks+= 20; $hisdata->Gems+= 20; $hisdata->Recruits+= 20; break;
					case 171: if (!($mylast_card->IsPlayAgainCard() and $mylast_action == 'play')) { $mydata->Tower-= $mychanges['Tower']; $mydata->Wall-= $mychanges['Wall']; $mydata->Quarry-= $mychanges['Quarry']; $mydata->Magic-= $mychanges['Magic']; $mydata->Dungeons-= $mychanges['Dungeons']; $mydata->Bricks-= $mychanges['Bricks']; $mydata->Gems-= $mychanges['Gems']; $mydata->Recruits-= $mychanges['Recruits']; } break;
					case 172: $this->Attack(14, $hisdata->Tower, $hisdata->Wall); break;
					case 173: $this->Attack(25, $hisdata->Tower, $hisdata->Wall); break;
					case 174: $bricks_production*= 2; $gems_production*= 2; $recruits_production*= 2; break;
					case 175: $mydata->Tower+= 13; $mydata->Bricks+= 7; $mydata->Gems+= 7; $mydata->Recruits+= 7; break;
					case 176: $mydata->Tower+= 3; $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; break;
					case 177: $nextcard = $this->DrawCard($carddb->GetList("", "Holy"), $mydata->Hand, $cardpos, 'DrawCard_list'); $tmp = min($this->KeywordCount($mydata->Hand, "Holy"),2); $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; break;
					case 178: $this->Attack($mydata->Recruits, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits= 0; break;
					case 179: $mydata->Tower+= $mydata->Wall; $mydata->Wall = 0; break;
					case 180: $this->Attack(15, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits-= 4; break;
					case 181: $this->Attack(35, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= 5; $mydata->Gems+= 5; $mydata->Recruits+= 5; $hisdata->Bricks-= 5; $hisdata->Gems-= 5; $hisdata->Recruits-= 5; break;
					case 182: $this->Attack(55, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks-= 10; $mydata->Gems-= 10; $mydata->Recruits-= 10; $hisdata->Bricks-= 10; $hisdata->Gems-= 10; $hisdata->Recruits-= 10; break;
					case 183: $nextcard = $this->DrawCard(array(181, 182, 183), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 184: $mydata->Tower+= 5; $mydata->Bricks+= 4; $mydata->Gems+= 4; $mydata->Recruits+= 4; $nextcard = $this->DrawCard(array(181, 182, 159), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 185: if ($mydata->Magic < $hisdata->Magic) { $mydata->Magic+= 1; } else { $mydata->Gems+= 11; } break;
					case 186: $hisdata->Wall-= 40; break;
					case 187: if ($mydata->Quarry < $hisdata->Quarry) { $mydata->Quarry+= 1; } else { $mydata->Bricks+= 12; } break;
					case 188: $mydata->Wall+= 5; break;
					case 189: $mydata->Tower+= 5; $recruits_production*= 3; break;
					case 190: $hisdata->Tower-= 7; $hisdata->Wall-= 7; break;
					case 191: $mydata->Tower+= 15; break;
					case 192: $mydata->Tower+= 45; break;
					case 193: $this->Attack(16, $hisdata->Tower, $hisdata->Wall); break;
					case 194: $tempnum = $this->KeywordCount($mydata->Hand, "Soldier"); if ($tempnum < 4) $nextcard = $this->DrawCard($carddb->GetList("", "Soldier"), $mydata->Hand, $cardpos, 'DrawCard_list'); else $mydata->Recruits+= 7; break;
					case 195: if ($this->KeywordCount($hisdata->Hand, "Dragon") == 0) { $mydata->Bricks+= 6; $mydata->Gems+= 6; $mydata->Recruits+= 6; } else for ($i = 1; $i <= 8; $i++) if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Dragon")) { $mydata->Hand[$i] = $hisdata->Hand[$i]; $mydata->NewCards[$i] = 1; $hisdata->Hand[$i] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $i, 'DrawCard_random'); $hisdata->NewCards[$i] = 1; if ($cardpos == $i) $nextcard = 0; } break;
					case 196: $mydata->Tower+= 12; $nextcard = $this->DrawCard($carddb->GetList("", "Holy"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 197: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); $hisdata->Quarry-= 1; break;
					case 198: $hisdata->Wall-= 60; if ($hisdata->Wall <= 0) { $hisdata->Quarry-= 1; $hisdata->Magic-= 1; $hisdata->Dungeons-= 1; } break;
					case 199: $mydata->Bricks= ($mydata->Bricks * 2); $mydata->Gems= ($mydata->Gems * 2); $mydata->Recruits= ($mydata->Recruits * 2); break;
					case 200: $mydata->Tower+= 20; $hisdata->Tower+= 30; break;
					case 201: $mydata->Wall+= 30; if ($this->KeywordCount($mydata->Hand, "Soldier") > 0) $hisdata->Tower-= 11; break;
					case 202: $this->Attack(17, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= 5; $hisdata->Bricks-= 5; break;
					case 203: $mydata->Tower+= 6; $nextcard = $this->DrawCard(array_merge($carddb->GetList("Uncommon", "Undead"), $carddb->GetList("Rare", "Undead")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 204: $hisdata->Tower-= 9; break;
					case 205: $hisdata->Bricks+= 10; $hisdata->Gems+= 10; $hisdata->Recruits+= 10; $hisdata->Hand = $this->DrawHand_list($carddb->GetList("", "", "Zero")); $hisdata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); break;
					case 206: $mydata->Bricks-= 8; $mydata->Gems-= 8; $mydata->Recruits-= 8; $hisdata->Bricks-= 8; $hisdata->Gems-= 8; $hisdata->Recruits-= 8; break;
					case 207: $tmp = max(0, $hisdata->Recruits - $hisdata->Gems); $tmp = min(23, $tmp); $this->Attack($tmp, $hisdata->Tower, $hisdata->Wall); if ($tmp > 15) { $hisdata->Recruits-= $tmp; } break;
					case 208: $mydata->Wall+= 38; $mydata->Quarry-= 1; break;
					case 209: $mydata->Wall+= 105; $mydata->Quarry-= 1; $mydata->Dungeons-= 1; break;
					case 210: if ($hisdata->Tower < $mydata->Tower) { $mydata->Tower-= 10; $hisdata->Tower-= 10; } else { $mydata->Tower+= 10; $hisdata->Tower+= 10; } break;
					case 211: if ($hisdata->Wall > $mydata->Wall) $this->Attack(25, $hisdata->Tower, $hisdata->Wall); else $this->Attack(13, $hisdata->Tower, $hisdata->Wall); break;
					case 212: $this->Attack(2, $hisdata->Tower, $hisdata->Wall); $hisdata->Recruits-= 1; break;
					case 213: $mydata->Quarry+= 1; $hisdata->Quarry-= 1; break;
					case 214: $mydata->Magic+= 1; $hisdata->Magic-= 1; break;
					case 215: $mydata->Dungeons+= 1; $hisdata->Dungeons-= 1; break;
					case 216: if ($mydata->Tower < 10) $mydata->Tower+= 15; else $mydata->Tower+= 3; break;
					case 217: $mydata->Wall+= 2; for ($i = 1; $i <= 8; $i++) { if ($i != $mode) { $mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$i] = 1; } } break;
					case 218: $this->Attack($hisdata->Gems, $hisdata->Tower, $hisdata->Wall); $hisdata->Gems= 0; break;
					case 219: $tmp = max(0, $hisdata->Bricks - $hisdata->Recruits); $tmp = min(23, $tmp); $this->Attack($tmp, $hisdata->Tower, $hisdata->Wall); if ($tmp > 15) { $hisdata->Bricks-= $tmp; } break;
					case 220: $tmp = max(0, $hisdata->Gems - $hisdata->Bricks); $tmp = min(23, $tmp); $this->Attack($tmp, $hisdata->Tower, $hisdata->Wall); if ($tmp > 15) { $hisdata->Gems-= $tmp; } break;
					case 221: $tmp = ($hisdata->Quarry * 7) + $hisdata->Bricks; $hisdata->Tower-= $tmp; $hisdata->Wall-= $tmp; break;
					case 222: $tmp = max(0, $mydata->Quarry + $mydata->Magic + $mydata->Dungeons - $hisdata->Quarry - $hisdata->Magic - $hisdata->Dungeons); if ($tmp > 0) { $hisdata->Wall = 0; } $tmp = max(0, $mydata->Bricks + $mydata->Gems + $mydata->Recruits - $hisdata->Bricks - $hisdata->Gems - $hisdata->Recruits); $this->Attack($tmp, $hisdata->Tower, $hisdata->Wall); break;
					case 223: $tmp = ($this->KeywordCount($mydata->Hand, "Burning") + $this->KeywordCount($hisdata->Hand, "Burning"))*10; $this->Attack($tmp, $hisdata->Tower, $hisdata->Wall); break;
					case 224: if ($mydata->Wall <= $hisdata->Wall) { $mydata->Quarry-= 1; $mydata->Dungeons-= 1; }; if ($hisdata->Wall <= $mydata->Wall) { $hisdata->Quarry-= 1; $hisdata->Dungeons-= 1; }; break;
					case 225: if ($mydata->Dungeons < $hisdata->Dungeons) { $hisdata->Dungeons-= 2; } else { $hisdata->Recruits-= 13; } break;
					case 226: if ($mydata->Magic < $hisdata->Magic) { $hisdata->Magic-= 2; } else { $hisdata->Gems-= 13; } break;
					case 227: if ($mydata->Quarry < $hisdata->Quarry) { $hisdata->Quarry-= 2; } else { $hisdata->Bricks-= 14; } break;
					case 228: $mydata->Tower+= 35; $mydata->Bricks+= 10; $mydata->Gems+= 10; $mydata->Recruits+= 10; break;
					case 229: $this->Attack($mydata->Bricks, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks= 0; break;
					case 230: if ($hisdata->Wall == 0) { $this->Attack(35, $hisdata->Tower, $hisdata->Wall); } else { $this->Attack(11, $hisdata->Tower, $hisdata->Wall); } break;
					case 231: $mydata->Tower+= 10; break;
					case 232: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); $mydata->Tower+= 10; $mydata->Wall+= 10; $bricks_production*= 2; $gems_production*= 2; $recruits_production*= 2; break;
					case 233: $mydata->Tower+= 15; $mydata->Magic+= 1; break;
					case 234: $mydata->Tower+= 15; $mydata->Dungeons+= 1; break;
					case 235: $mydata->Tower+= 15; $mydata->Quarry+= 1; break;
					case 236: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); $mydata->Tower-= 1; break;
					case 237: $mydata->Tower+= 3; break;
					case 238: $mydata->Wall+= 8; break;
					case 239: $this->Attack(80, $hisdata->Tower, $hisdata->Wall); break;
					case 240: $this->Attack(9, $hisdata->Tower, $hisdata->Wall); break;
					case 241: $hisdata->Bricks-= 20; $hisdata->Gems-= 20; $hisdata->Recruits-= 20; break;
					case 242: $hisdata->Bricks= 0; $hisdata->Gems= 0; $hisdata->Recruits= 0; break;
					case 243: $this->Attack(8, $hisdata->Tower, $hisdata->Wall); $hisdata->Bricks+= 2; $hisdata->Gems+= 2; $hisdata->Recruits+= 2; break;
					case 244: $hisdata->Tower-= 5; $hisdata->Wall-= 5; break;
					case 245: $hisdata->Tower-= 20; $hisdata->Bricks-= 10; $hisdata->Recruits-= 10; break;
					case 246: $hisdata->Tower-= 10; $hisdata->Wall-= 10; break;
					case 247: $hisdata->Wall-= 1; $hisdata->Bricks-= 1; $hisdata->Gems-= 1; $hisdata->Recruits-= 1; $nextcard = $this->DrawCard(array_diff($carddb->GetList("Common"), $mydata->Deck->Common), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 248: $mydata->Bricks-= 2; $mydata->Gems-= 2; $mydata->Recruits-= 2; $mydata->Tower-= 2; $mydata->Wall-= 5; $hisdata->Bricks-= 2; $hisdata->Gems-= 2; $hisdata->Recruits-= 2; $hisdata->Tower-= 2; $hisdata->Wall-= 5; break;
					case 249: $tmp = $mydata->Wall; $mydata->Wall= $hisdata->Wall; $hisdata->Wall= $tmp; break;
					case 250: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); break;
					case 251: $mydata->Hand[$cardpos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random');
								$mydata->NewCards[$cardpos] = 1;
								$my_storage = $his_storage = array();
								for ($i = 1; $i <= 8; $i++)
								{
									if ($carddb->GetCard($mydata->Hand[$i])->GetClass() != "Rare") $my_storage[] = $i;
									if ($carddb->GetCard($hisdata->Hand[$i])->GetClass() != "Rare") $his_storage[] = $i;
								}
								$count = min(count($my_storage), count($his_storage), 3);
								if ($count > 0)
								{
									shuffle($my_storage); shuffle($his_storage);
									for ($k = 0; $k < $count; $k++)
									{
										$i = $my_storage[$k]; $j = $his_storage[$k];
										$mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random');
										$hisdata->Hand[$j] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $cardpos, 'DrawCard_random');
										$mydata->NewCards[$i] = 1;
										$hisdata->NewCards[$j] = 1;
									}
								}
							$nextcard = 0; break;
					case 252: $mydata->Hand[$cardpos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random');
								$mydata->NewCards[$cardpos] = 1;
								$my_storage = $his_storage = array();
								for ($i = 1; $i <= 8; $i++)
								{
									if ($carddb->GetCard($mydata->Hand[$i])->GetClass() != "Rare") $my_storage[] = $i;
									if ($carddb->GetCard($hisdata->Hand[$i])->GetClass() != "Rare") $his_storage[] = $i;
								}
								$count = min(count($my_storage), count($his_storage), 3);
								if ($count > 0)
								{
									shuffle($my_storage); shuffle($his_storage);
									for ($k = 0; $k < $count; $k++)
									{
										$i = $my_storage[$k]; $j = $his_storage[$k];
										$tempcard = $mydata->Hand[$i];
										$mydata->Hand[$i] = $hisdata->Hand[$j];
										$hisdata->Hand[$j] = $tempcard;
										$mydata->NewCards[$i] = 1;
										$hisdata->NewCards[$j] = 1;
									}
								}
							$nextcard = 0; break;
					case 253: $mydata->Hand = array (1=> 114, 113, 112, 120, 133, 330, 28, 103); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 254: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); if ($this->KeywordCount($mydata->Hand, "Burning") > 3) $hisdata->Magic-= 1; break;
					case 255: $mydata->Hand = $this->DrawHand_random($mydata->Deck); $hisdata->Hand = $this->DrawHand_random($hisdata->Deck); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $hisdata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 256: $hisdata->Wall-= 6; break;
					case 257: $mydata->Tower+= 2; $hisdata->Tower-= 2; break;
					case 258: $this->Attack(4, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks-= 3; break;
					case 259: $this->Attack(9, $hisdata->Tower, $hisdata->Wall); $bricks_production = 0; $gems_production = 0; $recruits_production = 0; break;
					case 260: $this->Attack(4, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= 2; $mydata->Gems+= 2; $mydata->Recruits+= 2; $hisdata->Bricks-= 2; $hisdata->Gems-= 2; $hisdata->Recruits-= 2; break;
					case 261: $mydata->Recruits+= 3; $hisdata->Recruits-= 3; break;
					case 262: $mydata->Wall+= 6; break;
					case 263: $mydata->Bricks+= 4; $mydata->Gems+= 4; $mydata->Recruits+= 4; $bricks_production = 0; $gems_production = 0; $recruits_production = 0; break;
					case 264: $mydata->Hand = $this->DrawHand_random($mydata->Deck); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 265: $mydata->Tower+= 99; break;
					case 266: if ($mode == 1) $mydata->Wall+= 20; elseif ($mode == 2) $mydata->Tower+= 10; break;
					case 267: if ($mode == 1) $mydata->Quarry+= 1; elseif ($mode == 2) $mydata->Magic+= 1; elseif ($mode == 3) $mydata->Dungeons+= 1; break;
					case 268: if ($mode == 1) $this->Attack(30, $hisdata->Tower, $hisdata->Wall); elseif ($mode == 2) $mydata->Wall+= 33; break;
					case 269: $mydata->Bricks-= 1; $mydata->Gems-= 1; $mydata->Recruits-= 1; $mydata->Wall+= 1; $mydata->Hand[$mode] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$mode] = 1; break;
					case 270: $hisdata->Hand[$mode] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $mode, 'DrawCard_random'); $hisdata->NewCards[$mode] = 1; break;
					case 271: $hisdata->Tower-= 2; $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; break;
					case 272: if ($mydata->Wall > $hisdata->Wall) $hisdata->Tower-= 7; else $this->Attack(6, $hisdata->Tower, $hisdata->Wall); break;
					case 273: if ($mode == 1) $mydata->Bricks+= 6; elseif ($mode == 2) $mydata->Gems+= 5; elseif ($mode == 3) $mydata->Recruits+= 5; break;  
					case 274: $last_card = $mydata->LastCard[$mylastcardindex]; if ((($carddb->GetCard($last_card)->GetResources("Bricks") + $carddb->GetCard($last_card)->GetResources("Gems") + $carddb->GetCard($last_card)->GetResources("Recruits")) == 0) and ($mylast_action == 'play')) { $mydata->Gems+= 3; $mydata->Recruits+= 2; } else { $mydata->Gems+= 2; $mydata->Recruits+= 1; } break;
					case 275: $bricks_production*= 2; break;
					case 276: $mydata->Wall+= 60; break;
					case 277: $mydata->Tower+= 35; break;
					case 278: if ($mode == 1) $mydata->Wall+= 8; elseif ($mode == 2) $mydata->Tower+= 5; break;
					case 279: if ($mode == 1) $mydata->Wall+= 5; elseif ($mode == 2) $this->Attack(6, $hisdata->Tower, $hisdata->Wall); elseif ($mode == 3) $hisdata->Tower-= 4; break;
					case 280: $mydata->Hand[$mode] = $hisdata->Hand[$mode]; $hisdata->Hand[$mode] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $mode, 'DrawCard_random'); $mydata->NewCards[$mode] = 1; $hisdata->NewCards[$mode] = 1; break;
					case 281: $this->Attack(11 - (int)round($mydata->Tower/20), $hisdata->Tower, $hisdata->Wall); break;
					case 282: $mydata->Bricks-= (int)round($mydata->Bricks * (($mydata->Tower)/100)); $mydata->Gems-= (int)round($mydata->Gems * (($mydata->Tower)/100)); $mydata->Recruits-= (int)round($mydata->Recruits * (($mydata->Tower)/100)); $hisdata->Bricks-= (int)round($hisdata->Bricks * (($hisdata->Tower)/100)); $hisdata->Gems-= (int)round($hisdata->Gems * (($hisdata->Tower)/100)); $hisdata->Recruits-= (int)round($hisdata->Recruits * (($hisdata->Tower)/100)); break;
					case 283: $mydata->Tower= 30; $mydata->Wall= 20; $hisdata->Tower= 30; $hisdata->Wall= 20; break;
					case 284: if (($mydata->Tower < 50)&&($hisdata->Tower > 60)) $hisdata->Tower-= 25; else $hisdata->Tower-= 10; break;
					case 285: $this->Attack(($mydata->Bricks + $mydata->Gems + $mydata->Recruits), $hisdata->Tower, $hisdata->Wall); $mydata->Bricks= 0; $mydata->Gems= 0; $mydata->Recruits= 0; break;
					case 286: $hisdata->Tower-= (int)round($hisdata->Tower/2); break;
					case 287: $mydata->Hand = $this->DrawHand_list(array(97, 95, 13, 42, 51, 20, 76, 68)); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $mydata->Bricks+= 10; $mydata->Gems+= 10; $mydata->Recruits+= 10; $nextcard = 0; break;
					case 288: $hisdata->Bricks+= 10; $hisdata->Gems+= 10; $hisdata->Recruits+= 10; $hisdata->Hand[$mode] = 288; $hisdata->NewCards[$mode] = 1; break;
					case 289: $mydata->Tower+= 3; $mydata->Wall+= 7; break;
					case 290: $mydata->Tower+= 15; $mydata->Wall+= 30; break;
					case 291: $mydata->Hand = array (1=> 367, 279, 7, 305, 193, 36, 322, 166); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $mydata->Recruits+= 35; $nextcard = 0; break;
					case 292: $bricks_production*= 2; $gems_production*= 2; $recruits_production*= 2; $mydata->Hand = array (1=> 122, 122, 111, 111, 272, 272, 190, 136); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 293: $hisdata->Bricks-= 1; $hisdata->Gems-= 1; $hisdata->Recruits-= 1; $hisdata->Tower-= 12; break;
					case 294: $mydata->Hand = array (1=> 1, 238, 238, 149, 149, 19, 47, 276); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $mydata->Bricks+= 20; $nextcard = 0; break;
					case 295: $mydata->Hand = array (1=> 2, 2, 18, 18, 191, 155, 228, 87); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $mydata->Bricks+= 40; $nextcard = 0; break;
					case 296: $mydata->Wall+= 14; break;
					case 297: $mydata->Wall+= 2; break;
					case 298: $this->Attack(25, $hisdata->Tower, $hisdata->Wall); $mydata->Magic-= 1; $mydata->Hand[$mode] = 28;  $mydata->NewCards[$mode] = 1; break;
					case 299: $this->Attack($mydata->Quarry + $mydata->Magic + $mydata->Dungeons, $hisdata->Tower, $hisdata->Wall); break;
					case 300: $hisdata->Bricks-= 5; break;
					case 301: if (($mydata->Tower < $mydata->Wall) and ($mydata->Tower < 45)) $mydata->Tower+= 15; else $mydata->Tower+= 6; break;
					case 302: $tempnum = $this->KeywordCount($mydata->Hand, "Unliving"); $mydata->Bricks+= $tempnum; $mydata->Gems+= $tempnum; $mydata->Recruits+= $tempnum; break;
					case 303: $tempnum = $this->KeywordCount($mydata->Hand, "Unliving"); $mydata->Tower+= $tempnum; $mydata->Wall+= $tempnum; break;
					case 304: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); break;
					case 305: $this->Attack(12, $hisdata->Tower, $hisdata->Wall); $tempnum = $this->KeywordCount($mydata->Hand, "Soldier"); if ($tempnum > 3) $hisdata->Dungeons-= 1; break;
					case 306: $this->Attack(13, $hisdata->Tower, $hisdata->Wall); $tempnum = $this->KeywordCount($mydata->Hand, "Unliving"); if ($tempnum > 4) $hisdata->Quarry-= 1; break;
					case 307: $rarities = array(); $upgrades = array("Common" => "Uncommon", "Uncommon" => "Rare"); $rare = true;
								for ($i = 1; $i <= 8; $i++)
									if ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Undead")) $rarities[$i] = $carddb->GetCard($mydata->Hand[$i])->GetClass();
								foreach ($rarities as $index => $val)
									if (($val == "Common") OR (($val == "Uncommon") AND ($rare)))
									{
										$mydata->Hand[$index] = $this->DrawCard($carddb->GetList($upgrades[$val], "Undead"), $mydata->Hand, $cardpos, 'DrawCard_list');
										if (($val == "Uncommon") AND ($rare)) $rare = false;
									}
								break;
					case 308: $tempnum = min(5, $this->KeywordCount($mydata->Hand, "Unliving")); $mydata->Wall+= 9*$tempnum; break;
					case 309: $tempnum = min(5, $this->KeywordCount($mydata->Hand, "Unliving")); $mydata->Tower+= 6*$tempnum; break;
					case 310: $mydata->Tower+= 8; $tempnum = $this->KeywordCount($mydata->Hand, "Unliving"); if ($tempnum > 4) $mydata->Magic+= 1; break;
					case 311: $mydata->Tower+= 11; $mydata->Wall+= 16; $mydata->Bricks+= 2; $mydata->Gems+= 2; $mydata->Recruits+= 2; break;
					case 312: $mydata->Tower+= 6; $mydata->Wall+= 9; $tempnum = $this->KeywordCount($mydata->Hand, "Unliving"); if ($tempnum > 4) $mydata->Quarry+= 1; break;
					case 313: $nextcard = $this->DrawCard(array_merge($carddb->GetList("Uncommon", "Titan"), $carddb->GetList("Rare", "Titan")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 314: $tmp = $this->KeywordCount($mydata->Hand, "Barbarian"); $hisdata->Tower-= 6*$tmp;  $hisdata->Wall-= 9*$tmp; break;
					case 315: if (in_array(302, $mydata->Hand) and in_array(303, $mydata->Hand) and in_array(310, $mydata->Hand) and in_array(311, $mydata->Hand) and in_array(312, $mydata->Hand)) { $hisdata->Tower= 0; $hisdata->Wall= 0; } break;
					case 316: $rarity = $carddb->GetCard($hisdata->Hand[$mode])->GetClass(); $hisdata->Hand[$mode] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $mode, 'DrawCard_random'); $mydata->Hand[$mode] = $this->DrawCard($carddb->GetList($rarity, "Undead"), $mydata->Hand, $cardpos, 'DrawCard_list'); if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; $hisdata->NewCards[$mode] = 1; break;
					case 317: $this->Attack(22, $hisdata->Tower, $hisdata->Wall); break;
					case 318: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); $mydata->Hand[$mode] = 318; if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 319: $mydata->Gems-= 3; $hisdata->Wall-= 4; $hisdata->Tower-= 4; break;
					case 320: if (($mydata->Tower < 10) && ($mydata->Wall == 0)) { $mydata->Tower= 25; $mydata->Wall= 15; } else { $mydata->Bricks+= 2; $mydata->Gems+= 2; $mydata->Recruits+= 2; } break;
					case 321: $storage = array(); for ($i = 1; $i <= 8; $i++) if (($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Undead")) AND ($i != $mode)) $storage[$i] = $i; shuffle($storage); $tmp = 0; for ($i = 0; ($i < count($storage) AND ($i < 4)); $i++) { $mydata->Hand[$storage[$i]] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$storage[$i]] = 1; $tmp++; } $mydata->Wall+= $tmp*10;
						break;
					case 322: $tempnum = min(4, $this->KeywordCount($mydata->Hand, "Soldier")); $this->Attack(8*$tempnum, $hisdata->Tower, $hisdata->Wall); if ($tempnum > 3) $mydata->Wall+= 15; break;
					case 323: if ($mode == 1) { $hisdata->Hand = $this->DrawHand_list(array_merge($carddb->GetList("Common", "Beast"), $carddb->GetList("Uncommon", "Beast"))); $hisdata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); } elseif ($mode == 2) { $tempnum = $this->KeywordCount($mydata->Hand, "Beast"); $tempnum+= $this->KeywordCount($hisdata->Hand, "Beast"); $this->Attack(5*$tempnum, $hisdata->Tower, $hisdata->Wall);} break;
					case 324: $tempnum = $this->KeywordCount($mydata->Hand, "Beast") + $this->KeywordCount($hisdata->Hand, "Beast"); if ($mode == 1) { $mydata->Bricks+= $tempnum; $mydata->Gems+= $tempnum; $mydata->Recruits+= $tempnum; } elseif ($mode == 2) $this->Attack(3*$tempnum, $hisdata->Tower, $hisdata->Wall); break;
					case 325: $temp_array = array(); for ($i = 1; $i <= 8; $i++) if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Undead")) $temp_array[$i] = $i; shuffle($temp_array); for ($i = 0; ($i < count($temp_array)) AND ($i < 4); $i++) { $hisdata->Hand[$temp_array[$i]] = 381; $hisdata->NewCards[$temp_array[$i]] = 1; } break;
					case 326: for ($i = 1; $i <= 8; $i++) if ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Soldier")) { $mydata->Hand[$i] = $this->DrawCard(array_merge($carddb->GetList("Uncommon", "Holy"), $carddb->GetList("Rare", "Holy")), $mydata->Hand, $cardpos, 'DrawCard_list'); $mydata->NewCards[$i] = 1; } break;
					case 327: $mydata->Tower+= 3; $mydata->Hand[$mode] = $this->DrawCard($carddb->GetList("", "Holy"), $mydata->Hand, $cardpos, 'DrawCard_list'); if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 328: $nextcard = $this->DrawCard($carddb->GetList("", "Barbarian"), $mydata->Hand, $cardpos, 'DrawCard_list'); $tempnum = $this->KeywordCount($mydata->Hand, "Barbarian"); if ($tempnum > 3) { $mydata->Bricks+= 3; $mydata->Gems+= 3; $mydata->Recruits+= 3; } break;
					case 329: $mydata->Gems+= 6; $mydata->Wall+= 3; $bricks_production = 0; $gems_production = 0; $recruits_production = 0; $mydata->Hand[$mode] = 329; if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 330: $tmp = 0; $temparray = array(); $j = 1;
						if ($mylast_action == 'discard') $temparray[0] = $mydata->LastCard[$mylastcardindex];
						if (count($discarded_cards[0]) > 0) $temparray = array_merge($discarded_cards[0], $temparray);
						if (count($hisdata->DisCards[1]) > 0) $temparray = array_merge($temparray, $hisdata->DisCards[1]);
						$storage = array(); $j = 0;						
						for ($i = 0; $i < count($temparray); $i++) if ($carddb->GetCard($temparray[$i])->HasKeyword("Undead")) { $storage[$j] = $temparray[$i]; $j++; }
						$temparray = $storage;
						if (count($temparray) > 8) $temparray = array_pad($temparray, 8);
						$trans_array = array();
						for ($i = 0; $i < 8; $i++) $trans_array[$i] = $i + 1;
						shuffle($trans_array);
						for ($i = 0; $i < count($temparray); $i++)
						{
							$mydata->Hand[$trans_array[$i]] = $temparray[$i];
							$mydata->NewCards[$trans_array[$i]] = 1;
							if ($trans_array[$i] == $cardpos) $nextcard = 0;
						}
						$this->Attack(count($temparray)*10, $hisdata->Tower, $hisdata->Wall); break;
					case 331: $tmp = $this->KeywordCount($mydata->Hand, "Holy"); $tmp = min($tmp,4); $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; $tmp = $this->KeywordCount($hisdata->Hand, "Holy"); $tmp = min($tmp,4); $hisdata->Bricks+= $tmp; $hisdata->Gems+= $tmp; $hisdata->Recruits+= $tmp; break;
					case 332: $tmp = $this->KeywordCount($mydata->Hand, "Undead"); $tmp = min($tmp,4); $mydata->Bricks-= $tmp; $mydata->Gems-= $tmp; $mydata->Recruits-= $tmp; $tmp = $this->KeywordCount($hisdata->Hand, "Undead"); $tmp = min($tmp,4); $hisdata->Bricks-= $tmp; $hisdata->Gems-= $tmp; $hisdata->Recruits-= $tmp; break;
					case 333: $tmp = $this->KeywordCount($mydata->Hand, "Undead"); $tmp = max(5 - $tmp,0); $this->Attack($tmp*4, $mydata->Tower, $mydata->Wall); $tmp = $this->KeywordCount($hisdata->Hand, "Undead"); $tmp = max(5 - $tmp,0); $this->Attack($tmp*4, $hisdata->Tower, $hisdata->Wall); break;
					case 334: if (($mylast_card->HasKeyword("Barbarian")) and ($mylast_action == 'play')) $hisdata->Wall-= 7; $this->Attack(7, $hisdata->Tower, $hisdata->Wall); break;
					case 335: $this->Attack(21, $hisdata->Tower, $hisdata->Wall);
					if ($mylast_card->HasKeyword("Burning"))
					{
						$i = 1;
						for ($j = 1; $j <= 2; $j++)
						{
							while (($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Burning")) and ($i <= 8)) $i++;
							if ($i > 8) break;//no "free" slots in hand
							$mydata->Hand[$i] = $mydata->LastCard[$mylastcardindex];
							$mydata->NewCards[$i] = 1;
						}
					}
					break;
					case 336: if (($mylast_card->HasKeyword("Burning")) and ($mylast_action == 'play')) $nextcard = $mydata->LastCard[$mylastcardindex];
							  else $nextcard = $this->DrawCard($carddb->GetList("", "Burning"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 337: if (($mylast_card->HasKeyword("Barbarian")) and ($mylast_action == 'play')) $hisdata->Wall-= 17; $this->Attack(20, $hisdata->Tower, $hisdata->Wall); break;
					case 338: if (($mylast_card->HasKeyword("Mage")) and ($mylast_action == 'play')) $mydata->Gems+= $mydata->Magic * 3; $this->Attack(26, $hisdata->Tower, $hisdata->Wall); break;
					case 339: if (!isset($mynewflags[$cardpos])) { $tmp = min(15, max(5, $hisdata->Bricks - $mydata->Bricks)); $mydata->Bricks+= $tmp; $hisdata->Bricks-= $tmp; $tmp = min(15, max(5, $hisdata->Gems - $mydata->Gems)); $mydata->Gems+= $tmp; $hisdata->Gems-= $tmp; $tmp = min(15, max(5, $hisdata->Recruits - $mydata->Recruits)); $mydata->Recruits+= $tmp; $hisdata->Recruits-= $tmp; } break;
					case 340: if ($mydata->Tower < $hisdata->Tower) $this->Attack(12, $hisdata->Tower, $hisdata->Wall); else $this->Attack(6, $hisdata->Tower, $hisdata->Wall); break;
					case 341: $tmp = $this->KeyWordCount($mydata->Hand, "Barbarian") + $this->KeyWordCount($mydata->Hand, "Holy"); $this->Attack(10 + 3*$tmp, $hisdata->Tower, $hisdata->Wall); break;
					case 342: $mydata->Hand[$cardpos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $nextcard = 0; $tmp = mt_rand(1, 8); $mydata->Hand[$tmp] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$tmp] = 1; $temp_array = array(); for ($i = 1; $i <= 8; $i++) if (!$carddb->GetCard($mydata->Hand[$i])->HasKeyword("Holy")) $temp_array[$i] = $i; shuffle($temp_array); for ($i = 0; ($i < count($temp_array)) AND ($i < 3); $i++) { $mydata->Hand[$temp_array[$i]] = 340; $mydata->NewCards[$temp_array[$i]] = 1; } break;
					case 343: for ($i = 1; $i <= 8; $i++) if ($mydata->Hand[$i] == 340) { $mydata->Hand[$i] = 341; $mydata->NewCards[$i] = 1; }  elseif ($mydata->Hand[$i] == 341) { $mydata->Hand[$i] = 31; $mydata->NewCards[$i] = 1; } break;
					case 344: $mydata->Wall+= 15; if (($mylast_card->HasKeyword("Barbarian")) and ($mylast_action == 'play')) $this->Attack($mylast_card->GetResources("Recruits"), $hisdata->Tower, $hisdata->Wall); break;
					case 345: $tmp = $this->KeyWordCount($mydata->Hand, "Barbarian"); $hisdata->Wall-= $tmp * 10; $this->Attack(40, $hisdata->Tower, $hisdata->Wall); break;
					case 346: for( $i = 1; $i <= 8; $i++ ) if( $carddb->GetCard($mydata->Hand[$i])->HasKeyword("Beast") and $carddb->GetCard($mydata->Hand[$i])->GetClass() == "Rare" and $carddb->GetCard($mydata->Hand[$i])->GetResources("Recruits") < 11 ) break; if( $i <= 8 ) { $mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$i] = 1; $this->Attack(42, $hisdata->Tower, $hisdata->Wall); } else $mydata->Recruits+= 9; break;
					case 347: $mydata->Hand[$cardpos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $nextcard = 0; $j = 0;
								for ($i = 1; $i <= 8; $i++)
									if (!$carddb->GetCard($mydata->Hand[$i])->HasKeyword("Beast"))
									{
										$mydata->Hand[$i] = $this->DrawCard($carddb->GetList("", "Beast"), $mydata->Hand, $cardpos, 'DrawCard_list');
										$mydata->NewCards[$i] = 1;
										$j++;
									}
								$mydata->Bricks-= $j * 2; $mydata->Gems-= $j * 2; $mydata->Recruits-= $j * 2;
								break;
					case 348: $found = false; for ($i = 1; $i <= $hislastcardindex; $i++) if ($carddb->GetCard($hisdata->LastCard[$i])->HasKeyword("Swift")) { $found = true; break; } if ($found) { $hisdata->Bricks-= 12; $hisdata->Gems-= 12; $hisdata->Recruits-= 12; } else { $hisdata->Bricks-= 3; $hisdata->Gems-= 3; $hisdata->Recruits-= 3; } break;
					case 349: if ($mylast_card->HasKeyword("Unliving")) { $tmp = $mylast_card->GetResources("Bricks"); $mydata->Tower+= round($tmp / 3); $mydata->Wall+= round($tmp / 2); } break;
					case 350: if (($mylast_card->HasKeyword("Undead")) and ($mylast_action == 'play')) { $tmp = $mylast_card->GetResources(""); $this->Attack(round($tmp / 2), $hisdata->Tower, $hisdata->Wall); $mydata->Tower+= round($tmp / 3); $mydata->Wall+= round($tmp / 2); } break;
					case 351: $nextcard = (isset($mynewflags[$cardpos])) ? 159 : 351; break;
					case 352: if ($mydata->Magic > $hisdata->Magic) $this->Attack(34, $hisdata->Tower, $hisdata->Wall); else $this->Attack(15, $hisdata->Tower, $hisdata->Wall); break;
					case 353: $mydata->Magic-= 1; if ($mode == 1) $mydata->Quarry+= 1; elseif ($mode == 2) $mydata->Dungeons+= 1; break;
					case 354: $mydata->Tower+= 9; $mydata->Wall+= 15; if ($this->KeywordCount($mydata->Hand, "Legend") > 0) $mydata->Magic+= 1; break;
					case 355: $j = 0;
								for ($i = 1; $i <= 8; $i++)
									if (($carddb->GetCard($mydata->Hand[$i])->HasKeyword("any")) AND ($i != $cardpos))
									{
										$mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random');
										$mydata->NewCards[$i] = 1;
										$j++;
									}
								$this->Attack($j * 7, $mydata->Tower, $mydata->Wall);
								$j = 0;
								for ($i = 1; $i <= 8; $i++)
									if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("any"))
									{
										$hisdata->Hand[$i] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $cardpos, 'DrawCard_random');
										$hisdata->NewCards[$i] = 1;
										$j++;
									}
								$this->Attack($j * 7, $hisdata->Tower, $hisdata->Wall);
								break;
					case 356: $hisdata->Gems-= 3;  $hisdata->Recruits-= 2; break;
					case 357: $this->Attack(3, $hisdata->Tower, $hisdata->Wall); $hisdata->Gems-= 1;  $hisdata->Recruits-= 3; break;
					case 358: $hisdata->Tower-= 16; $hisdata->Wall-= 19; break;
					case 359: $found = false; for ($i = 1; $i <= 8; $i++) if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Charge")) { $found = true; break; } $mydata->Wall+= ($found) ? 18 : 10; break;
					case 360: $mydata->Bricks-= 2; $mydata->Gems-= 2; $mydata->Recruits-= 2; break;
					case 361: $this->Attack(7, $hisdata->Tower, $hisdata->Wall); if (($mylast_card->HasKeyword("Mage")) and ($mylast_action == 'play')) { $hisdata->Bricks-= 3; $hisdata->Gems-= 3; $hisdata->Recruits-= 3; }break;
					case 362: $mydata->Wall+= 5; $hisdata->Wall-= 5; $this->Attack(5, $hisdata->Tower, $hisdata->Wall); break;
					case 363:
							$storage = array(); $min = 1000;
							for ($i = 1; $i <= 8; $i++)
								if ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Soldier"))
								{
									$storage[$i] = $carddb->GetCard($mydata->Hand[$i])->GetResources("Recruits");
									$min = min($storage[$i], $min);									
								}
							if (count($storage) > 0)
							{
								$min_array = array();
								foreach ($storage as $c_pos => $c_cost) if ($c_cost == $min) $min_array[$c_pos] = $min;
								$discarded_pos = array_rand($min_array);
								$mydata->Hand[$discarded_pos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $discarded_pos, 'DrawCard_random');
								$mydata->NewCards[$discarded_pos] = 1;
								$mydata->Bricks+= 3; $mydata->Gems+= 3; $mydata->Recruits+= 3;
							}
							break;
					case 364: if ((($mychanges['Bricks'] < 0) OR ($mychanges['Gems'] < 0) OR ($mychanges['Recruits'] < 0)) AND !($mylast_card->IsPlayAgainCard() and $mylast_action == 'play')) { $hisdata->Bricks+= min(0, max(-6, $mychanges['Bricks'])); $hisdata->Gems+= min(0, max(-6, $mychanges['Gems'])); $hisdata->Recruits+= min(0, max(-6, $mychanges['Recruits'])); } break;
					case 365: $tmp = 0; $found = false;
						for ($i = 1; $i <= $mylastcardindex; $i++) if (($carddb->GetCard($mydata->LastCard[$i])->HasKeyword("Quick")) AND ($mydata->LastAction[$i] == 'play')) { $found = true; break; }
						if ($found)
							for ($i = 1; $i <= 8; $i++)
								if ($mydata->NewCards[$i] == 1)
								{
									$mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $i, 'DrawCard_random');
									if ($i == $cardpos) $nextcard = 0;
									$tmp++;
								}
						if ($tmp > 0) { $mydata->Bricks-= $tmp; $mydata->Gems-= $tmp; $mydata->Recruits-= $tmp; }
								$tmp = 0; $found = false;
						for ($i = 1; $i <= $hislastcardindex; $i++) if (($carddb->GetCard($hisdata->LastCard[$i])->HasKeyword("Quick")) AND ($hisdata->LastAction[$i] == 'play')) { $found = true; break; }
						if ($found)
							for ($i = 1; $i <= 8; $i++)
								if ($hisdata->NewCards[$i] == 1)
								{
									$hisdata->Hand[$i] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $i, 'DrawCard_random');
									if ($i == $cardpos) $nextcard = 0;
									$tmp++;
								}
						if ($tmp > 0) { $hisdata->Bricks-= $tmp; $hisdata->Gems-= $tmp; $hisdata->Recruits-= $tmp; }
						break;
					case 366: $mydata->Tower+= 1; if ((($mychanges['Bricks'] < 0) OR ($mychanges['Gems'] < 0) OR ($mychanges['Recruits'] < 0)) AND !($mylast_card->IsPlayAgainCard() AND $mylast_action == 'play')) { $mydata->Bricks-= min(0, max(-6, $mychanges['Bricks'])); $mydata->Gems-= min(0, max(-6, $mychanges['Gems'])); $mydata->Recruits-= min(0, max(-6, $mychanges['Recruits'])); } break;
					case 367: $found = false; for ($i = 1; $i <= 8; $i++) if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Charge")) { $found = true; break; } $this->Attack((($found) ? 4 : 10), $hisdata->Tower, $hisdata->Wall); break;
					case 368: if (!($mylast_card->IsPlayAgainCard() and $mylast_action == 'play') AND ($hischanges['Wall'] > 8)) $hisdata->Wall-= 12; $this->Attack(4, $hisdata->Tower, $hisdata->Wall); break;
					case 369: $tmp = $this->KeyWordCount($mydata->Hand, "Beast") + $this->KeyWordCount($mydata->Hand, "Burning") + $this->KeyWordCount($hisdata->Hand, "Beast") + $this->KeyWordCount($hisdata->Hand, "Burning"); $this->Attack(13 + 2*$tmp, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits+= $tmp; break;
					case 370: $this->Attack(27, $hisdata->Tower, $hisdata->Wall); $d_found = $s_found = false; for ($i = 1; $i <= 8; $i++) if ($i != $cardpos) { if ((!$d_found) AND ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Dragon"))) $d_found = true; if ((!$s_found) AND ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Soldier"))) $s_found = true; } if ($d_found AND $s_found) $hisdata->Tower-= 14; break;
					case 371: $this->Attack(35, $hisdata->Tower, $hisdata->Wall); $l_found = $m_found = false; for ($i = 1; $i <= 8; $i++) if ($i != $cardpos) { if ((!$l_found) AND ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Legend"))) $l_found = true; if ((!$m_found) AND ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Mage"))) $m_found = true; } if ($l_found AND $m_found) { $mydata->Magic+= 1; $mydata->Gems+= 20; } break;
					case 372: $tmp = 0; for ($i = 1; $i <= 8; $i++) if (($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Undead")) AND ($i != $mode) AND ($i != $cardpos)) { $mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$i] = 1; $tmp++; } $this->Attack($tmp*9, $hisdata->Tower, $hisdata->Wall); break;
					case 373: $tmp = $this->KeywordCount($mydata->Hand, "Unliving"); if ($tmp > 3) { $mydata->Bricks+= min(round($carddb->GetCard($hisdata->Hand[$mode])->GetResources("Bricks") / 2),20); $mydata->Gems+= min(round($carddb->GetCard($hisdata->Hand[$mode])->GetResources("Gems") / 2),20); $mydata->Recruits+= min(round($carddb->GetCard($hisdata->Hand[$mode])->GetResources("Recruits") / 2),20); } $hisdata->Hand[$mode] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $mode, 'DrawCard_random'); $hisdata->NewCards[$mode] = 1; break;
					case 374: $hisdata->Tower-= 11; if (($mylast_card->HasKeyword("Mage")) and ($mylast_action == 'play')) { $hisdata->Bricks-= 5; $hisdata->Gems-= 5; $hisdata->Recruits-= 5; } break;
					case 375: $mydata->Tower+= 7; $mydata->Wall+= 11; $bricks_production*= 3; $gems_production*= 3; $recruits_production*= 3; break;
					case 376: $b_found = $s_found = false; for ($i = 1; $i <= 8; $i++) if ($i != $cardpos) { if ((!$b_found) AND ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Beast"))) $b_found = true; if ((!$s_found) AND ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Soldier"))) $s_found = true; } $this->Attack(19 + (($b_found) ? 15 : 0) + (($s_found) ? 15 : 0), $hisdata->Tower, $hisdata->Wall); break;
					case 377: $tmp = $this->KeywordCount($mydata->Hand, "Alliance"); $this->Attack(20 + $tmp * 5, $hisdata->Tower, $hisdata->Wall); $mydata->Wall+= $tmp * 4; $mydata->Tower+= $tmp * 3; $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; break;
					case 378: $bonus = false; $temp_array = array(203, 307, 316); if (in_array($mydata->LastCard[$mylastcardindex], $temp_array) and ($mylast_action == 'play')) $bonus = true; $this->Attack(56 + (($bonus) ? 36 : 0), $hisdata->Tower, $hisdata->Wall); break;
					case 379: $mydata->Tower = 50; $mydata->Wall = 70; $mydata->Bricks+= 10; $mydata->Gems+= 10; $mydata->Recruits+= 10; break;
					case 380: if (!isset($mynewflags[$cardpos])) { $temp_array = array("Tower", "Wall", "Quarry", "Magic", "Dungeons", "Bricks", "Gems", "Recruits"); foreach($temp_array as $attribute) $mydata->$attribute = $hisdata->$attribute = round(($mydata->$attribute + $hisdata->$attribute) / 2); } break;
					case 381: $found = false; for ($i = 1; $i <= 8; $i++) if ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Holy")) { $found = true; break; } if ($found) $mydata->Gems+= 1; break;
					case 382:
							$storage = array(); $j = 0;
							for ($i = 1; $i <= 8; $i++)
								if ($hisdata->Hand[$i] == 381)
								{
									$storage[$j] = $i;
									$j++;
								}
							if ($j > 0)
							{
								$discarded_pos = $storage[array_rand($storage)];
								$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $discarded_pos, 'DrawCard_random');
								$hisdata->NewCards[$discarded_pos] = 1;
								$mydata->Bricks+= 3; $mydata->Gems+= 3; $mydata->Recruits+= 3;
								$hisdata->Bricks-= 3; $hisdata->Gems-= 3; $hisdata->Recruits-= 3;
							}
							break;
					case 383: $mydata->Hand = array (1=> 410, 27, 85, 90, 175, 341, 137, 44); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 384: if ($mode == 1) $nextcard = 227; elseif ($mode == 2) $nextcard = 226; elseif ($mode == 3) $nextcard = 225; break;
					case 385: if ($mode == 1) $hisdata->Recruits-= 15; elseif ($mode == 2) $hisdata->Tower-= 10; break;
					case 386: for ($i = 1; $i <= 8; $i++) if (($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Soldier")) OR ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Barbarian")) OR ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Brigand"))) { $mydata->Hand[$i] = $this->DrawCard(array(23, 273, 297), $mydata->Hand, $cardpos, 'DrawCard_list'); $mydata->NewCards[$i] = 1; } for ($i = 1; $i <= 8; $i++) if (($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Soldier")) OR ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Barbarian")) OR ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Brigand"))) { $hisdata->Hand[$i] = $this->DrawCard(array(23, 273, 297), $hisdata->Hand, $cardpos, 'DrawCard_list'); $hisdata->NewCards[$i] = 1; } break;
					case 387: $storage = array();
							for ($i = 1; $i <= 8; $i++)
							{
								$cur_card = $carddb->GetCard($mydata->Hand[$i]);
								if (($cur_card->HasKeyword("Unliving")) AND ($cur_card->GetClass() != 'Rare')) $storage[] = $i;
							}
							if (count($storage) > 0)
							{
								$discarded_pos = $storage[array_rand($storage)];
								if ($carddb->GetCard($mydata->Hand[$discarded_pos])->GetClass() == 'Uncommon') $mydata->Quarry-= 1;
								$mydata->Hand[$discarded_pos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $discarded_pos, 'DrawCard_random');
								$mydata->NewCards[$discarded_pos] = 1;
							}
							
							$storage = array();
							for ($i = 1; $i <= 8; $i++)
							{
								$cur_card = $carddb->GetCard($hisdata->Hand[$i]);
								if (($cur_card->HasKeyword("Unliving")) AND ($cur_card->GetClass() != 'Rare')) $storage[] = $i;
							}
							if (count($storage) > 0)
							{
								$discarded_pos = $storage[array_rand($storage)];
								if ($carddb->GetCard($hisdata->Hand[$discarded_pos])->GetClass() == 'Uncommon') $hisdata->Quarry-= 1;
								$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $discarded_pos, 'DrawCard_random');
								$hisdata->NewCards[$discarded_pos] = 1;
							}
							break;
					case 388: $my_res = $mydata->Bricks + $mydata->Gems + $mydata->Recruits; $his_res = $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits; if (($my_res) >= ($his_res)) { $mydata->Bricks+= round($mydata->Bricks * 0.25); $mydata->Gems+= round($mydata->Gems * 0.25); $mydata->Recruits+= round($mydata->Recruits * 0.25); } if (($my_res) <= ($his_res)) { $hisdata->Bricks+= round($hisdata->Bricks * 0.25); $hisdata->Gems+= round($hisdata->Gems * 0.25); $hisdata->Recruits+= round($hisdata->Recruits * 0.25); } break;
					case 389: $mydata->Tower+= 4; $mydata->Bricks+= round($mydata->Bricks * 0.25); $mydata->Gems+= round($mydata->Gems * 0.25); $mydata->Recruits+= round($mydata->Recruits * 0.25); break;
					case 390: if (!($mylast_card->IsPlayAgainCard() and $mylast_action == 'play')) { $hisdata->Tower-= $hischanges['Tower']; $hisdata->Wall-= $hischanges['Wall']; $hisdata->Quarry-= $hischanges['Quarry']; $hisdata->Magic-= $hischanges['Magic']; $hisdata->Dungeons-= $hischanges['Dungeons']; $hisdata->Bricks-= $hischanges['Bricks']; $hisdata->Gems-= $hischanges['Gems']; $hisdata->Recruits-= $hischanges['Recruits']; } break;
					case 391: $mydata->Tower-= 10; $mydata->Wall+= 20; break;
					case 392: $nextcard = 313; break;
					case 393: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); $mydata->Hand[$mode] = $this->DrawCard(array_merge($carddb->GetList("", "Restoration"), $carddb->GetList("", "Nature")), $mydata->Hand, $cardpos, 'DrawCard_list'); if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 394: $this->Attack(7, $hisdata->Tower, $hisdata->Wall); $mydata->Hand[$mode] = $this->DrawCard($carddb->GetList("", "Alliance"), $mydata->Hand, $cardpos, 'DrawCard_list'); if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 395: if ($mydata->Wall > 7) { $mydata->Wall-= 7; $mydata->Tower+= 13; } else $mydata->Wall+= 7; break;
					case 396: $tmp = $this->KeyWordCount($mydata->Hand, "Holy") + $this->KeyWordCount($mydata->Hand, "Unliving"); $this->Attack($tmp, $hisdata->Tower, $hisdata->Wall); $mydata->Wall+= 3*$tmp; break;
					case 397: $tmp = $this->CountDistinctKeywords($mydata->Hand); $this->Attack($tmp * 8, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= $tmp * 2; $mydata->Gems+= $tmp * 2; $mydata->Recruits+= $tmp * 2; break;
					case 398: $this->Attack(25, $hisdata->Tower, $hisdata->Wall); $mydata->Magic+= 1; $hisdata->Magic-= 1;
							$storage = array(); $j = 0;
							for ($i = 1; $i <= 8; $i++)
								if ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Mage"))
								{
									$storage[$j] = $i;
									$j++;
								}
							if ($j > 0)
							{
								$discarded_pos = $storage[array_rand($storage)];
								$mydata->Hand[$discarded_pos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $discarded_pos, 'DrawCard_random');
								$mydata->NewCards[$discarded_pos] = 1;
								$mydata->Gems+= 30;
							}
							break;
					case 399: $mydata->Tower+= 2; $mydata->Wall+= 3; $nextcard = $this->DrawCard(array_merge($carddb->GetList("Common", "Mage"), $carddb->GetList("Uncommon", "Mage")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 400: if ($mode != $cardpos) $nextcard = $mydata->Hand[$mode]; break;
					case 401: $tmp = round($mydata->Bricks / 5); $mydata->Tower+= $tmp; $mydata->Wall+= $tmp; $tmp = round($mydata->Gems / 5); $hisdata->Bricks-= $tmp; $hisdata->Gems-= $tmp; $hisdata->Recruits-= $tmp; break;
					case 402: $tmp_card = $hislast_card; $mydata->Bricks+= min(round($tmp_card->GetResources("Bricks") / 3), 4); $mydata->Gems+= min(round($tmp_card->GetResources("Gems") / 3), 4); $mydata->Recruits+= min(round($tmp_card->GetResources("Recruits") / 3), 4); break;
					case 403: $mydata->Hand = array (1=> 13, 13, 240, 240, 368, 46, 106, 89); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; $mydata->Dungeons+= 2; break;
					case 404: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); $hisdata->Tower-= 15; $nextcard = 374; break;
					case 405: $tmp = $this->KeywordCount($mydata->Hand, "Alliance"); $mydata->Wall+= $tmp * 5; $mydata->Recruits+= $tmp * 3; break;
					case 406: $b_found = $s_found = false; for ($i = 1; $i <= 8; $i++) if ($i != $cardpos) { if ((!$b_found) AND ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Beast"))) $b_found = true; if ((!$s_found) AND ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Soldier"))) $s_found = true; } if ($b_found AND $s_found) $hisdata->Wall = 0; $this->Attack(35, $hisdata->Tower, $hisdata->Wall); break;
					case 407: $mydata->Tower = 40; $tmp = $this->KeywordCount($mydata->Hand, "Alliance"); if ($tmp > 4) { $mydata->Wall = 150; } else $mydata->Wall+= 50; break;
					case 408: $this->Attack(13, $hisdata->Tower, $hisdata->Wall);
							$storage = array(); $j = 0;
							for ($i = 1; $i <= 8; $i++)
								if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Legend"))
								{
									$storage[$j] = $i;
									$j++;
								}
							if ($j > 0)
							{
								$discarded_pos = $storage[array_rand($storage)];
								$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $discarded_pos, 'DrawCard_random');
								$hisdata->NewCards[$discarded_pos] = 1;
								$hisdata->Magic-= 1;
							}
							break;
					case 409: $this->Attack(14, $hisdata->Tower, $hisdata->Wall);
							$storage = array(); $j = 0;
							for ($i = 1; $i <= 8; $i++)
								if (($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Dragon")) AND ($carddb->GetCard($hisdata->Hand[$i])->GetClass() == "Rare"))
								{
									$storage[$j] = $i;
									$j++;
								}
							if ($j > 0)
							{
								$discarded_pos = $storage[array_rand($storage)];
								$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $discarded_pos, 'DrawCard_random');
								$hisdata->NewCards[$discarded_pos] = 1;
								$nextcard = 131;
							}
							break;
					case 410: $found = false; for ($i = 1; $i <= 8; $i++) if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Holy")) { $found = true; break; } if (!$found) { $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; $hisdata->Bricks-= 1; $hisdata->Gems-= 1; $hisdata->Recruits-= 1; } else $hisdata->Tower-= 1; break;
					case 411: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); if (($mylast_card->HasKeyword("Beast")) and ($mylast_action == 'play')) $mydata->Recruits+= 3; break;
					case 412: if (isset($mynewflags[$cardpos])) $hisdata->Wall-= 5; else { $hisdata->Bricks-= 4; $hisdata->Gems-= 4; $hisdata->Recruits-= 4; } break;
					case 413: if ($mydata->Recruits < 8) $mydata->Recruits = ($mydata->Recruits * 2); else $mydata->Recruits+= 5; break;
					case 414: $dis_card = $carddb->GetCard($hisdata->Hand[$mode]); $hisdata->Hand[$mode] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $mode, 'DrawCard_random'); $hisdata->NewCards[$mode] = 1; $resources = array('Quarry' => 'Bricks', 'Magic' => 'Gems', 'Dungeons' => 'Recruits'); foreach ($resources as $facility => $resource) { $mydata->$resource-= $dis_card->GetResources($resource); if ($mydata->$resource < 0) $mydata->$facility-= 1; } break;
					case 415: $this->Attack(4, $hisdata->Tower, $hisdata->Wall);
							$storage = array();
							for ($i = 1; $i <= 8; $i++)
							{
								$current_card = $carddb->GetCard($hisdata->Hand[$i]);
								if (($current_card->HasKeyword("Alliance")) AND ($current_card->GetClass() != 'Rare')) $storage[] = $i;
							}
							
							if (count($storage) > 0)
							{
								$selected_pos = $storage[array_rand($storage)];
								$hisdata->Hand[$selected_pos] = 415;
								$hisdata->NewCards[$selected_pos] = 1;
							} break;
					case 416: if (isset($mynewflags[$cardpos])) $hisdata->Tower-= 6; else $this->Attack(6, $hisdata->Tower, $hisdata->Wall); break;
					case 417: if (isset($mynewflags[$cardpos])) { $mydata->Tower+= 10; $mydata->Bricks+= 3; } else $mydata->Tower+= 7; break;
					case 418: $mydata->Tower-= 8; if (isset($mynewflags[$cardpos])) $mydata->Magic+= 1; else $mydata->Gems+= 12; break;
					case 419: $my_temp = $his_temp = array();
							for ($i = 1; $i <= 8; $i++)
							{
								if ($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Unliving")) $my_temp[$i] = $i;
								if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Unliving")) $his_temp[$i] = $i;
							}
							shuffle($my_temp); shuffle($his_temp);
							if (count($my_temp) > 0)
							{
								for ($i = 0; ($i < count($my_temp)) AND ($i < 6); $i++)
								{
									$mydata->Hand[$my_temp[$i]] = $this->DrawCard($mydata->Deck, $mydata->Hand, $my_temp[$i], 'DrawCard_random');
									$mydata->NewCards[$my_temp[$i]] = 1;
								}
								$amount = max(($i - 4), 0); $mydata->Bricks-= $amount * 10; $mydata->Quarry-= $amount;
							}
							if (count($his_temp) > 0)
							{
								for ($j = 0; ($j < count($his_temp)) AND ($j < 6); $j++)
								{
									$hisdata->Hand[$his_temp[$j]] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $his_temp[$j], 'DrawCard_random');
									$hisdata->NewCards[$his_temp[$j]] = 1;
								}
								$amount = max(($j - 4), 0); $hisdata->Bricks-= $amount * 10; $hisdata->Quarry-= $amount;
							} break;
					case 420: if (($mydata->Wall % 10) == 0) {$mydata->Wall+= 9; $mydata->Gems+= 4; } else $mydata->Wall+= 4; break;
					case 421: $maximum = max($hisdata->Quarry, $hisdata->Magic, $hisdata->Dungeons);
						$facilities = array('Quarry' => 'Bricks', 'Magic' => 'Gems', 'Dungeons' => 'Recruits'); $temp = array();
						foreach ($facilities as $facility => $resource) if ($hisdata->$facility == $maximum) $temp[] = $facility;
						$chosen_facility = $temp[array_rand($temp)];
						$mydata->$facilities[$chosen_facility]+= $hisdata->$chosen_facility;
							  break;
					case 422: $mydata->Bricks-= 3; $mydata->Gems-= 3; $mydata->Recruits-= 3; $nextcard = $this->DrawCard(array(15, 80, 422), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 423: $nextcard = $this->DrawCard($carddb->GetList("", "Brigand"), $mydata->Hand, $cardpos, 'DrawCard_list'); if ($this->KeywordCount($mydata->Hand, "Undead") > 0) { $mydata->Bricks+= 2; $mydata->Gems+= 2; $mydata->Recruits+= 2; $hisdata->Bricks-= 2; $hisdata->Gems-= 2; $hisdata->Recruits-= 2; } break;
					case 424: $this->Attack(11, $hisdata->Tower, $hisdata->Wall);
							$resources = array('Quarry' => 'Bricks', 'Magic' => 'Gems', 'Dungeons' => 'Recruits');
							foreach ($resources as $facility => $resource)
							{
								$mydata->$resource+= $hisdata->$facility;
								$hisdata->$resource-= $hisdata->$facility;
							}
							$nextcard = $this->DrawCard(array_merge($carddb->GetList("Uncommon", "Brigand"), $carddb->GetList("Rare", "Brigand")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 425: $my_counter = $his_counter = 0;
							for ($i = 1; $i <= 8; $i++)
							{
								if (($carddb->GetCard($mydata->Hand[$i])->HasKeyword("Mage")) AND ($i != $cardpos))
								{
									$mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $i, 'DrawCard_random');
									$mydata->NewCards[$i] = 1;
									$my_counter++;
								}
								if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Mage"))
								{
									$hisdata->Hand[$i] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $i, 'DrawCard_random');
									$hisdata->NewCards[$i] = 1;
									$his_counter++;
								}
							}
							$mydata->Tower+= $my_counter * 5; $mydata->Wall+= $my_counter * 10; $mydata->Gems+= $my_counter * 5;
							$hisdata->Tower+= $his_counter * 5; $hisdata->Wall+= $his_counter * 10; $hisdata->Gems+= $his_counter * 5;
							break;
					case 426: $this->Attack(15, $hisdata->Tower, $hisdata->Wall); break;
					case 427: $recruits_production*= 10; break;
					case 428: $this->Attack(150, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 20; break;
					case 429: $my_count = $this->KeywordCount($mydata->Hand, "Alliance") + $this->KeywordCount($mydata->Hand, "Legend") + $this->KeywordCount($mydata->Hand, "Mage"); $his_count = $this->KeywordCount($hisdata->Hand, "Alliance") + $this->KeywordCount($hisdata->Hand, "Legend") + $this->KeywordCount($hisdata->Hand, "Mage");
							if ($my_count >= $his_count)
							{
								$min = min($mydata->Quarry, $mydata->Magic, $mydata->Dungeons);
								$facilities = array('Quarry', 'Magic', 'Dungeons'); $temp = array();
								foreach ($facilities as $facility) if ($mydata->$facility == $min) $temp[] = $facility;
								$chosen_facility = $temp[array_rand($temp)];
								$mydata->$chosen_facility+= 1;
							}
							if ($my_count <= $his_count)
							{
								$min = min($hisdata->Quarry, $hisdata->Magic, $hisdata->Dungeons);
								$facilities = array('Quarry', 'Magic', 'Dungeons'); $temp = array();
								foreach ($facilities as $facility) if ($hisdata->$facility == $min) $temp[] = $facility;
								$chosen_facility = $temp[array_rand($temp)];
								$hisdata->$chosen_facility+= 1;
							} break;
					case 430: $nextcard = $this->DrawCard(array_diff($carddb->GetList("Uncommon"), $mydata->Deck->Uncommon), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 431: $my_count = $this->KeywordCount($mydata->Hand, "Holy"); $his_count = $this->KeywordCount($hisdata->Hand, "Holy"); if ($my_count >= $his_count) { $mydata->Tower+= 10; $mydata->Wall+= 20; } if ($my_count <= $his_count) { $hisdata->Tower+= 10; $hisdata->Wall+= 20; } break;
					case 432: $my_count = $mydata->Bricks + $mydata->Gems + $mydata->Recruits; $his_count = $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits; if ($my_count >= $his_count) { $mydata->Quarry+= 1; $mydata->Magic+= 1; $mydata->Dungeons+= 1; $hisdata->Bricks+= 20; $hisdata->Gems+= 20; $hisdata->Recruits+= 20; } if ($my_count <= $his_count) { $hisdata->Quarry+= 1; $hisdata->Magic+= 1; $hisdata->Dungeons+= 1; $mydata->Bricks+= 20; $mydata->Gems+= 20; $mydata->Recruits+= 20; } break;
					case 433: $my_low = $my_high = $his_low = $his_high = $my_costs = $his_costs = array();
							$my_min = $his_min = 1000; $my_max = $his_max = 0;
							for ($i = 1; $i <= 8; $i++)
							{
								if ($i != $cardpos)
								{
									$cur_cost = $my_costs[$i] = $carddb->GetCard($mydata->Hand[$i])->GetResources("");
									if ($cur_cost < $my_min) $my_min = $cur_cost;
									if ($cur_cost > $my_max) $my_max = $cur_cost;
								}
								$cur_cost = $his_costs[$i] = $carddb->GetCard($hisdata->Hand[$i])->GetResources("");
								if ($cur_cost < $his_min) $his_min = $cur_cost;
								if ($cur_cost > $his_max) $his_max = $cur_cost;
							}
							for ($i = 1; $i <= 8; $i++)
							{
								if ($i != $cardpos)
								{
									$cur_cost = $my_costs[$i];
									if ($cur_cost == $my_min) $my_low[$i] = $i;
									elseif ($cur_cost == $my_max) $my_high[$i] = $i;
								}
								$cur_cost = $his_costs[$i];
								if ($cur_cost == $his_min) $his_low[$i] = $i;
								elseif ($cur_cost == $his_max) $his_high[$i] = $i;
							}
							$my_dis = array_rand($my_low);
							$his_dis = array_rand($his_low);
							$mydata->Hand[$my_dis] = $this->DrawCard($mydata->Deck, $mydata->Hand, $my_dis, 'DrawCard_random');
							$mydata->NewCards[$my_dis] = 1;
							$hisdata->Hand[$his_dis] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $his_dis, 'DrawCard_random');
							$hisdata->NewCards[$his_dis] = 1;
							if ($my_max > 0)
							{
								$my_dis = array_rand($my_high);
								$mydata->Hand[$my_dis] = $this->DrawCard($mydata->Deck, $mydata->Hand, $my_dis, 'DrawCard_random');
								$mydata->NewCards[$my_dis] = 1;
							}
							if ($his_max > 0)
							{
								$his_dis = array_rand($his_high);
								$hisdata->Hand[$his_dis] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $his_dis, 'DrawCard_random');
								$hisdata->NewCards[$his_dis] = 1;
							} break;
					case 434: if (($mydata->Recruits - $hisdata->Recruits) > 52) { $mydata->Recruits = $hisdata->Recruits; $hisdata->Tower = 1; $hisdata->Wall = 0; } break;
					case 435: $mydata->Gems+= 50; $mydata->Magic+= 1; break;
					case 436: $mydata->Tower+= 16; if ($this->KeywordCount($mydata->Hand, "Soldier") > 0) $hisdata->Tower-= 16; else $hisdata->Tower-= 10; break;
					case 437: $mydata->Tower+= 7; if ($this->KeywordCount($mydata->Hand, "Soldier") > 0) $hisdata->Tower-= 4; break;
					case 438: if ($this->KeywordCount($mydata->Hand, "Burning") == 2) $nextcard = 223; else $mydata->Gems+= 14; break;
					case 439: $my_count = $this->KeywordCount($mydata->Hand, "Undead"); $his_count = $this->KeywordCount($hisdata->Hand, "Undead");
							if ($my_count <= $his_count)
							{
								$max = max($mydata->Quarry, $mydata->Magic, $mydata->Dungeons);
								$facilities = array('Quarry', 'Magic', 'Dungeons'); $temp = array();
								foreach ($facilities as $facility) if ($mydata->$facility == $max) $temp[] = $facility;
								$chosen_facility = $temp[array_rand($temp)];
								$mydata->$chosen_facility-= 1;
							}
							if ($my_count >= $his_count)
							{
								$max = max($hisdata->Quarry, $hisdata->Magic, $hisdata->Dungeons);
								$facilities = array('Quarry', 'Magic', 'Dungeons'); $temp = array();
								foreach ($facilities as $facility) if ($hisdata->$facility == $max) $temp[] = $facility;
								$chosen_facility = $temp[array_rand($temp)];
								$hisdata->$chosen_facility-= 1;
							} break;
					
				}
				
				//begin keyword processing
				
				//begin order independent keywords
				
				//process Durable cards - they stays on hand
				if ($card->HasKeyWord("Durable"))
					$nextcard = $cardid;
				
				//process Quick cards - play again with no production
				if ($card->HasKeyWord("Quick"))
				{
					$nextplayer = $playername;
					$bricks_production = 0;
					$gems_production = 0;
					$recruits_production = 0;
				}
				
				//process Swift cards - play again with production
				if ($card->HasKeyWord("Swift"))
				{
					$nextplayer = $playername;
				}
				
				//process Unliving cards - Bricks cost return
				if ($card->HasKeyWord("Unliving"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Unliving") - 1; // we don't count the played card
					$token_index = array_search("Unliving", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 8;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$mydata->Bricks+= round($card->CardData->Bricks / 2);
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Soldier cards - Recruits cost return
				if ($card->HasKeyWord("Soldier"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Soldier") - 1; // we don't count the played card
					$token_index = array_search("Soldier", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 8;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$mydata->Recruits+= round($card->CardData->Recruits / 2);
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Mage cards - Gems cost return
				if ($card->HasKeyWord("Mage"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Mage") - 1; // we don't count the played card
					$token_index = array_search("Mage", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 8;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$mydata->Gems+= round($card->CardData->Gems / 2);
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Undead cards - Upgrades random undead card
				if ($card->HasKeyWord("Undead"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Undead") - 1; // we don't count the played card
					$token_index = array_search("Undead", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 6;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$storage = array();
							for ($i = 1; $i <= 8; $i++)
							{
								$current_card = $carddb->GetCard($mydata->Hand[$i]);
								if (($current_card->HasKeyword("Undead")) AND ($current_card->GetClass() != 'Rare') AND ($i != $cardpos))
									$storage[$i] = $i;
							}
							
							if (count($storage) > 0)
							{
								$upgrades = array("Common" => "Uncommon", "Uncommon" => "Rare");
								
								$upgrade_pos = array_rand($storage);
								$upg_rarity = $carddb->GetCard($mydata->Hand[$upgrade_pos])->GetClass();
								$mydata->Hand[$upgrade_pos] = $this->DrawCard($carddb->GetList($upgrades[$upg_rarity], "Undead"), $mydata->Hand, $cardpos, 'DrawCard_list');
								$mydata->NewCards[$upgrade_pos] = 1;
							}
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Burning cards - Discard one card from enemy hand and do additional damage to enemy tower
				if ($card->HasKeyWord("Burning"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Burning") - 1; // we don't count the played card
					$token_index = array_search("Burning", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 8;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$storage = array();
							
							for ($i = 1; $i <= 8; $i++)
							{
								// pick only non Burning cards
								if (!$carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Burning")) $storage[$i] = $i;
							}
							
							if (count($storage) > 0)
							{
								$discarded_pos = array_rand($storage);
								$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $discarded_pos, 'DrawCard_random');
								$hisdata->NewCards[$discarded_pos] = 1;
								$damage = array("Common" => 1, "Uncommon" => 3, "Rare" => 5);
								$hisdata->Tower-= $damage[$card->GetClass()];
							}
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Holy cards - Discarding one random undead card from enemy hand and get additional stock
				if ($card->HasKeyWord("Holy"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Holy") - 1; // we don't count the played card
					$token_index = array_search("Holy", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 12;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$storage = array();
							for ($i = 1; $i <= 8; $i++) if ($carddb->GetCard($hisdata->Hand[$i])->HasKeyword("Undead")) $storage[$i] = $i;
							if (count($storage) > 0)
							{
								$discarded_pos = array_rand($storage);
								$dis_rarity = $carddb->GetCard($hisdata->Hand[$discarded_pos])->GetClass();
								$hisdata->Hand[$discarded_pos] = 381;
								$hisdata->NewCards[$discarded_pos] = 1;
								$stock = array("Common" => 1, "Uncommon" => 2, "Rare" => 3);
								$gained = $stock[$dis_rarity];
								$mydata->Bricks+= $gained;
								$mydata->Gems+= $gained;
								$mydata->Recruits+= $gained;
							}
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Brigand cards - Steal additional stock
				if ($card->HasKeyWord("Brigand"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Brigand") - 1; // we don't count the played card
					$token_index = array_search("Brigand", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 7;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$stock = array("Common" => 1, "Uncommon" => 2, "Rare" => 3);
							$gained = $stock[$card->GetClass()];
							$mydata->Bricks+= $gained;
							$mydata->Gems+= $gained;
							$mydata->Recruits+= $gained;
							$hisdata->Bricks-= $gained;
							$hisdata->Gems-= $gained;
							$hisdata->Recruits-= $gained;
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Barbarian cards - Additional damage to enemy wall
				if ($card->HasKeyWord("Barbarian"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Barbarian") - 1; // we don't count the played card
					$token_index = array_search("Barbarian", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 9;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$damage = array("Common" => 3, "Uncommon" => 8, "Rare" => 15);
							$hisdata->Wall-= $damage[$card->GetClass()];
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Beast cards - Additional damage to enemy
				if ($card->HasKeyWord("Beast"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Beast") - 1; // we don't count the played card
					$token_index = array_search("Beast", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 8;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$damage = array("Common" => 2, "Uncommon" => 5, "Rare" => 10);
							$this->Attack($damage[$card->GetClass()], $hisdata->Tower, $hisdata->Wall);
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Dragon cards - chance for getting a rare dragon card (only if played card wasn't a rare dragon)
				if ($card->HasKeyWord("Dragon"))
				{
					if (($card->GetClass() != "Rare") AND (mt_rand(1, 100) <= 11))
					{
						$nextcard = $this->DrawCard($carddb->GetList("Rare", "Dragon"), $mydata->Hand, $cardpos, 'DrawCard_list');
					}
				}
				
				//process Rebirth cards - if there are enough Burning cards in game the card stays on hand and player get additional gems
				if ($card->HasKeyWord("Rebirth"))
				{
					if (($this->KeywordCount($mydata->Hand, "Burning") + $this->KeywordCount($hisdata->Hand, "Burning")) > 3)
					{
						$nextcard = $cardid;
						$mydata->Gems+= 16;
					}
				}
				
				//process Flare attack cards - place searing fire cards to both players hands (odd and even positions randomly selected)
				if ($card->HasKeyWord("Flare attack"))
				{
					$selector = mt_rand(0,1);
					for ($i = 1; $i <= 4; $i++)
					{
						// current index (odd and even positions)
						$mine = 2*$i - $selector;
						$his = 2*$i - (1 - $selector);
						
						$mytarget = $carddb->GetCard($mydata->Hand[$mine]);
						$histarget = $carddb->GetCard($hisdata->Hand[$his]);
						
						$my_rarity = $mytarget->GetClass();
						$his_rarity = $histarget->GetClass();
						
						// played card position is ignored, does not discard burning cards (rares cards can only be rares)
						if (($mine != $cardpos) AND (!$mytarget->HasKeyword("Burning")) AND (($my_rarity != 'Rare') OR ($my_rarity == $card->GetClass())))
						{
							$mydata->Hand[$mine] = 248;
							$mydata->NewCards[$mine] = 1;
						}
						
						if ((!$histarget->HasKeyword("Burning")) AND (($his_rarity != 'Rare') OR ($his_rarity == $card->GetClass())))
						{
							$hisdata->Hand[$his] = 248;
							$hisdata->NewCards[$his] = 1;
						}
					}
				}
				
				//process Banish cards - discard one random Durable card from enemy hand, if there is one
				if ($card->HasKeyWord("Banish"))
				{
					// target card is discarded only if it has same or lower rarity then the played card
					$rarities = array("Common" => 0, "Uncommon" => 1, "Rare" => 2);
					$storage = array("Common" => array(), "Uncommon" => array(), "Rare" => array());
					$played_rank = $rarities[$card->GetClass()];
					
					for ($i = 1; $i <= 8; $i++)
					{
						$dis_card = $carddb->GetCard($hisdata->Hand[$i]);
						$dis_class = $dis_card->GetClass();
						$dis_rank = $rarities[$dis_class];
						
						// pick only cards that can be discarded by played card
						if (($dis_card->HasKeyword("Durable")) AND ($dis_rank <= $played_rank)) $storage[$dis_class][] = $i;
					}
					
					if ((count($storage['Common']) + count($storage['Uncommon']) + count($storage['Rare'])) > 0)
					{
						// pick preferably cards with higher rarity, but choose random card within the rarity group
						shuffle($storage['Common']); shuffle($storage['Uncommon']); shuffle($storage['Rare']);
						$storage_temp = array_merge($storage['Common'], $storage['Uncommon'], $storage['Rare']);
						$discarded_pos = array_pop($storage_temp);
						$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $discarded_pos, 'DrawCard_random');
						$hisdata->NewCards[$discarded_pos] = 1;
					}
				}
				
				//process Titan cards - Draw a Titan card
				if ($card->HasKeyWord("Titan"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Titan") - 1; // we don't count the played card
					$token_index = array_search("Titan", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 9;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$nextcard = $this->DrawCard($carddb->GetList("", "Titan"), $mydata->Hand, $cardpos, 'DrawCard_list');
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Alliance cards - Additional Production X2
				if ($card->HasKeyWord("Alliance"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Alliance") - 1; // we don't count the played card
					$token_index = array_search("Alliance", $mydata->TokenNames);
					
					if ($token_index)
					{
						$mydata->TokenValues[$token_index]+= $ammount * 8;
						
						if ($mydata->TokenValues[$token_index] >= 100)
						{
							$bricks_production*= 2;
							$gems_production*= 2;
							$recruits_production*= 2;
							
							$mydata->TokenValues[$token_index] = 0;
						}
					}
				}
				
				//process Legend cards - chance for getting additional facility
				if ($card->HasKeyWord("Legend"))
				{
					if (mt_rand(1, 100) <= 25)
					{
						$minimum = min($mydata->Quarry, $mydata->Magic, $mydata->Dungeons);
						$facilities = array("Quarry" => $mydata->Quarry, "Magic" => $mydata->Magic, "Dungeons" => $mydata->Dungeons);
						$temp = array();
						foreach ($facilities as $facility => $f_value)
							if ($f_value == $minimum) $temp[$facility] = $f_value;
						$chosen = array_rand($temp);						
						$mydata->$chosen++;
					}
				}
				
				//process Skirmisher cards - discard one random Charge card from enemy hand, if there is one
				if ($card->HasKeyWord("Skirmisher"))
				{
					// target card is discarded only if it has same or lower rarity then the played card
					$rarities = array("Common" => 0, "Uncommon" => 1, "Rare" => 2);
					$storage = array("Common" => array(), "Uncommon" => array(), "Rare" => array());
					$played_rank = $rarities[$card->GetClass()];
					
					for ($i = 1; $i <= 8; $i++)
					{
						$dis_card = $carddb->GetCard($hisdata->Hand[$i]);
						$dis_class = $dis_card->GetClass();
						$dis_rank = $rarities[$dis_class];
						
						// pick only cards that can be discarded by played card
						if (($dis_card->HasKeyword("Charge")) AND ($dis_rank <= $played_rank)) $storage[$dis_class][] = $i;
					}
					
					if ((count($storage['Common']) + count($storage['Uncommon']) + count($storage['Rare'])) > 0)
					{
						// pick preferably cards with higher rarity, but choose random card within the rarity group
						shuffle($storage['Common']); shuffle($storage['Uncommon']); shuffle($storage['Rare']);
						$storage_temp = array_merge($storage['Common'], $storage['Uncommon'], $storage['Rare']);
						$discarded_pos = array_pop($storage_temp);
						$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $discarded_pos, 'DrawCard_random');
						$hisdata->NewCards[$discarded_pos] = 1;
					}
				}
				
				//end order independent keywords
				
				//begin order dependent keywords
				
				//process Enduring cards - if last card played was the same card, bonus attack
				if ($card->HasKeyWord("Enduring"))
				{
					if (($mydata->LastCard[$mylastcardindex] == $cardid) AND ($mylast_action == 'play'))
					{
						$bonus_damage = $this->KeywordValue($card->GetKeywords(), 'Enduring');
						$this->Attack($bonus_damage, $hisdata->Tower, $hisdata->Wall);
					}
				}
				
				//process Charge cards - if enemy wall is 0, bonus damage to enemy tower
				if ($card->HasKeyWord("Charge"))
				{
					$charge_damage = $this->KeywordValue($card->GetKeywords(), 'Charge');
					if ($hisdata->Wall == 0) $hisdata->Tower-= $charge_damage;
				}
				
				//end order dependent keywords
				
				//end keyword processing
				
				//process discarded cards
				$mydiscards_index = count($mydata->DisCards[0]);
				$hisdiscards_index = count($mydata->DisCards[1]);
				
				//compute and store the discarded cards
				//we don't need to take into account the position of the played card. It hasn't been proccessed yet. In other words if it was discarded we know it, because the newcards flag was set, if not then newcards flag isn't set yet.
				for ($i = 1; $i <= 8; $i++)
				{
					//this last condition makes sure that played card which discards itself from hand will not get into discarded cards
					if( ((!isset($mynewcards[$i]) and isset($mydata->NewCards[$i])) or $myhand[$i] != $mydata->Hand[$i]) and $i != $cardpos )
					{
						$mydiscards_index++;
						$mydata->DisCards[0][$mydiscards_index] = $myhand[$i];
					}
					
					if (((!(isset($hisnewcards[$i]))) and (isset($hisdata->NewCards[$i]))) or ($hishand[$i] != $hisdata->Hand[$i]))
					{
						$hisdiscards_index++;
						$mydata->DisCards[1][$hisdiscards_index] = $hishand[$i];
					}
					
				}
				
				// apply game limits and compute the changes
				$mydata_copy = $hisdata_copy = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				
				// create a copy of all game attributes
				foreach ($mydata_copy as $attribute => $value)
				{
					$mydata_copy[$attribute] = $mydata->$attribute;
					$hisdata_copy[$attribute] = $hisdata->$attribute;
				}
				
				// apply game limits to copy of attributes
				$mydata_copy = $this->ApplyGameLimits($mydata_copy);
				$hisdata_copy = $this->ApplyGameLimits($hisdata_copy);
				
				// apply limits to game attributes
				foreach ($mydata_copy as $attribute => $value)
				{
					$mydata->$attribute = $mydata_copy[$attribute];
					$hisdata->$attribute = $hisdata_copy[$attribute];
				}
				
				// production is applied to copy of the game attributes only (for changes array needs), production factor is descresed because normal production is a default card effect, thus it doesn't need to be highlighted - only abnormal productions (production X0, X2, X3...) are displayed via changes array
				$mydata_copy['Bricks']+= ($bricks_production - 1) * $mydata->Quarry;
				$mydata_copy['Gems']+= ($gems_production - 1) * $mydata->Magic;
				$mydata_copy['Recruits']+= ($recruits_production - 1) * $mydata->Dungeons;
				
				// add the new difference to the changes arrays
				foreach ($mydata_temp as $attribute => $value)
				{
					$mydata->Changes[$attribute] += $mydata_copy[$attribute] - $mydata_temp[$attribute];
					$hisdata->Changes[$attribute] += $hisdata_copy[$attribute] - $hisdata_temp[$attribute];
				}
				
				// compute changes on token counters
				foreach ($mytokens_temp as $index => $token_val)
				{
					$mydata->TokenChanges[$index] += $mydata->TokenValues[$index] - $mytokens_temp[$index];
					$hisdata->TokenChanges[$index] += $hisdata->TokenValues[$index] - $histokens_temp[$index];
				}
				
				// apply limits to token counters
				foreach ($mytokens_temp as $index => $token_val)
				{
					$mydata->TokenValues[$index] = max(min($mydata->TokenValues[$index], 100), 0);
					$hisdata->TokenValues[$index] = max(min($hisdata->TokenValues[$index], 100), 0);
				}
			}
			
			// add production at the end of turn
			$mydata->Bricks+= $bricks_production * $mydata->Quarry;
			$mydata->Gems+= $gems_production * $mydata->Magic;
			$mydata->Recruits+= $recruits_production * $mydata->Dungeons;
										
			// draw card at the end of turn
			if( $nextcard > 0 )
			{// value was decided by a card effect
				$mydata->Hand[$cardpos] = $nextcard;
			}
			elseif( $nextcard == 0 )
			{// drawing was disabled entirely by a card effect
			}
			elseif( $nextcard == -1 )
			{// normal drawing
				if (($action == 'play') AND ($card->IsPlayAgainCard())) $drawfunc = 'DrawCard_norare';
				elseif ($action == 'play') $drawfunc = 'DrawCard_random';
				else $drawfunc = 'DrawCard_different';
				
				$mydata->Hand[$cardpos] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, $drawfunc);
			}
			
			// store info about this current action, updating history as needed
			if ($mylast_card->IsPlayAgainCard() and $mylast_action == 'play') 
			{
				// preserve history when the previously played card was a "play again" card
				$mylastcardindex++;
			}
			else
			{
				// otherwise erase the old history and start a new one
				$mydata->LastCard = null;
				$mydata->LastMode = null;
				$mydata->LastAction = null;
				$mylastcardindex = 1;
			}
			
			// record the current action in history
			$mydata->LastCard[$mylastcardindex] = $cardid;
			$mydata->LastMode[$mylastcardindex] = $mode;
			$mydata->LastAction[$mylastcardindex] = $action;
			$mydata->NewCards[$cardpos] = 1; //TODO: this shouldn't apply everytime
			
			// check victory conditions (in this predetermined order)
			if(     $mydata->Tower > 0 and $hisdata->Tower <= 0 )
			{	// tower destruction victory - player
				$data->Winner = $playername;
				$data->Outcome = 'Tower destruction victory';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower <= 0 and $hisdata->Tower > 0 )
			{	// tower destruction victory - opponent
				$data->Winner = $opponent;
				$data->Outcome = 'Tower destruction victory';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower <= 0 and $hisdata->Tower <= 0 )
			{	// tower destruction victory - draw
				$data->Winner = '';
				$data->Outcome = 'Draw';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower >= 100 and $hisdata->Tower < 100 )
			{	// tower building victory - player
				$data->Winner = $playername;
				$data->Outcome = 'Tower building victory';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower < 100 and $hisdata->Tower >= 100 )
			{	// tower building victory - opponent
				$data->Winner = $opponent;
				$data->Outcome = 'Tower building victory';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower >= 100 and $hisdata->Tower >= 100 )
			{	// tower building victory - draw
				$data->Winner = '';
				$data->Outcome = 'Draw';
				$this->State = 'finished';
			}
			elseif( ($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= 400 and !(($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= 400) )
			{	// resource accumulation victory - player
				$data->Winner = $playername;
				$data->Outcome = 'Resource accumulation victory';
				$this->State = 'finished';
			}
			elseif( ($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= 400 and !(($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= 400) )
			{	// resource accumulation victory - opponent
				$data->Winner = $opponent;
				$data->Outcome = 'Resource accumulation victory';
				$this->State = 'finished';
			}
			elseif( ($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= 400 and ($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= 400 )
			{	// resource accumulation victory - draw
				$data->Winner = '';
				$data->Outcome = 'Draw';
				$this->State = 'finished';
			}
			elseif( $data->Round >= 250 )
			{	// timeout victory
				$data->Outcome = 'Timeout victory';
				$this->State = 'finished';
				
				// compare towers
				if    ( $mydata->Tower > $hisdata->Tower ) $data->Winner = $playername;
				elseif( $mydata->Tower < $hisdata->Tower ) $data->Winner = $opponent;
				// compare walls
				elseif( $mydata->Wall > $hisdata->Wall ) $data->Winner = $playername;
				elseif( $mydata->Wall < $hisdata->Wall ) $data->Winner = $opponent;
				// compare facilities
				elseif( $mydata->Quarry + $mydata->Magic + $mydata->Dungeons > $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons ) $data->Winner = $playername;
				elseif( $mydata->Quarry + $mydata->Magic + $mydata->Dungeons < $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons ) $data->Winner = $opponent;
				// compare resources
				elseif( $mydata->Bricks + $mydata->Gems + $mydata->Recruits > $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits ) $data->Winner = $playername;
				elseif( $mydata->Bricks + $mydata->Gems + $mydata->Recruits < $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits ) $data->Winner = $opponent;
				// else draw
				else
				{
					$data->Winner = '';
					$data->Outcome = 'Draw';
				}
			}
			else
			{	//game continues
				$data->Current = $nextplayer;
				$data->Timestamp = time();
				if( $nextplayer != $playername )
					$data->Round++;
			}
			
			return 'OK';
		}
		
		private function KeywordCount(array $hand, $keyword)
		{
			global $carddb;
			
			$count = 0;
			
			foreach ($hand as $cardid)
				if ($carddb->GetCard($cardid)->HasKeyword($keyword))
					$count++;
			
			return $count;
		}
		
		private function KeywordValue($keywords, $target_keyword)
		{
			$result = preg_match('/'.$target_keyword.' \((\d+)\)/', $keywords, $matches);
			if ($result == 0) return 0;
			
			return (int)$matches[1];
		}
		
		private function CountDistinctKeywords(array $hand)
		{
			global $carddb;
			
			$first = true;
			$keywords_list = "";
			
			foreach ($hand as $cardid)
			{
				$keyword = $carddb->GetCard($cardid)->GetKeywords();
				if ($keyword != "") // ignore cards with no keywords
					if ($first)
					{
						$keywords_list = $keyword;
						$first = false;
					}
					else $keywords_list.= " ".$keyword;
			}
			
			if ($keywords_list == "") return 0; // no keywords in hand
			
			$words = preg_split("/\. ?/", $keywords_list, -1, PREG_SPLIT_NO_EMPTY); // split individual keywords
			foreach($words as $word)
			{
				$word = preg_split("/ \(/", $word, 0); // remove parameter if present
				$word = $word[0];
				$keywords[$word] = $word; // removes duplicates
			}
			
			return count($keywords);
		}
		
		// returns one card at type-random from the specified source with the specified draw function
		private function DrawCard($source, array $hand, $card_pos, $draw_function)
		{
			while (1)
			{
				$nextcard = $this->$draw_function($source, $hand[$card_pos]);
				
				// count the number of occurences of the same card on other slots
				$match = 0;
				for ($i = 1; $i <= 8; $i++)
					if (($hand[$i] == $nextcard) and ($card_pos != $i))
						$match++; //do not count the card already played
				
				if (mt_rand(1, pow(2, $match)) == 1) return $nextcard; // chance to retain the card decreases exponentially as the number of matches increases
			}
			
		}
		
		// returns new hand from the specified source with the specified draw function
		private function DrawHand($source, $draw_function)
		{
			$hand = array(1=> 0, 0, 0, 0, 0, 0, 0, 0);
			//card position is in this case irrelevant - send current position (it contains empty slot anyway)
			for ($i = 1; $i <= 8; $i++) $hand[$i] = $this->DrawCard($source, $hand, $i, $draw_function);
 			return $hand;
 		}
		
		// returns one card at type-random from the specified deck
		private function DrawCard_random(CDeckData $deck)
		{
			$i = mt_rand(1, 100);
			if     ($i <= 65) return $deck->Common[mt_rand(1, 15)]; // common
			elseif ($i <= 65 + 29) return $deck->Uncommon[mt_rand(1, 15)]; // uncommon
			elseif ($i <= 65 + 29 + 6) return $deck->Rare[mt_rand(1, 15)]; // rare
		}
		
		// returns one card at type-random from the specified deck, different from those on your hand
		private function DrawCard_different(CDeckData $deck, $cardid)
		{
			do
				$nextcard = $this->DrawCard_random($deck);
			while( $nextcard == $cardid );

			return $nextcard;
		}
		
		// returns one card at type-random from the specified deck - no rare
		private function DrawCard_norare(CDeckData $deck)
		{
			$i = mt_rand(1, 94);
			if ($i <= 65) return $deck->Common[mt_rand(1, 15)]; // common
			else return $deck->Uncommon[mt_rand(1, 15)]; // uncommon
		}
		
		// returns one card at random from the specified list of card ids
		private function DrawCard_list(array $list)
		{
			return $list[array_rand($list)];
		}
		
		// returns a new hand consisting of type-random cards chosen from the specified deck
		private function DrawHand_random(CDeckData $deck)
		{
			return $this->DrawHand($deck, 'DrawCard_random');
		}
		
		// returns a starting hand
		private function DrawHand_initial(CDeckData $deck)
		{
			return $this->DrawHand($deck, 'DrawCard_norare');
		}
		
		// returns a new hand consisting of random cards from the specified list of card ids
		private function DrawHand_list(array $list)
		{
			return $this->DrawHand($list, 'DrawCard_list');
		}
		
		// performs an attack - first reducing wall, then tower
		// may lower both values below 0
		private function Attack($atk, &$tower, &$wall)
		{
			$damage = $atk;
			
			// first, try to stop the attack with the wall
			if( $wall > 0 )
			{
				$damage-= $wall;
				$wall-= $atk;
				if( $wall < 0 ) $wall = 0;
			}
			
			// rest of the damage hits the tower
			if( $damage > 0 )
				$tower-= $damage;
		}
		
		private function ApplyGameLimits(array $attributes)
		{			
			if ($attributes["Quarry"] < 1) $attributes["Quarry"] = 1;
			if ($attributes["Magic"] < 1) $attributes["Magic"] = 1;
			if ($attributes["Dungeons"] < 1) $attributes["Dungeons"] = 1;
			if ($attributes["Bricks"] < 0) $attributes["Bricks"] = 0;
			if ($attributes["Gems"] < 0) $attributes["Gems"] = 0;
			if ($attributes["Recruits"] < 0) $attributes["Recruits"] = 0;
			if ($attributes["Tower"] < 0) $attributes["Tower"] = 0;
			if ($attributes["Tower"] > 100) $attributes["Tower"] = 100;
			if ($attributes["Wall"] < 0) $attributes["Wall"] = 0;
			if ($attributes["Wall"] > 150) $attributes["Wall"] = 150;
			
			return $attributes;
		}
	}
	
	
	class CGameData
	{
		public $Player; // array (name => CGamePlayerData)
		public $Current; // name of the player whose turn it currently is
		public $Round; // incremented after each play/discard action
		public $Winner; // if defined, name of the winner
		public $Outcome; // type: 'Build victory', 'Tower elimination victory', 'Opponent has surrendered', 'Draw', 'Aborted', 'Opponent fled the battlefield'
		public $Timestamp; // timestamp of the most recent action
	}
	
	class CGamePlayerData
	{
		public $Deck; // CDeckData
		public $Hand; // array ($i => $cardid)
		public $LastCard; // list of cards played last turn (in the order they were played)
		public $LastMode; // list of modes corresponding to cards played last turn (each is 0 or 1-8)
		public $LastAction; // list of actions corresponding to cards played last turn ('play'/'discard')
		public $NewCards; // associative array, where keys are card positions which have changed (values are arbitrary at the moment)
		public $Changes; // associative array, where keys are game atributes (resources, facilties, tower and wall). Values are ammount of difference
		public $DisCards; //array of two lists, one for each player. List contais all cards that where discarded during player's turn(s). Can be empty.
		public $TokenNames;
		public $TokenValues;
		public $TokenChanges;
		public $Tower;
		public $Wall;
		public $Quarry;
		public $Magic;
		public $Dungeons;
		public $Bricks;
		public $Gems;
		public $Recruits;
	}
?>
