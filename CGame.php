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
			$result = $db->Query('SELECT MAX(`GameID`)+1 as `max` FROM `games`');
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
			global $messagedb;
			
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
			
			//we need to store this information, because some cards will need it to make their effect, however after effect this information is not stored
			$mychanges = $mydata->Changes;
			$hischanges = $hisdata->Changes;
			$discarded_cards[0] = $mydata->DisCards[0];
			$discarded_cards[1] = $mydata->DisCards[1];
			
			// clear newcards flag, changes indicator and discarded cards here, if required
			if (!($this->IsPlayAgainCard($mydata->LastCard[$mylastcardindex]) and $mydata->LastAction[$mylastcardindex] == 'play'))
			{
				$mydata->NewCards = null;
				$mydata->Changes = $hisdata->Changes = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
				$mydata->DisCards[0] = $mydata->DisCards[1] = null;
			}
			
			// by default, opponent goes next (but this may change via card)
			$nextplayer = $opponent;
			
			// reduce the chance of getting the same card many times
			$drawfunc = ( $action == 'play' ) ? 'DrawCard_random' : 'DrawCard_different';
			$nextcard = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, $drawfunc);
			
			$production_factor = 1; // default production factor
			
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
				
				//create a copy of both players' hands and newcards flags
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
					case   7: $this->Attack(7, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 3; break;
					case   8: $mydata->Gems+= 7; $hisdata->Gems-= 7; break;
					case   9: $mydata->Magic+= 2; $nextcard = $this->DrawCard($carddb->GetList("Rare", "Mage"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  10: $this->Attack(21, $hisdata->Tower, $hisdata->Wall); break;
					case  11: $mydata->Gems+= 25; if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Mage")) and ($mydata->LastAction[$mylastcardindex] == 'play')) $mydata->Gems+= 8; break;
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
					case  23: $tmp = (((($mychanges['Bricks'] < 0) OR ($mychanges['Gems'] < 0) OR ($mychanges['Recruits'] < 0)) AND !($this->IsPlayAgainCard($mydata->LastCard[$mylastcardindex]) and $mydata->LastAction[$mylastcardindex] == 'play')) ? 2 : 1); $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; break;
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
					case  36: $this->Attack(16, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 9; break;
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
					case  49: $mydata->Bricks+= 10; break;
					case  50: $mydata->Recruits+= 10; break;
					case  51: $temp_array = array(); $j = 1; for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($hisdata->Hand[$i], "any")) { $temp_array[$j] = $i; $j++; } $position = $temp_array[array_rand($temp_array)]; $mydata->Bricks+= min($this->GetResources($hisdata->Hand[$position], "Bricks"),5); $mydata->Gems+= min($this->GetResources($hisdata->Hand[$position], "Gems"),5); $mydata->Recruits+= min($this->GetResources($hisdata->Hand[$position], "Recruits"),5); $hisdata->Hand[$position] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random'); $hisdata->NewCards[$position] = 1; break;
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
					case  78: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 2; $mydata->Gems+= 3; break;
					case  79: $hisdata->Bricks-= 10; break;
					case  80: $my_fac = $mydata->Quarry + $mydata->Magic + $mydata->Dungeons; $his_fac = $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons; if (($my_fac) >= ($his_fac)) { $mydata->Quarry-= 2; $mydata->Magic-= 2; $mydata->Dungeons-= 2; } if (($my_fac) <= ($his_fac)) { $hisdata->Quarry-= 2; $hisdata->Magic-= 2; $hisdata->Dungeons-= 2; } break;
					case  81: $mydata->Quarry+= 2; $mydata->Magic+= 2; $mydata->Dungeons+= 2; $hisdata->Quarry+= 2; $hisdata->Magic+= 2; $hisdata->Dungeons+= 2; $mydata->Bricks= 0; $mydata->Gems= 0; $mydata->Recruits= 0; $hisdata->Bricks= 0; $hisdata->Gems= 0; $hisdata->Recruits= 0; break;
					case  82: $this->Attack($mydata->Magic, $hisdata->Tower, $hisdata->Wall); break;
					case  83: $this->Attack(15, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 7; $mydata->Dungeons+= 1; $hisdata->Dungeons+= 1; break;
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
					case  96: $this->Attack(1, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 4; $mydata->Gems+= 3; break;
					case  97: $this->Attack(1, $hisdata->Tower, $hisdata->Wall); $last_card = $mydata->LastCard[$mylastcardindex]; if ((($this->GetResources($last_card, "Bricks") + $this->GetResources($last_card, "Gems") + $this->GetResources($last_card, "Recruits")) == 0) and ($mydata->LastAction[$mylastcardindex] == 'play')) $nextcard = $this->DrawCard(array_merge($carddb->GetList("Uncommon", "", "Zero"), $carddb->GetList("Rare", "", "Zero")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case  98: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); $hisdata->Magic-= 1; break;
					case  99: $mydata->Dungeons+= 1; $mydata->Magic+= 1; $hisdata->Dungeons+= 1; $hisdata->Magic+= 1; $hisdata->Gems-= 15; $hisdata->Recruits-= 10; break;
					case 100: $mydata->Quarry+= 5; $hisdata->Quarry+= 5; break;
					case 101: $tempnum = $this->KeywordCount($mydata->Hand, "Undead"); $hisdata->Bricks-= $tempnum; $hisdata->Gems-= $tempnum; $hisdata->Recruits-= $tempnum; break;
					case 102: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits+= 20; break;
					case 103: $tempnum = $this->KeywordCount($mydata->Hand, "Undead"); $mydata->Bricks+= $tempnum; $mydata->Gems+= $tempnum; $mydata->Recruits+= $tempnum; if ($tempnum > 6) $mydata->Magic+= 1; break;
					case 104: $mydata->Wall+= 10; break;
					case 105: $nextcard = $mydata->LastCard[$mylastcardindex]; break;
					case 106: $this->Attack(7, $hisdata->Tower, $hisdata->Wall); break;
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
					case 119: $production_factor*= 2; break;
					case 120: $this->Attack(11, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 5; $hisdata->Gems-= 5; break;
					case 121: $this->Attack(30, $hisdata->Tower, $hisdata->Wall); $mydata->Magic+= 1; break;
					case 122: $this->Attack(3, $hisdata->Tower, $hisdata->Wall); $mydata->Gems+= 1; break;
					case 123: $mydata->Tower+= 7; $production_factor*= 2; break;
					case 124: $this->Attack(70, $hisdata->Tower, $hisdata->Wall); $mydata->Tower-= 30; $mydata->Wall-= 50; break;
					case 125: $mydata->Tower+= 15; $mydata->Wall+= 15; $mydata->Bricks+= 15; $mydata->Gems+= 15; $mydata->Recruits+= 15; break;
					case 126: $this->Attack(40, $hisdata->Tower, $hisdata->Wall); break;
					case 127: $this->Attack(60, $hisdata->Tower, $hisdata->Wall); $hisdata->Recruits-= 15; break;
					case 128: $this->Attack(45, $hisdata->Tower, $hisdata->Wall); $hisdata->Gems-= 10; break;
					case 129: $mydata->Bricks+= 7; $mydata->Gems+= 7; $mydata->Recruits+= 7; $hisdata->Bricks+= 10; $hisdata->Gems+= 10; $hisdata->Recruits+= 10; break;
					case 130: $this->Attack(30, $hisdata->Tower, $hisdata->Wall);  $hisdata->Bricks-= 8; $hisdata->Recruits-= 8; break;
					case 131: $nextcard = $this->DrawCard($carddb->GetList("", "Dragon"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 132: if (($mydata->Quarry + $mydata->Magic + $mydata->Dungeons) > ($hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons)) { $mydata->Bricks-= 15; $mydata->Gems-= 15; $mydata->Recruits-= 15; } $mydata->Quarry+= 1; $mydata->Magic+= 1; $mydata->Dungeons+= 1; break;
					case 133: $tempnum = $this->KeywordCount($mydata->Hand, "Undead"); $this->Attack((($tempnum * 4) + 4), $hisdata->Tower, $hisdata->Wall); break;
					case 134: $mydata->Dungeons+= 2; $nextcard = $this->DrawCard($carddb->GetList("Rare", "Beast"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 135: if ($mydata->Bricks > $hisdata->Bricks) { $this->Attack(10, $hisdata->Tower, $hisdata->Wall); } else { $mydata->Bricks+= 8; } break;
					case 136: $this->Attack(9, $hisdata->Tower, $hisdata->Wall); $production_factor*= 2; break;
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
					case 166: $this->Attack(33, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 12; $mydata->Gems+= 5; $mydata->Recruits+= 5; break;
					case 167: $this->Attack(200, $hisdata->Tower, $hisdata->Wall); break;
					case 168: $nextcard = $hisdata->LastCard[$hislastcardindex]; break;
					case 169: $mydata->Tower-= 20; $mydata->Tower = max(1, $mydata->Tower); $mydata->Wall+= 70; break;
					case 170: $mydata->Bricks+= 15; $mydata->Gems+= 15; $mydata->Recruits+= 15; $hisdata->Bricks+= 20; $hisdata->Gems+= 20; $hisdata->Recruits+= 20; break;
					case 171: if (!($this->IsPlayAgainCard($mydata->LastCard[$mylastcardindex]) and $mydata->LastAction[$mylastcardindex] == 'play')) { $mydata->Tower-= $mychanges['Tower']; $mydata->Wall-= $mychanges['Wall']; $mydata->Quarry-= $mychanges['Quarry']; $mydata->Magic-= $mychanges['Magic']; $mydata->Dungeons-= $mychanges['Dungeons']; $mydata->Bricks-= $mychanges['Bricks']; $mydata->Gems-= $mychanges['Gems']; $mydata->Recruits-= $mychanges['Recruits']; } break;
					case 172: $this->Attack(14, $hisdata->Tower, $hisdata->Wall); break;
					case 173: $this->Attack(25, $hisdata->Tower, $hisdata->Wall); break;
					case 174: $production_factor*= 2; break;
					case 175: $mydata->Tower+= 13; $mydata->Bricks+= 7; $mydata->Gems+= 7; $mydata->Recruits+= 7; break;
					case 176: $mydata->Tower+= 3; $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; break;
					case 177: $nextcard = $this->DrawCard($carddb->GetList("", "Holy"), $mydata->Hand, $cardpos, 'DrawCard_list'); $tmp = min($this->KeywordCount($mydata->Hand, "Holy"),2); $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; break;
					case 178: $this->Attack($mydata->Recruits, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits= 0; break;
					case 179: $mydata->Tower+= $mydata->Wall; $mydata->Wall = 0; break;
					case 180: $this->Attack(15, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits-= 4; break;
					case 181: $this->Attack(35, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= 5; $mydata->Gems+= 5; $mydata->Recruits+= 5; $hisdata->Bricks-= 5; $hisdata->Gems-= 5; $hisdata->Recruits-= 5; break;
					case 182: $this->Attack(55, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks-= 10; $mydata->Gems-= 10; $mydata->Recruits-= 10; $hisdata->Bricks-= 10; $hisdata->Gems-= 10; $hisdata->Recruits-= 10; break;
					case 183: $nextcard = $this->DrawCard(array(181, 182, 183), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 184: $mydata->Tower+= 5; $mydata->Bricks+= 4; $mydata->Gems+= 4; $mydata->Recruits+= 4; $nextcard = $this->DrawCard(array(181, 182, 183), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 185: if ($mydata->Magic < $hisdata->Magic) { $mydata->Magic+= 1; } else { $mydata->Gems+= 11; } break;
					case 186: $hisdata->Wall-= 40; break;
					case 187: if ($mydata->Quarry < $hisdata->Quarry) { $mydata->Quarry+= 1; } else { $mydata->Bricks+= 12; } break;
					case 188: $mydata->Wall+= 5; break;
					case 189: $mydata->Tower+= 5; $mydata->Recruits+= ($mydata->Dungeons * 2); break;
					case 190: $hisdata->Tower-= 7; $hisdata->Wall-= 7; break;
					case 191: $mydata->Tower+= 15; break;
					case 192: $mydata->Tower+= 45; break;
					case 193: $this->Attack(16, $hisdata->Tower, $hisdata->Wall); break;
					case 194: $tempnum = $this->KeywordCount($mydata->Hand, "Soldier"); if ($tempnum < 4) $nextcard = $this->DrawCard($carddb->GetList("", "Soldier"), $mydata->Hand, $cardpos, 'DrawCard_list'); else $mydata->Recruits+= 7; break;
					case 195: if ($this->KeywordCount($hisdata->Hand, "Dragon") == 0) { $mydata->Bricks+= 6; $mydata->Gems+= 6; $mydata->Recruits+= 6; } else for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($hisdata->Hand[$i], "Dragon")) { $mydata->Hand[$i] = $hisdata->Hand[$i]; $mydata->NewCards[$i] = 1; $hisdata->Hand[$i] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random'); $hisdata->NewCards[$i] = 1; if ($cardpos == $i) $nextcard = 0; } break;
					case 196: $mydata->Tower+= 12; $nextcard = $this->DrawCard($carddb->GetList("", "Holy"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 197: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 10; $hisdata->Quarry-= 1; break;
					case 198: $hisdata->Wall-= 60; if ($hisdata->Wall <= 0) { $hisdata->Quarry-= 1; $hisdata->Magic-= 1; $hisdata->Dungeons-= 1; } break;
					case 199: $mydata->Bricks= ($mydata->Bricks * 2); $mydata->Gems= ($mydata->Gems * 2); $mydata->Recruits= ($mydata->Recruits * 2); break;
					case 200: $mydata->Tower+= 20; $hisdata->Tower+= 30; break;
					case 201: $mydata->Wall+= 30; if ($this->KeywordCount($mydata->Hand, "Soldier") > 0) $hisdata->Tower-= 11; break;
					case 202: $this->Attack(17, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= 5; $hisdata->Bricks-= 5; break;
					case 203: $mydata->Tower+= 6; $nextcard = $this->DrawCard(array_merge($carddb->GetList("Uncommon", "Undead"), $carddb->GetList("Rare", "Undead")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 204: $hisdata->Tower-= 9; break;
					case 205: $hisdata->Bricks+= 10; $hisdata->Gems+= 10; $hisdata->Recruits+= 10; $hisdata->Hand = $this->DrawHand_list($carddb->GetList("", "none", "Zero")); $hisdata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); break;
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
					case 232: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); $mydata->Tower+= 10; $mydata->Wall+= 10; $production_factor*= 2; break;
					case 233: $mydata->Tower+= 15; $mydata->Magic+= 1; break;
					case 234: $mydata->Tower+= 15; $mydata->Dungeons+= 1; break;
					case 235: $mydata->Tower+= 15; $mydata->Quarry+= 1; break;
					case 236: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 1; $mydata->Tower-= 1; break;
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
					case 251: $mydata->Hand[$cardpos] = $nextcard; $mydata->NewCards[$cardpos] = 1; $temp_array1 = $temp_array2 = array(0 => 1, 2, 3, 4); shuffle($temp_array1); shuffle($temp_array2);
							for ($k = 0; $k <= 1; $k++)
							{
								$i = $temp_array1[$k];
								$j = $temp_array2[$k];
								if ($mode == 1) {$i = $i * 2; $j = ($j * 2) - 1;}
								elseif ($mode == 2) {$i = ($i * 2) - 1; $j = $j * 2;}
								$mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random');
								$hisdata->Hand[$j] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, $cardpos, 'DrawCard_random');
								$mydata->NewCards[$i] = 1;
								$hisdata->NewCards[$j] = 1;
							}
							$nextcard = 0; break;
					case 252: $mydata->Hand[$cardpos] = $nextcard; $mydata->NewCards[$cardpos] = 1; $temp_array1 = $temp_array2 = array(0 => 1, 2, 3, 4); shuffle($temp_array1); shuffle($temp_array2);
							for ($k = 0; $k <= 1; $k++)
							{
								$i = $temp_array1[$k];
								$j = $temp_array2[$k];
								if ($mode == 1) {$i = $i * 2; $j = ($j * 2) - 1;}
								elseif ($mode == 2) {$i = ($i * 2) - 1; $j = $j * 2;}
								$tempcard = $mydata->Hand[$i];
								$mydata->Hand[$i] = $hisdata->Hand[$j];
								$hisdata->Hand[$j] = $tempcard;
								$mydata->NewCards[$i] = 1;
								$hisdata->NewCards[$j] = 1;
							}
							$nextcard = 0; break;
					case 253: $mydata->Hand = array (1=> 114, 113, 112, 120, 133, 330, 28, 103); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 254: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); if ($this->KeywordCount($mydata->Hand, "Burning") > 3) $hisdata->Magic-= 1; break;
					case 255: $mydata->Hand = $this->DrawHand_random($mydata->Deck); $hisdata->Hand = $this->DrawHand_random($hisdata->Deck); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $hisdata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 256: $hisdata->Wall-= 6; break;
					case 257: $mydata->Tower+= 2; $hisdata->Tower-= 2; break;
					case 258: $this->Attack(4, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks-= 3; break;
					case 259: $this->Attack(9, $hisdata->Tower, $hisdata->Wall); $production_factor = 0; break;
					case 260: $this->Attack(4, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= 2; $mydata->Gems+= 2; $mydata->Recruits+= 2; $hisdata->Bricks-= 2; $hisdata->Gems-= 2; $hisdata->Recruits-= 2; break;
					case 261: $mydata->Recruits+= 3; $hisdata->Recruits-= 3; break;
					case 262: $mydata->Wall+= 6; break;
					case 263: $mydata->Bricks+= 4; $mydata->Gems+= 4; $mydata->Recruits+= 4; $production_factor = 0; break;
					case 264: $mydata->Hand = $this->DrawHand_random($mydata->Deck); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 265: $mydata->Tower+= 99; break;
					case 266: if ($mode == 1) $mydata->Wall+= 20; elseif ($mode == 2) $mydata->Tower+= 10; break;
					case 267: if ($mode == 1) $mydata->Quarry+= 1; elseif ($mode == 2) $mydata->Magic+= 1; elseif ($mode == 3) $mydata->Dungeons+= 1; break;
					case 268: if ($mode == 1) $this->Attack(30, $hisdata->Tower, $hisdata->Wall); elseif ($mode == 2) $mydata->Wall+= 33; break;
					case 269: $mydata->Bricks-= 1; $mydata->Gems-= 1; $mydata->Recruits-= 1; $mydata->Wall+= 1; $mydata->Hand[$mode] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$mode] = 1; break;
					case 270: $hisdata->Hand[$mode] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random'); $hisdata->NewCards[$mode] = 1; break;
					case 271: $hisdata->Tower-= 2; $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; break;
					case 272: if ($mydata->Wall > $hisdata->Wall) $hisdata->Tower-= 7; else $this->Attack(6, $hisdata->Tower, $hisdata->Wall); break;
					case 273: if ($mode == 1) $mydata->Bricks+= 6; elseif ($mode == 2) $mydata->Gems+= 5; elseif ($mode == 3) $mydata->Recruits+= 5; break;  
					case 274: $last_card = $mydata->LastCard[$mylastcardindex]; if ((($this->GetResources($last_card, "Bricks") + $this->GetResources($last_card, "Gems") + $this->GetResources($last_card, "Recruits")) == 0) and ($mydata->LastAction[$mylastcardindex] == 'play')) { $mydata->Gems+= 3; $mydata->Recruits+= 2; } else { $mydata->Gems+= 2; $mydata->Recruits+= 1; } break;
					case 275: $mydata->Bricks+= $mydata->Quarry; break;
					case 276: $mydata->Wall+= 60; break;
					case 277: $mydata->Tower+= 35; break;
					case 278: if ($mode == 1) $mydata->Wall+= 8; elseif ($mode == 2) $mydata->Tower+= 5; break;
					case 279: if ($mode == 1) $mydata->Wall+= 5; elseif ($mode == 2) $this->Attack(6, $hisdata->Tower, $hisdata->Wall); elseif ($mode == 3) $hisdata->Tower-= 4; break;
					case 280: $mydata->Hand[$mode] = $hisdata->Hand[$mode]; $hisdata->Hand[$mode] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random'); $mydata->NewCards[$mode] = 1; $hisdata->NewCards[$mode] = 1; break;
					case 281: $this->Attack(11 - (int)floor($mydata->Tower/20), $hisdata->Tower, $hisdata->Wall); break;
					case 282: $mydata->Bricks-= (int)floor($mydata->Bricks * (($mydata->Tower)/100)); $mydata->Gems-= (int)floor($mydata->Gems * (($mydata->Tower)/100)); $mydata->Recruits-= (int)floor($mydata->Recruits * (($mydata->Tower)/100)); $hisdata->Bricks-= (int)floor($hisdata->Bricks * (($hisdata->Tower)/100)); $hisdata->Gems-= (int)floor($hisdata->Gems * (($hisdata->Tower)/100)); $hisdata->Recruits-= (int)floor($hisdata->Recruits * (($hisdata->Tower)/100)); break;
					case 283: $mydata->Tower= 30; $mydata->Wall= 20; $hisdata->Tower= 30; $hisdata->Wall= 20; break;
					case 284: if (($mydata->Tower < 50)&&($hisdata->Tower > 60)) $hisdata->Tower-= 25; else $hisdata->Tower-= 10; break;
					case 285: $this->Attack(($mydata->Bricks + $mydata->Gems + $mydata->Recruits), $hisdata->Tower, $hisdata->Wall); $mydata->Bricks= 0; $mydata->Gems= 0; $mydata->Recruits= 0; break;
					case 286: $hisdata->Tower-= (int)ceil($hisdata->Tower/2); break;
					case 287: $mydata->Hand = $this->DrawHand_list(array(97, 95, 13, 42, 51, 20, 76, 68)); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $mydata->Bricks+= 10; $mydata->Gems+= 10; $mydata->Recruits+= 10; $nextcard = 0; break;
					case 288: $hisdata->Bricks+= 10; $hisdata->Gems+= 10; $hisdata->Recruits+= 10; $hisdata->Hand[$mode] = 288; $hisdata->NewCards[$mode] = 1; break;
					case 289: $mydata->Tower+= 3; $mydata->Wall+= 7; break;
					case 290: $mydata->Tower+= 15; $mydata->Wall+= 30; break;
					case 291: $mydata->Hand = array (1=> 367, 279, 7, 305, 193, 36, 322, 166); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $mydata->Recruits+= 35; $nextcard = 0; break;
					case 292: $production_factor*= 2; $mydata->Hand = array (1=> 122, 122, 111, 111, 272, 272, 190, 136); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
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
					case 304: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 10; break;
					case 305: $this->Attack(12, $hisdata->Tower, $hisdata->Wall); $tempnum = $this->KeywordCount($mydata->Hand, "Soldier"); if ($tempnum > 3) $hisdata->Dungeons-= 1; break;
					case 306: $this->Attack(13, $hisdata->Tower, $hisdata->Wall); $tempnum = $this->KeywordCount($mydata->Hand, "Unliving"); if ($tempnum > 4) $hisdata->Quarry-= 1; break;
					case 307: $rarities = array(); $upgrades = array("Common" => "Uncommon", "Uncommon" => "Rare"); $rare = true;
								for ($i = 1; $i <= 8; $i++)
									if ($this->HasKeyword($mydata->Hand[$i], "Undead")) $rarities[$i] = $this->GetClass($mydata->Hand[$i]); 
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
					case 316: $rarity = $this->GetClass($hisdata->Hand[$mode]); $hisdata->Hand[$mode] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random'); $mydata->Hand[$mode] = $this->DrawCard($carddb->GetList($rarity, "Undead"), $mydata->Hand, $cardpos, 'DrawCard_list'); if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; $hisdata->NewCards[$mode] = 1; break;
					case 317: $this->Attack(22, $hisdata->Tower, $hisdata->Wall); $tempnum = $this->KeywordCount($mydata->Hand, "Charge"); $tempnum = min(4, $tempnum); if ($hisdata->Wall == 0) $hisdata->Tower-= 7*$tempnum; break;
					case 318: $this->Attack(10, $hisdata->Tower, $hisdata->Wall); if ($hisdata->Wall == 0) $hisdata->Tower-= 7; $mydata->Hand[$mode] = 318; if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 319: $mydata->Gems-= 3; $hisdata->Wall-= 4; $hisdata->Tower-= 4; break;
					case 320: if (($mydata->Tower < 10) && ($mydata->Wall == 0)) { $mydata->Tower= 25; $mydata->Wall= 15; } else { $mydata->Bricks+= 2; $mydata->Gems+= 2; $mydata->Recruits+= 2; } break;
					case 321: $storage = array(); for ($i = 1; $i <= 8; $i++) if (($this->HasKeyword($mydata->Hand[$i], "Undead")) AND ($i != $mode)) $storage[$i] = $i; shuffle($storage); $tmp = 0; for ($i = 0; ($i < count($storage) AND ($i < 4)); $i++) { $mydata->Hand[$storage[$i]] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$storage[$i]] = 1; $tmp++; } $mydata->Wall+= $tmp*10;
						break;
					case 322: $tempnum = min(4, $this->KeywordCount($mydata->Hand, "Soldier")); $this->Attack(8*$tempnum, $hisdata->Tower, $hisdata->Wall); if ($tempnum > 3) $mydata->Wall+= 15; break;
					case 323: if ($mode == 1) { $hisdata->Hand = $this->DrawHand_list(array_merge($carddb->GetList("Common", "Beast"), $carddb->GetList("Uncommon", "Beast"))); $hisdata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); } elseif ($mode == 2) { $tempnum = $this->KeywordCount($mydata->Hand, "Beast"); $tempnum+= $this->KeywordCount($hisdata->Hand, "Beast"); $this->Attack(5*$tempnum, $hisdata->Tower, $hisdata->Wall);} break;
					case 324: $tempnum = $this->KeywordCount($mydata->Hand, "Beast") + $this->KeywordCount($hisdata->Hand, "Beast"); if ($mode == 1) { $mydata->Bricks+= $tempnum; $mydata->Gems+= $tempnum; $mydata->Recruits+= $tempnum; } elseif ($mode == 2) $this->Attack(3*$tempnum, $hisdata->Tower, $hisdata->Wall); break;
					case 325: $temp_array = array(); for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($hisdata->Hand[$i], "Undead")) $temp_array[$i] = $i; shuffle($temp_array); $tmp = 0; for ($i = 0; ($i < count($temp_array)) AND ($i < 4); $i++) { $hisdata->Hand[$temp_array[$i]] = 381; $hisdata->NewCards[$temp_array[$i]] = 1; $tmp++; } $hisdata->Tower-= $tmp*3; break;
					case 326: for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($mydata->Hand[$i], "Soldier")) { $mydata->Hand[$i] = $this->DrawCard(array(85, 90, 137), $mydata->Hand, $cardpos, 'DrawCard_list'); $mydata->NewCards[$i] = 1; } break;
					case 327: $mydata->Tower+= 3; $mydata->Hand[$mode] = $this->DrawCard($carddb->GetList("", "Holy"), $mydata->Hand, $cardpos, 'DrawCard_list'); if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 328: $nextcard = $this->DrawCard($carddb->GetList("", "Barbarian"), $mydata->Hand, $cardpos, 'DrawCard_list'); $tempnum = $this->KeywordCount($mydata->Hand, "Barbarian"); if ($tempnum > 3) { $mydata->Bricks+= 3; $mydata->Gems+= 3; $mydata->Recruits+= 3; } break;
					case 329: $mydata->Gems+= 6; $mydata->Wall+= 3; $production_factor = 0; $mydata->Hand[$mode] = 329; if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 330: $tmp = 0; $temparray = array(); $j = 1;
						if ($mydata->LastAction[$mylastcardindex] == 'discard') $temparray[0] = $mydata->LastCard[$mylastcardindex];
						if (count($discarded_cards[0]) > 0) $temparray = array_merge($discarded_cards[0], $temparray);
						if (count($hisdata->DisCards[1]) > 0) $temparray = array_merge($temparray, $hisdata->DisCards[1]);
						$storage = array(); $j = 0;						
						for ($i = 0; $i < count($temparray); $i++) if ($this->HasKeyword($temparray[$i], "Undead")) { $storage[$j] = $temparray[$i]; $j++; }
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
					case 331: $tmp = $this->KeywordCount($mydata->Hand, "Holy"); $tmp = ceil($tmp/2); $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; $tmp = $this->KeywordCount($hisdata->Hand, "Holy"); $tmp = ceil($tmp/2); $hisdata->Bricks+= $tmp; $hisdata->Gems+= $tmp; $hisdata->Recruits+= $tmp; break;
					case 332: $tmp = $this->KeywordCount($mydata->Hand, "Undead"); $tmp = min($tmp,4); $mydata->Bricks-= $tmp; $mydata->Gems-= $tmp; $mydata->Recruits-= $tmp; $tmp = $this->KeywordCount($hisdata->Hand, "Undead"); $tmp = min($tmp,4); $hisdata->Bricks-= $tmp; $hisdata->Gems-= $tmp; $hisdata->Recruits-= $tmp; break;
					case 333: $tmp = $this->KeywordCount($mydata->Hand, "Undead"); $tmp = max(5 - $tmp,0); $this->Attack($tmp*4, $mydata->Tower, $mydata->Wall); $tmp = $this->KeywordCount($hisdata->Hand, "Undead"); $tmp = max(5 - $tmp,0); $this->Attack($tmp*4, $hisdata->Tower, $hisdata->Wall); break;
					case 334: if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Barbarian")) and ($mydata->LastAction[$mylastcardindex] == 'play')) $hisdata->Wall-= 7; $this->Attack(7, $hisdata->Tower, $hisdata->Wall); break;
					case 335: $this->Attack(21, $hisdata->Tower, $hisdata->Wall);
					if ($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Burning"))
					{
						$i = 1;
						for ($j = 1; $j <= 2; $j++)
						{
							while (($this->HasKeyword($mydata->Hand[$i], "Burning")) and ($i <= 8)) $i++;
							if ($i > 8) break;//no "free" slots in hand
							$mydata->Hand[$i] = $mydata->LastCard[$mylastcardindex];
							$mydata->NewCards[$i] = 1;
						}
					}
					break;
					case 336: if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Burning")) and ($mydata->LastAction[$mylastcardindex] == 'play')) $nextcard = $mydata->LastCard[$mylastcardindex];
							  else $nextcard = $this->DrawCard($carddb->GetList("", "Burning"), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 337: if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Barbarian")) and ($mydata->LastAction[$mylastcardindex] == 'play')) $hisdata->Wall-= 17; $this->Attack(20, $hisdata->Tower, $hisdata->Wall); break;
					case 338: if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Mage")) and ($mydata->LastAction[$mylastcardindex] == 'play')) $mydata->Gems+= $mydata->Magic * 3; $this->Attack(26, $hisdata->Tower, $hisdata->Wall); break;
					case 339: $tmp = min(20, max(5, $hisdata->Bricks - $mydata->Bricks)); $mydata->Bricks+= $tmp; $hisdata->Bricks-= $tmp; $tmp = min(20, max(5, $hisdata->Gems - $mydata->Gems)); $mydata->Gems+= $tmp; $hisdata->Gems-= $tmp; $tmp = min(20, max(5, $hisdata->Recruits - $mydata->Recruits)); $mydata->Recruits+= $tmp; $hisdata->Recruits-= $tmp; break;
					case 340: if ($mydata->Tower < $hisdata->Tower) $this->Attack(12, $hisdata->Tower, $hisdata->Wall); else $this->Attack(6, $hisdata->Tower, $hisdata->Wall); break;
					case 341: $tmp = $this->KeyWordCount($mydata->Hand, "Barbarian") + $this->KeyWordCount($mydata->Hand, "Holy"); $this->Attack(10 + 3*$tmp, $hisdata->Tower, $hisdata->Wall); break;
					case 342: $mydata->Hand[$cardpos] = $nextcard; $nextcard = 0; $tmp = mt_rand(1, 8); $mydata->Hand[$tmp] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$tmp] = 1;
								$i = 1;
								for ($j = 1; $j <= 3; $j++)
									{
										while (($this->HasKeyword($mydata->Hand[$i], "Holy")) and ($i <= 8)) $i++;
										if ($i > 8) break;//no "free" slots in hand
										$mydata->Hand[$i] = 340;
										$mydata->NewCards[$i] = 1;
									}
								break;
					case 343: for ($i = 1; $i <= 8; $i++) if ($mydata->Hand[$i] == 340) { $mydata->Hand[$i] = 341; $mydata->NewCards[$i] = 1; }  elseif ($mydata->Hand[$i] == 341) { $mydata->Hand[$i] = 31; $mydata->NewCards[$i] = 1; } break;
					case 344: $mydata->Wall+= 15; if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Barbarian")) and ($mydata->LastAction[$mylastcardindex] == 'play')) $this->Attack($this->GetResources($mydata->LastCard[$mylastcardindex], "Recruits"), $hisdata->Tower, $hisdata->Wall); break;
					case 345: $tmp = $this->KeyWordCount($mydata->Hand, "Barbarian"); $hisdata->Wall-= $tmp * 10; $this->Attack(40, $hisdata->Tower, $hisdata->Wall); break;
					case 346: $i = 1; while ((($this->GetResources($mydata->Hand[$i], "Recruits") >= 11) or ($this->GetClass($mydata->Hand[$i]) != "Rare") or (!$this->HasKeyword($mydata->Hand[$i], "Beast"))) and ($i <= 8)) $i++; if ($i < 9) { $mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$i] = 1; $this->Attack(42, $hisdata->Tower, $hisdata->Wall); } else $mydata->Recruits+= 9; break;
					case 347: $mydata->Hand[$cardpos] = $nextcard; $nextcard = 0; $j = 0;
								for ($i = 1; $i <= 8; $i++)
									if (!$this->HasKeyword($mydata->Hand[$i], "Beast"))
									{
										$mydata->Hand[$i] = $this->DrawCard($carddb->GetList("", "Beast"), $mydata->Hand, $cardpos, 'DrawCard_list');
										$mydata->NewCards[$i] = 1;
										$j++;
									}
								$mydata->Bricks-= $j * 2; $mydata->Gems-= $j * 2; $mydata->Recruits-= $j * 2;
								break;
					case 348: $found = false; for ($i = 1; $i <= $hislastcardindex; $i++) if ($this->HasKeyword($hisdata->LastCard[$i], "Swift")) { $found = true; break; } if ($found) { $hisdata->Bricks-= 12; $hisdata->Gems-= 12; $hisdata->Recruits-= 12; } else { $hisdata->Bricks-= 3; $hisdata->Gems-= 3; $hisdata->Recruits-= 3; } break;
					case 349: if ($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Unliving")) { $tmp = $this->GetResources($mydata->LastCard[$mylastcardindex], "Bricks"); $mydata->Tower+= ceil($tmp / 3); $mydata->Wall+= ceil($tmp / 2); } break;
					case 350: if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Undead")) and ($mydata->LastAction[$mylastcardindex] == 'play')) { $tmp = $this->GetResources($mydata->LastCard[$mylastcardindex], ""); $this->Attack(ceil($tmp / 2), $hisdata->Tower, $hisdata->Wall); $mydata->Tower+= ceil($tmp / 3); $mydata->Wall+= ceil($tmp / 2); } break;
					case 351: $nextcard = $this->DrawCard(array(351, 159), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 352: if ($mydata->Magic > $hisdata->Magic) $this->Attack(34, $hisdata->Tower, $hisdata->Wall); else $this->Attack(15, $hisdata->Tower, $hisdata->Wall); break;
					case 353: $mydata->Magic-= 1; if ($mode == 1) $mydata->Quarry+= 1; elseif ($mode == 2) $mydata->Dungeons+= 1; break;
					case 354: $mydata->Tower+= 9; $mydata->Wall+= 15; if ($this->KeywordCount($mydata->Hand, "Legend") > 0) $mydata->Magic+= 1; break;
					case 355: $j = 0; $mydata->Hand[$cardpos] = $nextcard; $nextcard = 0;
								for ($i = 1; $i <= 8; $i++)
									if ($this->HasKeyword($mydata->Hand[$i], "any"))
									{
										$mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random');
										$mydata->NewCards[$i] = 1;
										$j++;
									}
								$this->Attack($j * 7, $mydata->Tower, $mydata->Wall);								
								$j = 0;
								for ($i = 1; $i <= 8; $i++)
									if ($this->HasKeyword($hisdata->Hand[$i], "any"))
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
					case 359: if (($this->HasKeyword($hisdata->LastCard[$hislastcardindex], "Charge")) and ($hisdata->LastAction[$hislastcardindex] == 'play')) $mydata->Wall+= 18; else $mydata->Wall+= 10; break;
					case 360: $mydata->Bricks-= 2; $mydata->Gems-= 2; $mydata->Recruits-= 2; break;
					case 361: $this->Attack(7, $hisdata->Tower, $hisdata->Wall); if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Mage")) and ($mydata->LastAction[$mylastcardindex] == 'play')) { $hisdata->Bricks-= 3; $hisdata->Gems-= 3; $hisdata->Recruits-= 3; }break;
					case 362: $mydata->Wall+= 5; $hisdata->Wall-= 5; $this->Attack(5, $hisdata->Tower, $hisdata->Wall); break;
					case 363:
							$storage = array(); $min = 1000;
							for ($i = 1; $i <= 8; $i++)
								if ($this->HasKeyword($mydata->Hand[$i], "Soldier"))
								{
									$storage[$i] = $this->GetResources($mydata->Hand[$i], "Recruits");
									$min = min($storage[$i], $min);									
								}
							if (count($storage) > 0)
							{
								$min_array = array();
								foreach ($storage as $c_pos => $c_cost) if ($c_cost == $min) $min_array[$c_pos] = $min;
								$discarded_pos = array_rand($min_array);
								$mydata->Hand[$discarded_pos] = $this->DrawCard($mydata->Deck, $mydata->Hand, 0, 'DrawCard_random');
								$mydata->NewCards[$discarded_pos] = 1;
								$mydata->Bricks+= 3; $mydata->Gems+= 3; $mydata->Recruits+= 3;
							}
							break;
					case 364: if ((($mychanges['Bricks'] < 0) OR ($mychanges['Gems'] < 0) OR ($mychanges['Recruits'] < 0)) AND !($this->IsPlayAgainCard($mydata->LastCard[$mylastcardindex]) and $mydata->LastAction[$mylastcardindex] == 'play')) { $hisdata->Bricks+= min(0, max(-6, $mychanges['Bricks'])); $hisdata->Gems+= min(0, max(-6, $mychanges['Gems'])); $hisdata->Recruits+= min(0, max(-6, $mychanges['Recruits'])); } break;
					case 365: $tmp = 0; $found = false;
						for ($i = 1; $i <= $mylastcardindex; $i++) if (($this->HasKeyword($mydata->LastCard[$i], "Quick")) AND ($mydata->LastAction[$i] == 'play')) { $found = true; break; }
						if ($found)
							for ($i = 1; $i <= 8; $i++)
								if ($mydata->NewCards[$i] == 1)
								{
									$mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, 0, 'DrawCard_random');
									if ($i == $cardpos) $nextcard = 0;
									$tmp++;
								}
						if ($tmp > 0) { $mydata->Bricks-= $tmp; $mydata->Gems-= $tmp; $mydata->Recruits-= $tmp; }
								$tmp = 0; $found = false;
						for ($i = 1; $i <= $hislastcardindex; $i++) if (($this->HasKeyword($hisdata->LastCard[$i], "Quick")) AND ($hisdata->LastAction[$i] == 'play')) { $found = true; break; }
						if ($found)
							for ($i = 1; $i <= 8; $i++)
								if ($hisdata->NewCards[$i] == 1)
								{
									$hisdata->Hand[$i] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random');
									if ($i == $cardpos) $nextcard = 0;
									$tmp++;
								}
						if ($tmp > 0) { $hisdata->Bricks-= $tmp; $hisdata->Gems-= $tmp; $hisdata->Recruits-= $tmp; }
						break;
					case 366: $mydata->Tower+= 1; if ((($mychanges['Bricks'] < 0) OR ($mychanges['Gems'] < 0) OR ($mychanges['Recruits'] < 0)) AND !($this->IsPlayAgainCard($mydata->LastCard[$mylastcardindex]) AND $mydata->LastAction[$mylastcardindex] == 'play')) { $mydata->Bricks-= min(0, max(-6, $mychanges['Bricks'])); $mydata->Gems-= min(0, max(-6, $mychanges['Gems'])); $mydata->Recruits-= min(0, max(-6, $mychanges['Recruits'])); } break;
					case 367: $found = false; for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($hisdata->Hand[$i], "Charge")) { $found = true; break; } $this->Attack((($found) ? 4 : 10), $hisdata->Tower, $hisdata->Wall); break;
					case 368: if (!($this->IsPlayAgainCard($mydata->LastCard[$mylastcardindex]) and $mydata->LastAction[$mylastcardindex] == 'play') AND ($hischanges['Wall'] > 8)) $hisdata->Wall-= 12; $this->Attack(4, $hisdata->Tower, $hisdata->Wall); break;
					case 369: $tmp = $this->KeyWordCount($mydata->Hand, "Beast") + $this->KeyWordCount($mydata->Hand, "Burning") + $this->KeyWordCount($hisdata->Hand, "Beast") + $this->KeyWordCount($hisdata->Hand, "Burning"); $this->Attack(13 + 2*$tmp, $hisdata->Tower, $hisdata->Wall); $mydata->Recruits+= $tmp; break;
					case 370: $this->Attack(27, $hisdata->Tower, $hisdata->Wall); $d_found = $s_found = false; for ($i = 1; $i <= 8; $i++) if ($i != $cardpos) { if ((!$d_found) AND ($this->HasKeyword($mydata->Hand[$i], "Dragon"))) $d_found = true; if ((!$s_found) AND ($this->HasKeyword($mydata->Hand[$i], "Soldier"))) $s_found = true; } if ($d_found AND $s_found) $hisdata->Tower-= 14; break;
					case 371: $this->Attack(35, $hisdata->Tower, $hisdata->Wall); $l_found = $m_found = false; for ($i = 1; $i <= 8; $i++) if ($i != $cardpos) { if ((!$l_found) AND ($this->HasKeyword($mydata->Hand[$i], "Legend"))) $l_found = true; if ((!$m_found) AND ($this->HasKeyword($mydata->Hand[$i], "Mage"))) $m_found = true; } if ($l_found AND $m_found) { $mydata->Magic+= 1; $mydata->Gems+= 20; } break;
					case 372: $tmp = 0; for ($i = 1; $i <= 8; $i++) if (($this->HasKeyword($mydata->Hand[$i], "Undead")) AND ($i != $mode) AND ($i != $cardpos)) { $mydata->Hand[$i] = $this->DrawCard($mydata->Deck, $mydata->Hand, $cardpos, 'DrawCard_random'); $mydata->NewCards[$i] = 1; $tmp++; } $this->Attack($tmp*9, $hisdata->Tower, $hisdata->Wall); break;
					case 373: $tmp = $this->KeywordCount($mydata->Hand, "Unliving"); if ($tmp > 3) { $mydata->Bricks+= min(ceil($this->GetResources($hisdata->Hand[$mode], "Bricks") / 2),20); $mydata->Gems+= min(ceil($this->GetResources($hisdata->Hand[$mode], "Gems") / 2),20); $mydata->Recruits+= min(ceil($this->GetResources($hisdata->Hand[$mode], "Recruits") / 2),20); } $hisdata->Hand[$mode] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random'); $hisdata->NewCards[$mode] = 1; break;
					case 374: $hisdata->Tower-= 11; if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Mage")) and ($mydata->LastAction[$mylastcardindex] == 'play')) { $hisdata->Bricks-= 5; $hisdata->Gems-= 5; $hisdata->Recruits-= 5; } break;
					case 375: $mydata->Tower+= 7; $mydata->Wall+= 11; $production_factor*= 3; break;
					case 376: $b_found = $s_found = false; for ($i = 1; $i <= 8; $i++) if ($i != $cardpos) { if ((!$b_found) AND ($this->HasKeyword($mydata->Hand[$i], "Beast"))) $b_found = true; if ((!$s_found) AND ($this->HasKeyword($mydata->Hand[$i], "Soldier"))) $s_found = true; } $this->Attack(19 + (($b_found) ? 15 : 0) + (($s_found) ? 15 : 0), $hisdata->Tower, $hisdata->Wall); break;
					case 377: $tmp = $this->KeywordCount($mydata->Hand, "Alliance"); $this->Attack(20 + $tmp * 5, $hisdata->Tower, $hisdata->Wall); $mydata->Wall+= $tmp * 4; $mydata->Tower+= $tmp * 3; $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; break;
					case 378: $bonus = false; $temp_array = array(203, 307, 316); if (in_array($mydata->LastCard[$mylastcardindex], $temp_array) and ($mydata->LastAction[$mylastcardindex] == 'play')) $bonus = true; $this->Attack(56 + (($bonus) ? 36 : 0), $hisdata->Tower, $hisdata->Wall); break;
					case 379: $mydata->Tower = 50; $mydata->Wall = 70; $mydata->Bricks+= 10; $mydata->Gems+= 10; $mydata->Recruits+= 10; break;
					case 380: $temp_array = array("Tower", "Wall", "Quarry", "Magic", "Dungeons", "Bricks", "Gems", "Recruits"); foreach($temp_array as $attribute) $mydata->$attribute = $hisdata->$attribute = ceil(($mydata->$attribute + $hisdata->$attribute) / 2); break;
					case 381: $found = false; for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($mydata->Hand[$i], "Holy")) { $found = true; break; } if ($found) $mydata->Gems+= 1; break;
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
								$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random');
								$hisdata->NewCards[$discarded_pos] = 1;
								$mydata->Bricks+= 3; $mydata->Gems+= 3; $mydata->Recruits+= 3;
							}
							break;
					case 383: $mydata->Hand = array (1=> 410, 27, 85, 90, 175, 341, 137, 44); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; break;
					case 384: if ($mode == 1) $nextcard = 227; elseif ($mode == 2) $nextcard = 226; elseif ($mode == 3) $nextcard = 225; break;
					case 385: if ($mode == 1) $hisdata->Recruits-= 15; elseif ($mode == 2) $hisdata->Tower-= 10; break;
					case 386: for ($i = 1; $i <= 8; $i++) if (($this->HasKeyword($mydata->Hand[$i], "Soldier")) OR ($this->HasKeyword($mydata->Hand[$i], "Barbarian")) OR ($this->HasKeyword($mydata->Hand[$i], "Brigand"))) { $mydata->Hand[$i] = $this->DrawCard(array(23, 273, 297), $mydata->Hand, $cardpos, 'DrawCard_list'); $mydata->NewCards[$i] = 1; } for ($i = 1; $i <= 8; $i++) if (($this->HasKeyword($hisdata->Hand[$i], "Soldier")) OR ($this->HasKeyword($hisdata->Hand[$i], "Barbarian")) OR ($this->HasKeyword($hisdata->Hand[$i], "Brigand"))) { $hisdata->Hand[$i] = $this->DrawCard(array(23, 273, 297), $hisdata->Hand, $cardpos, 'DrawCard_list'); $hisdata->NewCards[$i] = 1; } break;
					case 387: $storage = array(); $min = 1000;
							for ($i = 1; $i <= 8; $i++)
								if ($this->HasKeyword($hisdata->Hand[$i], "Unliving"))
								{
									$storage[$i] = $this->GetResources($hisdata->Hand[$i], "Bricks");
									$min = min($storage[$i], $min);									
								}
							if (count($storage) > 0)
							{
								$min_array = array();
								foreach ($storage as $c_pos => $c_cost) if ($c_cost == $min) $min_array[$c_pos] = $min;
								$discarded_pos = array_rand($min_array);
								$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random');
								$hisdata->NewCards[$discarded_pos] = 1;
								$hisdata->Bricks-= $storage[$discarded_pos];
							}
							break;
					case 388: $my_res = $mydata->Bricks + $mydata->Gems + $mydata->Recruits; $his_res = $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits; if (($my_res) >= ($his_res)) { $mydata->Bricks+= ceil($mydata->Bricks * 0.25); $mydata->Gems+= ceil($mydata->Gems * 0.25); $mydata->Recruits+= ceil($mydata->Recruits * 0.25); } if (($my_res) <= ($his_res)) { $hisdata->Bricks+= ceil($hisdata->Bricks * 0.25); $hisdata->Gems+= ceil($hisdata->Gems * 0.25); $hisdata->Recruits+= ceil($hisdata->Recruits * 0.25); } break;
					case 389: $mydata->Tower+= 4; $mydata->Bricks+= ceil($mydata->Bricks * 0.25); $mydata->Gems+= ceil($mydata->Gems * 0.25); $mydata->Recruits+= ceil($mydata->Recruits * 0.25); break;
					case 390: if (!($this->IsPlayAgainCard($mydata->LastCard[$mylastcardindex]) and $mydata->LastAction[$mylastcardindex] == 'play')) { $hisdata->Tower-= $hischanges['Tower']; $hisdata->Wall-= $hischanges['Wall']; $hisdata->Quarry-= $hischanges['Quarry']; $hisdata->Magic-= $hischanges['Magic']; $hisdata->Dungeons-= $hischanges['Dungeons']; $hisdata->Bricks-= $hischanges['Bricks']; $hisdata->Gems-= $hischanges['Gems']; $hisdata->Recruits-= $hischanges['Recruits']; } break;
					case 391: $mydata->Tower-= 10; $mydata->Wall+= 20; break;
					case 392: $nextcard = $this->DrawCard(array(392, 313), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 393: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); $mydata->Hand[$mode] = $this->DrawCard(array_merge($carddb->GetList("", "Restoration"), $carddb->GetList("", "Nature")), $mydata->Hand, $cardpos, 'DrawCard_list'); if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 394: $this->Attack(7, $hisdata->Tower, $hisdata->Wall); $mydata->Hand[$mode] = $this->DrawCard($carddb->GetList("", "Alliance"), $mydata->Hand, $cardpos, 'DrawCard_list'); if ($mode == $cardpos) $nextcard = 0; else $mydata->NewCards[$mode] = 1; break;
					case 395: if ($mydata->Wall > 7) { $mydata->Wall-= 7; $mydata->Tower+= 13; } else $mydata->Wall+= 7; break;
					case 396: $tmp = $this->KeyWordCount($mydata->Hand, "Holy") + $this->KeyWordCount($mydata->Hand, "Unliving"); $this->Attack($tmp, $hisdata->Tower, $hisdata->Wall); $mydata->Wall+= 3*$tmp; break;
					case 397: $tmp = $this->CountDistinctKeywords($mydata->Hand); $this->Attack($tmp * 8, $hisdata->Tower, $hisdata->Wall); $mydata->Bricks+= $tmp * 2; $mydata->Gems+= $tmp * 2; $mydata->Recruits+= $tmp * 2; break;
					case 398: $this->Attack(25, $hisdata->Tower, $hisdata->Wall); $mydata->Magic+= 1; $hisdata->Magic-= 1;
							$storage = array(); $j = 0;
							for ($i = 1; $i <= 8; $i++)
								if ($this->HasKeyword($mydata->Hand[$i], "Mage"))
								{
									$storage[$j] = $i;
									$j++;
								}
							if ($j > 0)
							{
								$discarded_pos = $storage[array_rand($storage)];
								$mydata->Hand[$discarded_pos] = $this->DrawCard($mydata->Deck, $mydata->Hand, 0, 'DrawCard_random');
								$mydata->NewCards[$discarded_pos] = 1;
								$mydata->Gems+= 30;
							}
							break;
					case 399: $mydata->Tower+= 2; $mydata->Wall+= 3; $nextcard = $this->DrawCard(array_merge($carddb->GetList("Common", "Mage"), $carddb->GetList("Uncommon", "Mage")), $mydata->Hand, $cardpos, 'DrawCard_list'); break;
					case 400: if ($mode != $cardpos) $nextcard = $mydata->Hand[$mode]; break;
					case 401: $tmp = ceil($mydata->Bricks / 5); $mydata->Tower+= $tmp; $mydata->Wall+= $tmp; $tmp = ceil($mydata->Gems / 5); $hisdata->Bricks-= $tmp; $hisdata->Gems-= $tmp; $hisdata->Recruits-= $tmp; break;
					case 402: $tmp = $hisdata->LastCard[$hislastcardindex]; $mydata->Bricks+= min(floor($this->GetResources($tmp, "Bricks") / 4), 3); $mydata->Gems+= min(floor($this->GetResources($tmp, "Gems") / 4), 3); $mydata->Recruits+= min(floor($this->GetResources($tmp, "Recruits") / 4), 3); break;
					case 403: $mydata->Hand = array (1=> 13, 13, 240, 240, 368, 46, 106, 89); $mydata->NewCards = array (1=> 1, 1, 1, 1, 1, 1, 1, 1); $nextcard = 0; $mydata->Dungeons+= 2; break;
					case 404: $this->Attack(20, $hisdata->Tower, $hisdata->Wall); $hisdata->Tower-= 15; $nextcard = 374; break;
					case 405: $tmp = $this->KeywordCount($mydata->Hand, "Alliance"); $mydata->Wall+= $tmp * 5; $mydata->Recruits+= $tmp * 3; break;
					case 406: $this->Attack(35, $hisdata->Tower, $hisdata->Wall); $b_found = $s_found = false; for ($i = 1; $i <= 8; $i++) if ($i != $cardpos) { if ((!$b_found) AND ($this->HasKeyword($mydata->Hand[$i], "Beast"))) $b_found = true; if ((!$s_found) AND ($this->HasKeyword($mydata->Hand[$i], "Soldier"))) $s_found = true; } if ($b_found AND $s_found) $hisdata->Tower-= 26; break;
					case 407: $mydata->Tower+= 14; $mydata->Wall = max(($mydata->Wall * 2), 44); $tmp = $this->KeywordCount($mydata->Hand, "Alliance"); $mydata->Bricks+= $tmp; $mydata->Gems+= $tmp; $mydata->Recruits+= $tmp; break;
					case 408: $this->Attack(13, $hisdata->Tower, $hisdata->Wall);
							$storage = array(); $j = 0;
							for ($i = 1; $i <= 8; $i++)
								if ($this->HasKeyword($hisdata->Hand[$i], "Legend"))
								{
									$storage[$j] = $i;
									$j++;
								}
							if ($j > 0)
							{
								$discarded_pos = $storage[array_rand($storage)];
								$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random');
								$hisdata->NewCards[$discarded_pos] = 1;
								$hisdata->Magic-= 1;
							}
							break;
					case 409: $this->Attack(14, $hisdata->Tower, $hisdata->Wall);
							$storage = array(); $j = 0;
							for ($i = 1; $i <= 8; $i++)
								if (($this->HasKeyword($hisdata->Hand[$i], "Dragon")) AND ($this->GetClass($hisdata->Hand[$i]) == "Rare"))
								{
									$storage[$j] = $i;
									$j++;
								}
							if ($j > 0)
							{
								$discarded_pos = $storage[array_rand($storage)];
								$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random');
								$hisdata->NewCards[$discarded_pos] = 1;
								$nextcard = 131;
							}
							break;
					case 410: $found = false; for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($hisdata->Hand[$i], "Holy")) { $found = true; break; } if (!$found) { $mydata->Bricks+= 1; $mydata->Gems+= 1; $mydata->Recruits+= 1; $hisdata->Bricks-= 1; $hisdata->Gems-= 1; $hisdata->Recruits-= 1; } else $hisdata->Tower-= 1; break;
					case 411: $this->Attack(5, $hisdata->Tower, $hisdata->Wall); if (($this->HasKeyword($mydata->LastCard[$mylastcardindex], "Beast")) and ($mydata->LastAction[$mylastcardindex] == 'play')) $mydata->Recruits+= 3; break;
					
				}
				
				//begin keyword processing
				
				//process Durable cards - they stays on hand
				if ($this->HasKeyWord($cardid, "Durable"))
					$nextcard = $cardid;
				
				//process Quick cards - play again with no production
				if ($this->HasKeyWord($cardid, "Quick"))
				{
					$nextplayer = $playername;
					$production_factor = 0;
				}
				
				//process Swift cards - play again with production
				if ($this->HasKeyWord($cardid, "Swift")) $nextplayer = $playername;
				
				//process Unliving cards - chance for Bricks cost return
				if ($this->HasKeyWord($cardid, "Unliving"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Unliving") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 8)) $mydata->Bricks+= ceil($card->CardData->Bricks / 2);
				}
				
				//process Soldier cards - chance for Recruits cost return
				if ($this->HasKeyWord($cardid, "Soldier"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Soldier") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 8)) $mydata->Recruits+= ceil($card->CardData->Recruits / 2);
				}
				
				//process Mage cards - chance for Gems cost return
				if ($this->HasKeyWord($cardid, "Mage"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Mage") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 8)) $mydata->Gems+= ceil($card->CardData->Gems / 2);
				}
				
				//process Undead cards - chance for extra cost
				if ($this->HasKeyWord($cardid, "Undead"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Undead") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 6))
					{
						$stock = array("Common" => 1, "Uncommon" => 3, "Rare" => 5);
						$lost = $stock[$card->CardData->Class];						
						$mydata->Bricks-= $lost;
						$mydata->Gems-= $lost;
						$mydata->Recruits-= $lost;
					}
				}
				
				//process Burning cards - chance for discarding one random card from enemy hand and additional damage to enemy tower
				if ($this->HasKeyWord($cardid, "Burning"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Burning") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 8))
					{
						$discarded_pos = array_rand($hisdata->Hand);
						$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random');
						$hisdata->NewCards[$discarded_pos] = 1;
						$damage = array("Common" => 1, "Uncommon" => 3, "Rare" => 5);
						$hisdata->Tower-= $damage[$card->CardData->Class];
					}
				}
				
				//process Holy cards - chance for discarding one random undead card from enemy hand and get additional stock
				if ($this->HasKeyWord($cardid, "Holy"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Holy") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 12))
					{
						$storage = array();
						for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($hisdata->Hand[$i], "Undead")) $storage[$i] = $i;
						if (count($storage) > 0)
						{
							$discarded_pos = array_rand($storage);
							$dis_rarity = $this->GetClass($hisdata->Hand[$discarded_pos]);
							$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random');
							$hisdata->NewCards[$discarded_pos] = 1;
							$stock = array("Common" => 1, "Uncommon" => 2, "Rare" => 3);
							$gained = $stock[$dis_rarity];						
							$mydata->Bricks+= $gained;
							$mydata->Gems+= $gained;
							$mydata->Recruits+= $gained;
						}
					}
				}
				
				//process Brigand cards - chance for stealing additional stock
				if ($this->HasKeyWord($cardid, "Brigand"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Brigand") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 7))
					{
						$stock = array("Common" => 1, "Uncommon" => 2, "Rare" => 3);
						$gained = $stock[$card->CardData->Class];
						$mydata->Bricks+= $gained;
						$mydata->Gems+= $gained;
						$mydata->Recruits+= $gained;
						$hisdata->Bricks-= $gained;
						$hisdata->Gems-= $gained;
						$hisdata->Recruits-= $gained;
					}
				}
				
				//process Barbarian cards - chance for additional damage to enemy wall
				if ($this->HasKeyWord($cardid, "Barbarian"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Barbarian") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 9))
					{
						$damage = array("Common" => 3, "Uncommon" => 8, "Rare" => 15);
						$hisdata->Wall-= $damage[$card->CardData->Class];
					}
				}
				
				//process Beast cards - chance for additional damage to enemy
				if ($this->HasKeyWord($cardid, "Beast"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Beast") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 8))
					{
						$damage = array("Common" => 2, "Uncommon" => 5, "Rare" => 10);
						$this->Attack($damage[$card->CardData->Class], $hisdata->Tower, $hisdata->Wall);
					}
				}
				
				//process Dragon cards - chance for getting a rare dragon card (only if played card wasn't a rare dragon)
				if ($this->HasKeyWord($cardid, "Dragon"))
				{
					if (($card->CardData->Class != "Rare") AND (mt_rand(1, 100) <= 11))
					{
						$nextcard = $this->DrawCard($carddb->GetList("Rare", "Dragon"), $mydata->Hand, $cardpos, 'DrawCard_list');
					}
				}
				
				//process Rebirth cards - if there are enough Burning cards in game the card stays on hand and player get additional gems
				if ($this->HasKeyWord($cardid, "Rebirth"))
				{
					if (($this->KeywordCount($mydata->Hand, "Burning") + $this->KeywordCount($hisdata->Hand, "Burning")) > 3)
					{
						$nextcard = $cardid;
						$mydata->Gems+= 16;
					}
				}
				
				//process Flare attack cards - place searing fire cards to both players hands (odd and even positions randomly selected)
				if ($this->HasKeyWord($cardid, "Flare attack"))
				{
					$selector = mt_rand(0,1);
					for ($i = 1; $i <= 8; $i++)
					{
						if ((($i % 2) == $selector) AND ($i != $cardpos) AND (!$this->HasKeyword($mydata->Hand[$i], "Burning")))// the position of the played card is ignored
						{
							$mydata->Hand[$i] = 248;
							$mydata->NewCards[$i] = 1;
						}
						elseif ((($i % 2) != $selector)	AND (!$this->HasKeyword($hisdata->Hand[$i], "Burning")))
						{
							$hisdata->Hand[$i] = 248;
							$hisdata->NewCards[$i] = 1;
						}
					}
				}
				
				//process Banish cards - discard one random Durable card from enemy hand, if there is one
				if ($this->HasKeyWord($cardid, "Banish"))
				{
					$storage = array();
					for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($hisdata->Hand[$i], "Durable")) $storage[$i] = $i;
					if (count($storage) > 0)
					{					
						$discarded_pos = array_rand($storage);
						$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random');
						$hisdata->NewCards[$discarded_pos] = 1;
					}
				}
				
				//process Titan cards - chance for getting a Titan card more often
				if ($this->HasKeyWord($cardid, "Titan"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Titan") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 9))
					{
						$nextcard = $this->DrawCard($carddb->GetList("", "Titan"), $mydata->Hand, $cardpos, 'DrawCard_list');
					}
				}
				
				//process Alliance cards - chance for getting additional Production X2
				if ($this->HasKeyWord($cardid, "Alliance"))
				{
					$ammount = $this->KeywordCount($mydata->Hand, "Alliance") - 1; // we don't count the played card
					if (mt_rand(1, 100) <= ($ammount * 8))
					{
						$production_factor*= 2;
					}
				}
				
				//process Legend cards - chance for getting additional facility
				if ($this->HasKeyWord($cardid, "Legend"))
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
				if ($this->HasKeyWord($cardid, "Skirmisher"))
				{
					$storage = array();
					for ($i = 1; $i <= 8; $i++) if ($this->HasKeyword($hisdata->Hand[$i], "Charge")) $storage[$i] = $i;
					if (count($storage) > 0)
					{					
						$discarded_pos = array_rand($storage);
						$hisdata->Hand[$discarded_pos] = $this->DrawCard($hisdata->Deck, $hisdata->Hand, 0, 'DrawCard_random');
						$hisdata->NewCards[$discarded_pos] = 1;
					}
				}
								
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
				$mydata_copy['Bricks']+= ($production_factor - 1) * $mydata->Quarry;
				$mydata_copy['Gems']+= ($production_factor - 1) * $mydata->Magic;
				$mydata_copy['Recruits']+= ($production_factor - 1) * $mydata->Dungeons;
				
				// add the new difference to the changes arrays
				foreach ($mydata_temp as $attribute => $value)
				{
					$mydata->Changes[$attribute] += $mydata_copy[$attribute] - $mydata_temp[$attribute];
					$hisdata->Changes[$attribute] += $hisdata_copy[$attribute] - $hisdata_temp[$attribute];
				}
			}
			
			// add production at the end of turn
			$mydata->Bricks+= $production_factor * $mydata->Quarry;
			$mydata->Gems+= $production_factor * $mydata->Magic;
			$mydata->Recruits+= $production_factor * $mydata->Dungeons;
										
			// draw card at the end of turn
			if ($nextcard != 0) $mydata->Hand[$cardpos] = $nextcard;  // 0 - some cards might disable drawing this turn
			
			// store info about this current action, updating history as needed
			if ($this->IsPlayAgainCard($mydata->LastCard[$mylastcardindex]) and $mydata->LastAction[$mylastcardindex] == 'play') 
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
				$data->Round++;
			}
			
			if ($this->State == 'finished') // send battle report messages
			{
				if ($data->Winner == '')
				{
					$player1 = $playername;
					$player2 = $opponent;
					
					$message1 = 'You have found a worthy adversary, indeed. Game with '.$opponent.' ended in a '.$data->Outcome.'.';
					$message2 = 'You have found a worthy adversary, indeed. Game with '.$playername.' ended in a '.$data->Outcome.'.';
				}
				else
				{
					$player1 = (($data->Winner == $playername) ? $playername : $opponent);
					$player2 = (($data->Winner == $playername) ? $opponent : $playername);
					
					$message1 = 'Congratulations, you have beaten '.$player2.'. Game has ended by '.$data->Outcome.'.';
					$message2 = 'You have been beaten by '.$player1.'. Don\'t worry, you\'ll get him/her next time. Game has ended by '.$data->Outcome.'.';
				}
				
				$messagedb->SendMessage("MArcomage", $player1, "Battle report", $message1);
				$messagedb->SendMessage("MArcomage", $player2, "Battle report", $message2);
			}
			
			$this->SaveGame();
			return 'OK';
		}
		
		private function IsPlayAgainCard($cardid)
		{	
			return (($this->HasKeyWord($cardid, "Quick")) OR ($this->HasKeyWord($cardid, "Swift")));
		}
		
		private function KeywordCount(array $hand, $keyword)
		{
			$count = 0;
			
			foreach ($hand as $cardid)
				if ($this->HasKeyword($cardid, $keyword))
					$count++;
			
			return $count;
		}
		
		private function HasKeyword($cardid, $keyword)
		{
			global $carddb;
			
			if( $keyword != "any" )
				return (strpos($carddb->GetCard($cardid)->CardData->Keywords, $keyword) !== FALSE);
			else // search for any keywords
				return ($carddb->GetCard($cardid)->CardData->Keywords != "");
		}
		
		private function CountDistinctKeywords(array $hand)
		{
			global $carddb;
			
			$first = true;
			
			foreach ($hand as $cardid)
			{
				$keyword = $carddb->GetCard($cardid)->CardData->Keywords;
				if ($keyword != "") // ignore cards with no keywords
					if ($first)
					{
						$keywords_list = $carddb->GetCard($cardid)->CardData->Keywords;
						$first = false;
					}
					else $keywords_list.= " ".$carddb->GetCard($cardid)->CardData->Keywords;
			}
			
			$words = preg_split("/\. ?/", $keywords_list, -1, PREG_SPLIT_NO_EMPTY); // split individual keywords
			foreach($words as $word)
			{
				$word = preg_split("/ \(/", $word, 0); // remove parameter if present
				$word = $word[0];
				$keywords[$word] = $word; // removes duplicates
			}
			
			return count($keywords);
		}
		
		private function GetResources($cardid, $type)
		{
			global $carddb;
			
			if ($type !="")
				$resource = $carddb->GetCard($cardid)->CardData->$type;
			else
			{
				$resources = array("Bricks" => 0, "Gems" => 0, "Recruits" => 0);
				$resource = 0;
				foreach ($resources as $r_name => $r_value)
					$resource+= $carddb->GetCard($cardid)->CardData->$r_name;
			}
		
			return $resource;
		}
		
		private function GetClass($cardid)
		{
			global $carddb;
					
			return $carddb->GetCard($cardid)->CardData->Class;
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
			$card_pos = 0; //card position is in this case irrelevant
			for ($i = 1; $i <= 8; $i++) $hand[$i] = $this->DrawCard($source, $hand, $card_pos, $draw_function);
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
