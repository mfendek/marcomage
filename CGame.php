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
			
			$game_data[$player1] = new CGamePlayerData;
			$game_data[$player1]->Deck = $deck1;
			
			$result = $db->Query('INSERT INTO `games` (`Player1`, `Player2`, `Data`) VALUES ("'.$db->Escape($player1).'", "'.$db->Escape($player2).'", "'.$db->Escape(serialize($game_data)).'")');
			if (!$result) return false;
			
			$game = new CGame($db->LastID(), $player1, $player2, $this);
			
			return $game;
		}
		
		public function DeleteGame($gameid)
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `games` WHERE `GameID` = '.$db->Escape($gameid));
			if (!$result) return false;
			
			return true;
		}
		
		public function DeleteGames($player) // delete all games and related data for specified player
		{
			global $chatdb;
			global $replaydb;
			$db = $this->db;
			
			// get list of games that are going to be deleted
			$result = $db->Query('SELECT `GameID` FROM `games` WHERE (`Player1` = "'.$db->Escape($player).'") OR (`Player2` = "'.$db->Escape($player).'")');
			if (!$result) return false;
			
			$games = array();
			while( $data = $result->Next() )
				$games[] = $data['GameID'];
			
			// delete games
			$result = $db->Query('DELETE FROM `games` WHERE (`Player1` = "'.$db->Escape($player).'") OR (`Player2` = "'.$db->Escape($player).'")');
			if (!$result) return false;
			
			// delete related data
			foreach ($games as $gameid)
			{
				$res = $chatdb->DeleteChat($gameid);
				if (!$res) return false;
				$res = $replaydb->DeleteReplay($gameid);
				if (!$res) return false;
			}
			
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

		public function CheckGame($player1, $player2)
		{
			$db = $this->db;
			$result = $db->Query('SELECT 1 FROM `games` WHERE `Player1` = "'.$db->Escape($player1).'" AND `Player2` = "'.$db->Escape($player2).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			return true;
		}
		
		public function JoinGame($player, $game_id)
		{
			$db = $this->db;
			$result = $db->Query('UPDATE `games` SET `Player2` = "'.$db->Escape($player).'" WHERE `GameID` = "'.$db->Escape($game_id).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function CountFreeSlots1($player) // used in all cases except when accepting a challenge
		{
			global $playerdb;
			$db = $this->db;
			
			// outgoing = chalenges_from + hosted_games
			$outgoing = '`Player1` = "'.$db->Escape($player).'" AND `State` = "waiting"';
			
			// incoming challenges
			$challenges_to = '`Player2` = "'.$db->Escape($player).'" AND `State` = "waiting"';
			
			// active games
			$active_games = '`Player1` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P2 over")';
			
			$result = $db->Query('SELECT COUNT(`GameID`) as `count` FROM `games` WHERE ('.$outgoing.') OR ('.$challenges_to.') OR ('.$active_games.')');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return max(0, MAX_GAMES + floor($playerdb->GetLevel($player) / BONUS_GAME_SLOTS) - $data['count']); // make sure the result is not negative
		}
		
		public function CountFreeSlots2($player) // used only when accepting a challenge
		{
			global $playerdb;
			$db = $this->db;
			
			// outgoing = chalenges_from + hosted_games
			$outgoing = '`Player1` = "'.$db->Escape($player).'" AND `State` = "waiting"';
			
			// active games
			$active_games = '`Player1` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P2 over")';
			
			$result = $db->Query('SELECT COUNT(`GameID`) as `count` FROM `games` WHERE ('.$outgoing.') OR ('.$active_games.')');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return max(0, MAX_GAMES + floor($playerdb->GetLevel($player) / BONUS_GAME_SLOTS) - $data['count']);
		}
		
		public function ListChallengesFrom($player)
		{
			// $player is on the left side and $Status = "waiting"
			$db = $this->db;
			$result = $db->Query('SELECT `Player2` FROM `games` WHERE `Player1` = "'.$db->Escape($player).'" AND `Player2` != "" AND `State` = "waiting"');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Player2'];
			
			return $names;
		}
		
		public function ListChallengesTo($player)
		{
			// $player is on the right side and $Status = "waiting"
			$db = $this->db;
			$result = $db->Query('SELECT `Player1` FROM `games` WHERE `Player2` = "'.$db->Escape($player).'" AND `State` = "waiting"');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Player1'];
			
			return $names;
		}
		
		public function ListFreeGames($player, $hidden = "none", $friendly = "none")
		{
			// list hosted games, where player can join
			$hidden_q = ($hidden != "none") ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) '.(($hidden == "include") ? '>' : '=').' 0' : '';
			$friendly_q = ($friendly != "none") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) '.(($friendly == "include") ? '>' : '=').' 0' : '';
			
			$db = $this->db;
			$result = $db->Query('SELECT `GameID`, `Player1`, `Last Action`, `GameModes` FROM `games` WHERE `Player1` != "'.$db->Escape($player).'" AND `Player2` = "" AND `State` = "waiting"'.$hidden_q.$friendly_q.' ORDER BY `Last Action` DESC');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
		
		public function ListHostedGames($player)
		{
			// list hosted games, hosted by specific player
			$db = $this->db;
			$result = $db->Query('SELECT `GameID`, `Last Action`, `GameModes` FROM `games` WHERE `Player1` = "'.$db->Escape($player).'" AND `Player2` = "" AND `State` = "waiting" ORDER BY `Last Action` DESC');
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
			$result = $db->Query('SELECT `GameID` FROM `games` WHERE (`Player1` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P2 over"))');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
		
		public function ListOpponents($player)
		{
			// list names of all oppponents from games where specified player is on the left
			$db = $this->db;
			$result = $db->Query('SELECT `Player2` FROM `games` WHERE `Player1` = "'.$db->Escape($player).'" AND `State` != "waiting" AND `State` != "P1 over"');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Player2'];
			
			return $names;
		}
		
		public function ListGamesData($player)
		{
			// $player is either on the left or right side and Status != 'waiting' or 'P? over'
			$db = $this->db;
			$result = $db->Query('SELECT `GameID`, `Player1`, `Player2`, `State`, `Current`, `Round`, `Last Action` FROM `games` WHERE (`Player1` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P1 over")) OR (`Player2` = "'.$db->Escape($player).'" AND (`State` != "waiting" AND `State` != "P2 over"))');
			if (!$result) return false;
			
			$games = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$games[$i] = $result->Next();
			
			return $games;
		}
		
		public function ListEndedGames($player)
		{
			// list names of all oppponents from ended games where specified player is on the left
			$db = $this->db;
			$result = $db->Query('SELECT `Player2` FROM `games` WHERE `Player1` = "'.$db->Escape($player).'" AND `State` = "P1 over"');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Player2'];
			
			return $names;
		}
		
		/// Does the specified player has any games where it's his/her turn?
		public function IsAnyCurrentGame($player)
		{
			$db = $this->db;
			$result = $db->Query('SELECT 1 FROM `games` WHERE `Current` = "'.$db->Escape($player).'" AND `State` = "in progress" LIMIT 1');
			if (!$result) return false;

			return( $result->Rows() > 0 );
		}
		
		public function NextGameList($player)
		{
			// provide list of active games with opponent names
			$db = $this->db;
			$result = $db->Query('SELECT `GameID`, (CASE WHEN `Player1` = "'.$db->Escape($player).'" THEN `Player2` ELSE `Player1` END) as `Opponent` FROM `games` WHERE ((`Player1` = "'.$db->Escape($player).'") OR (`Player2` = "'.$db->Escape($player).'")) AND (`State` = "in progress") AND (`Current` = "'.$db->Escape($player).'")');
			if (!$result) return false;
			
			$game_data = array();
			while( $data = $result->Next() )
				$game_data[$data['GameID']] = $data['Opponent'];
			
			return $game_data;
		}
	}
	
	
	class CGame
	{
		private $Games;
		private $GameID;
		private $Player1;
		private $Player2;
		private $Note1;
		private $Note2;
		private $HiddenCards; // hide opponent's cards (yes/no)
		private $FriendlyPlay; // allow game to effect player score (yes/no)
		public $State; // 'waiting' / 'in progress' / 'finished' / 'P1 over' / 'P2 over'
		public $Current; // name of the player whose turn it currently is
		public $Round; // incremented after each play/discard action
		public $Winner; // if defined, name of the winner
		public $Surrender; // if defined, name of the player who requested to surrender
		public $EndType; // game end type: 'Pending', 'Construction', 'Destruction', 'Resource', 'Timeout', 'Draw', 'Surrender', 'Abort', 'Abandon'
		public $LastAction; // timestamp of the most recent action
		public $GameData; // array (name => CGamePlayerData)
		
		public function __construct($gameid, $player1, $player2, CGames $Games)
		{
			$this->GameID = $gameid;
			$this->Player1 = $player1;
			$this->Player2 = $player2;
			$this->Games = &$Games;
			$this->GameData[$player1] = new CGamePlayerData;
			$this->GameData[$player2] = new CGamePlayerData;
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
		
		public function Outcome()
		{
			$outcomes = array(
				'Surrender' => 'Opponent has surrendered',
				'Abort' => 'Aborted',
				'Abandon' => 'Opponent has fled the battlefield',
				'Destruction' => 'Tower destruction victory',
				'Draw' => 'Draw',
				'Construction' => 'Tower building victory',
				'Resource' => 'Resource accumulation victory',
				'Timeout' => 'Timeout victory',
				'Pending' => 'Pending'
			);
			return $outcomes[$this->EndType];
		}
		
		public function GetNote($player)
		{
			return (($this->Player1 == $player) ? $this->Note1 : $this->Note2);
		}
		
		public function SetNote($player, $new_content)
		{
			if ($this->Player1 == $player) $this->Note1 = $new_content;
			else $this->Note2 = $new_content;
		}
		
		public function ClearNote($player)
		{
			if ($this->Player1 == $player) $this->Note1 = '';
			else $this->Note2 = '';
		}
		
		public function GetGameMode($game_mode)
		{
			return $this->$game_mode;
		}
		
		public function SetGameModes($game_modes)
		{
			$db = $this->Games->getDB();
			$result = $db->Query('UPDATE `games` SET `GameModes` = "'.$db->Escape($game_modes).'" WHERE `GameID` = "'.$db->Escape($this->GameID).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function LoadGame()
		{
			$db = $this->Games->getDB();
			$result = $db->Query('SELECT `State`, `Current`, `Round`, `Winner`, `Surrender`, `EndType`, `Last Action`, `Data`, `Note1`, `Note2`, `GameModes` FROM `games` WHERE `GameID` = "'.$db->Escape($this->GameID).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$this->State = $data['State'];
			$this->Current = $data['Current'];
			$this->Round = $data['Round'];
			$this->Winner = $data['Winner'];
			$this->Surrender = $data['Surrender'];
			$this->EndType = $data['EndType'];
			$this->LastAction = $data['Last Action'];
			$this->Note1 = $data['Note1'];
			$this->Note2 = $data['Note2'];
			$this->HiddenCards = (strpos($data['GameModes'], 'HiddenCards') !== false) ? 'yes' : 'no';
			$this->FriendlyPlay = (strpos($data['GameModes'], 'FriendlyPlay') !== false) ? 'yes' : 'no';
			$this->GameData = unserialize($data['Data']);
			
			return true;
		}
		
		public function SaveGame()
		{
			$db = $this->Games->getDB();
			$result = $db->Query('UPDATE `games` SET `State` = "'.$db->Escape($this->State).'", `Current` = "'.$db->Escape($this->Current).'", `Round` = "'.$db->Escape($this->Round).'", `Winner` = "'.$db->Escape($this->Winner).'", `Surrender` = "'.$db->Escape($this->Surrender).'", `EndType` = "'.$db->Escape($this->EndType).'", `Last Action` = "'.$db->Escape($this->LastAction).'", `Data` = "'.$db->Escape(serialize($this->GameData)).'", `Note1` = "'.$db->Escape($this->Note1).'", `Note2` = "'.$db->Escape($this->Note2).'" WHERE `GameID` = "'.$db->Escape($this->GameID).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function StartGame($player, $deck)
		{
			global $game_config;
			
			$this->GameData[$player] = new CGamePlayerData;
			$this->GameData[$player]->Deck = $deck;
			
			$this->State = 'in progress';
			$this->LastAction = date('Y-m-d G:i:s');
			$this->Current = ((mt_rand(0,1) == 1) ? $this->Player1 : $this->Player2);
			
			$p1 = &$this->GameData[$this->Player1];
			$p2 = &$this->GameData[$this->Player2];
			
			$p1->LastCard[1] = $p2->LastCard[1] = 0;
			$p1->LastMode[1] = $p2->LastMode[1] = 0;
			$p1->LastAction[1] = $p2->LastAction[1] = 'play';
			$p1->NewCards = $p2->NewCards = $p1->Revealed = $p2->Revealed = null;
			$p1->DisCards[0] = $p1->DisCards[1] = $p2->DisCards[0] = $p2->DisCards[1] = null; //0 - cards discarded from my hand, 1 - discarded from opponents hand
			$p1->Changes = $p2->Changes = array ('Quarry'=> 0, 'Magic'=> 0, 'Dungeons'=> 0, 'Bricks'=> 0, 'Gems'=> 0, 'Recruits'=> 0, 'Tower'=> 0, 'Wall'=> 0);
			$p1->Tower = $p2->Tower = $game_config['init_tower'];
			$p1->Wall = $p2->Wall = $game_config['init_wall'];
			$p1->Quarry = $p2->Quarry = 3;
			$p1->Magic = $p2->Magic = 3;
			$p1->Dungeons = $p2->Dungeons = 3;
			$p1->Bricks = $p2->Bricks = 15;
			$p1->Gems = $p2->Gems = 5;
			$p1->Recruits = $p2->Recruits = 10;
			
			// add starting bonus to second player
			if ($this->Current == $this->Player1)
			{
				$p2->Bricks+= 1;
				$p2->Gems+= 1;
				$p2->Recruits+= 1;
			}
			else
			{
				$p1->Bricks+= 1;
				$p1->Gems+= 1;
				$p1->Recruits+= 1;
			}
			
			// initialize tokens
			$p1->TokenNames = $p1->Deck->Tokens;
			$p2->TokenNames = $p2->Deck->Tokens;
			
			$p1->TokenValues = $p1->TokenChanges = array_fill_keys(array_keys($p1->TokenNames), 0);
			$p2->TokenValues = $p2->TokenChanges = array_fill_keys(array_keys($p2->TokenNames), 0);
			
			$p1->Hand = $this->DrawHand_norare($p1->Deck);
			$p2->Hand = $this->DrawHand_norare($p2->Deck);
		}
		
		public function SurrenderGame()
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress' OR $this->Surrender == '') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->Winner = ($this->Player1 == $this->Surrender) ? $this->Player2 : $this->Player1;
			$this->EndType = 'Surrender';
			$this->SaveGame();
			
			return 'OK';
		}

		public function RequestSurrender($playername)
		{
			// only allow to request for surrender if the game is still on
			if ($this->State != 'in progress' OR $this->Surrender != '') return 'Action not allowed!';

			$this->Surrender = $playername;
			$this->SaveGame();

			return 'OK';
		}

		public function CancelSurrender()
		{
			// only allow to cancel surrender request if the game is still on
			if ($this->State != 'in progress' OR $this->Surrender == '') return 'Action not allowed!';

			$this->Surrender = '';
			$this->SaveGame();

			return 'OK';
		}

		public function AbortGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->Winner = '';
			$this->EndType = 'Abort';
			$this->SaveGame();
			
			return 'OK';
		}
		
		public function FinishGame($playername)
		{
			// only allow surrender if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			$this->State = 'finished';
			$this->Winner = ($this->Player1 == $playername) ? $this->Player1 : $this->Player2;
			$opponent = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$this->EndType = 'Abandon';
			$this->SaveGame();
			
			return 'OK';
		}
		
		public function PlayCard($playername, $cardpos, $mode, $action)
		{
			global $carddb;
			global $keyworddb;
			global $statistics;
			global $game_config;
			
			// only allow discarding if the game is still on
			if ($this->State != 'in progress') return 'Action not allowed!';
			
			// only allow action when it's the players' turn
			if ($this->Current != $playername) return 'Action only allowed on your turn!';
			
			// anti-hack
			if (($cardpos < 1) || ($cardpos > 8)) return 'Wrong card position!';
			if (($action != 'play') && ($action != 'discard')) return 'Invalid action!';
			
			// game configuration
			$max_tower = $game_config['max_tower'];
			$max_wall = $game_config['max_wall'];
			$init_tower = $game_config['init_tower'];
			$init_wall = $game_config['init_wall'];
			$res_vic = $game_config['res_victory'];
			$time_vic = $game_config['time_victory'];
			
			// prepare basic information
			$opponent = ($this->Player1 == $playername) ? $this->Player2 : $this->Player1;
			$mydata = &$this->GameData[$playername];
			$hisdata = &$this->GameData[$opponent];
			
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
			$hidden_cards = ($this->HiddenCards == 'yes');
			
			//we need to store this information, because some cards will need it to make their effect, however after effect this information is not stored
			$mychanges = $mydata->Changes;
			$hischanges = $hisdata->Changes;
			$mynewflags = $mydata->NewCards;
			$hisnewflags = $hisdata->NewCards;
			$discarded_cards[0] = $mydata->DisCards[0];
			$discarded_cards[1] = $mydata->DisCards[1];
			
			// create a copy of interesting game attributes
			$attributes = array('Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall');
			$mydata_temp = $hisdata_temp = array();
			
			foreach ($attributes as $attribute)
			{
				$mydata_temp[$attribute] = $mydata->$attribute;
				$hisdata_temp[$attribute] = $hisdata->$attribute;
			}
			
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
				// verify mode (depends on card)
				if( $mode < 0
				||  $mode > $card->CardData->Modes
				||  $mode == 0 && $card->CardData->Modes > 0
				  ) return 'Bad mode!';
				
				// verify if there are enough resources
				if (($mydata->Bricks < $card->CardData->Bricks) || ($mydata->Gems < $card->CardData->Gems) || ($mydata->Recruits < $card->CardData->Recruits)) return 'Insufficient resources!';
				
				$mydata->Bricks-= $card->CardData->Bricks;
				$mydata->Gems-= $card->CardData->Gems;
				$mydata->Recruits-= $card->CardData->Recruits;
				
				// update copy of game attributes (card cost was substracted)
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
				
				// execute card action !!!
				if( eval($card->CardData->Code) === FALSE )
					error_log("Debug: ".$cardid.": ".$card->CardData->Code);

				// keyword processing
				if ($card->CardData->Keywords != '')
				{
					// list all keywords in order they are to be executed
					$category_keywords = array('Alliance', 'Aqua', 'Barbarian', 'Beast', 'Brigand', 'Burning', 'Destruction', 'Dragon', 'Holy', 'Illusion', 'Legend', 'Mage', 'Nature', 'Restoration', 'Soldier', 'Titan', 'Undead', 'Unliving');
					$effect_keywords = array('Durable', 'Quick', 'Swift', 'Far sight', 'Banish', 'Skirmisher', 'Rebirth', 'Flare attack', 'Frenzy', 'Enduring', 'Charge');

					$keywords = array_merge($category_keywords, $effect_keywords);
					foreach ($keywords as $keyword_name)
						if ($card->HasKeyWord($keyword_name))
						{
							$keyword = $keyworddb->GetKeyword($keyword_name);
							if( eval($keyword->Code) === FALSE )
								error_log("Debug: ".$keyword_name.": ".$keyword->Code);
						}
				}

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
						$statistics->UpdateCardStats($myhand[$i], 'discard'); // update card statistics (card discarded by card effect)
						// hide revealed card if it was revealed before and discarded now
						if (isset($mydata->Revealed[$i])); unset($mydata->Revealed[$i]);
					}
					
					if (((!(isset($hisnewcards[$i]))) and (isset($hisdata->NewCards[$i]))) or ($hishand[$i] != $hisdata->Hand[$i]))
					{
						$hisdiscards_index++;
						$mydata->DisCards[1][$hisdiscards_index] = $hishand[$i];
						$statistics->UpdateCardStats($hishand[$i], 'discard'); // update card statistics (card discarded by card effect)
						// hide revealed card if it was revealed before and discarded now
						if (isset($hisdata->Revealed[$i])); unset($hisdata->Revealed[$i]);
					}
					
				}
				
				// apply limits to game attributes
				$this->ApplyGameLimits($mydata);
				$this->ApplyGameLimits($hisdata);
				
				// apply limits to token counters
				foreach ($mytokens_temp as $index => $token_val)
				{
					$mydata->TokenValues[$index] = max(min($mydata->TokenValues[$index], 100), 0);
					$hisdata->TokenValues[$index] = max(min($hisdata->TokenValues[$index], 100), 0);
				}
				
				// compute changes on token counters
				foreach ($mytokens_temp as $index => $token_val)
				{
					$mydata->TokenChanges[$index] += $mydata->TokenValues[$index] - $mytokens_temp[$index];
					$hisdata->TokenChanges[$index] += $hisdata->TokenValues[$index] - $histokens_temp[$index];
				}
			}
			
			// add production at the end of turn
			$mydata->Bricks+= $bricks_production * $mydata->Quarry;
			$mydata->Gems+= $gems_production * $mydata->Magic;
			$mydata->Recruits+= $recruits_production * $mydata->Dungeons;
			
			// compute changes on game attributes
			$attributes = array('Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall');
			foreach ($attributes as $attribute)
			{
				$mydata->Changes[$attribute]+= $mydata->$attribute - $mydata_temp[$attribute];
				$hisdata->Changes[$attribute]+= $hisdata->$attribute - $hisdata_temp[$attribute];
			}
			
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
			if (isset($mydata->Revealed[$cardpos])); unset($mydata->Revealed[$cardpos]);
			
			// check victory conditions (in this predetermined order)
			if(     $mydata->Tower > 0 and $hisdata->Tower <= 0 )
			{	// tower destruction victory - player
				$this->Winner = $playername;
				$this->EndType = 'Destruction';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower <= 0 and $hisdata->Tower > 0 )
			{	// tower destruction victory - opponent
				$this->Winner = $opponent;
				$this->EndType = 'Destruction';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower <= 0 and $hisdata->Tower <= 0 )
			{	// tower destruction victory - draw
				$this->Winner = '';
				$this->EndType = 'Draw';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower >= $max_tower and $hisdata->Tower < $max_tower )
			{	// tower building victory - player
				$this->Winner = $playername;
				$this->EndType = 'Construction';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower < $max_tower and $hisdata->Tower >= $max_tower )
			{	// tower building victory - opponent
				$this->Winner = $opponent;
				$this->EndType = 'Construction';
				$this->State = 'finished';
			}
			elseif( $mydata->Tower >= $max_tower and $hisdata->Tower >= $max_tower )
			{	// tower building victory - draw
				$this->Winner = '';
				$this->EndType = 'Draw';
				$this->State = 'finished';
			}
			elseif( ($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= $res_vic and !(($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= $res_vic) )
			{	// resource accumulation victory - player
				$this->Winner = $playername;
				$this->EndType = 'Resource';
				$this->State = 'finished';
			}
			elseif( ($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= $res_vic and !(($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= $res_vic) )
			{	// resource accumulation victory - opponent
				$this->Winner = $opponent;
				$this->EndType = 'Resource';
				$this->State = 'finished';
			}
			elseif( ($mydata->Bricks + $mydata->Gems + $mydata->Recruits) >= $res_vic and ($hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits) >= $res_vic )
			{	// resource accumulation victory - draw
				$this->Winner = '';
				$this->EndType = 'Draw';
				$this->State = 'finished';
			}
			elseif( $this->Round >= $time_vic )
			{	// timeout victory
				$this->EndType = 'Timeout';
				$this->State = 'finished';
				
				// compare towers
				if    ( $mydata->Tower > $hisdata->Tower ) $this->Winner = $playername;
				elseif( $mydata->Tower < $hisdata->Tower ) $this->Winner = $opponent;
				// compare walls
				elseif( $mydata->Wall > $hisdata->Wall ) $this->Winner = $playername;
				elseif( $mydata->Wall < $hisdata->Wall ) $this->Winner = $opponent;
				// compare facilities
				elseif( $mydata->Quarry + $mydata->Magic + $mydata->Dungeons > $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons ) $this->Winner = $playername;
				elseif( $mydata->Quarry + $mydata->Magic + $mydata->Dungeons < $hisdata->Quarry + $hisdata->Magic + $hisdata->Dungeons ) $this->Winner = $opponent;
				// compare resources
				elseif( $mydata->Bricks + $mydata->Gems + $mydata->Recruits > $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits ) $this->Winner = $playername;
				elseif( $mydata->Bricks + $mydata->Gems + $mydata->Recruits < $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits ) $this->Winner = $opponent;
				// else draw
				else
				{
					$this->Winner = '';
					$this->EndType = 'Draw';
				}
			}
			else
			{	//game continues
				$this->Current = $nextplayer;
				$this->LastAction = date('Y-m-d G:i:s');
				if( $nextplayer != $playername )
					$this->Round++;
			}
			
			// update card statistics (card was played or discarded by standard discard action)
			$statistics->UpdateCardStats($cardid, $action);
			
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
					else $keywords_list.= ",".$keyword;
			}
			
			if ($keywords_list == "") return 0; // no keywords in hand
			
			$words = explode(",", $keywords_list); // split individual keywords
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
			global $statistics;

			while (1)
			{
				$nextcard = $this->$draw_function($source, $hand[$card_pos]);
				
				// count the number of occurences of the same card on other slots
				$match = 0;
				for ($i = 1; $i <= 8; $i++)
					if (($hand[$i] == $nextcard) and ($card_pos != $i))
						$match++; //do not count the card already played
				
				if (mt_rand(1, pow(2, $match)) == 1)
				{
					$statistics->UpdateCardStats($nextcard, 'draw');
					return $nextcard; // chance to retain the card decreases exponentially as the number of matches increases
				}
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
			if (count($list) == 0) return 0; // "empty slot" card
			return $list[array_rand($list)];
		}
		
		// returns a new hand consisting of type-random cards chosen from the specified deck
		private function DrawHand_random(CDeckData $deck)
		{
			return $this->DrawHand($deck, 'DrawCard_random');
		}
		
		// returns a new hand consisting of type-random cards chosen from the specified deck (excluding rare cards)
		private function DrawHand_norare(CDeckData $deck)
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
		
		private function ApplyGameLimits(CGamePlayerData &$data)
		{
			global $game_config;
			
			$data->Quarry = max($data->Quarry, 1);
			$data->Magic = max($data->Magic, 1);
			$data->Dungeons = max($data->Dungeons, 1);
			$data->Bricks = max($data->Bricks, 0);
			$data->Gems = max($data->Gems, 0);
			$data->Recruits = max($data->Recruits, 0);
			$data->Tower = min(max($data->Tower, 0), $game_config['max_tower']);
			$data->Wall = min(max($data->Wall, 0), $game_config['max_wall']);
		}
		
		public function CalculateExp($player)
		{
			global $carddb;
			global $playerdb;
			
			$opponent = ($this->Player1 == $player) ? $this->Player2 : $this->Player1;
			$mydata = $this->GameData[$player];
			$hisdata = $this->GameData[$opponent];
			$round = $this->Round;
			$winner = $this->Winner;
			$endtype = $this->EndType;
			$mylevel = $playerdb->GetLevel($player);
			$hislevel = $playerdb->GetLevel($opponent);
			
			$win = ($player == $winner);
			$exp = 100; // base exp
			$message = 'Base = '.$exp.' EXP'."\n";
			
			// first phase: Game rating
			if ($endtype == 'Resource' AND $win) $mod = 1.15;
			elseif ($endtype == 'Construction' AND $win) $mod = 1.10;
			elseif ($endtype == 'Destruction' AND $win) $mod = 1.05;
			elseif ($endtype == 'Abandon' AND $win) $mod = 1;
			elseif ($endtype == 'Surrender' AND $win) $mod = 0.95;
			elseif ($endtype == 'Timeout' AND $win) $mod = 0.6;
			elseif ($endtype == 'Draw') $mod = 0.5;
			elseif ($endtype == 'Timeout' AND !$win) $mod = 0.4;
			elseif ($endtype == 'Destruction' AND !$win) $mod = 0.15;
			elseif ($endtype == 'Construction' AND !$win) $mod = 0.1;
			elseif ($endtype == 'Resource' AND !$win) $mod = 0.05;
			elseif ($endtype == 'Surrender' AND !$win) $mod = 0;
			elseif ($endtype == 'Abandon' AND !$win) $mod = 0;
			else $mod = 0; // should never happen
			
			// update exp and message
			$exp = round($exp * $mod);
			$message.= 'Game rating'."\n".'Modifier: '.$mod.', Total: '.$exp.' EXP'."\n";
			
			// second phase: Opponent rating
			if ($mylevel > $hislevel) $mod = 1 - 0.05 * min(10, $mylevel - $hislevel);
			elseif ($mylevel < $hislevel) $mod = 1 + 0.1 * min(10, $hislevel - $mylevel);
			else $mod = 1;
			
			// update exp and message
			$exp = round($exp * $mod);
			$message.= 'Opponent rating'."\n".'Modifier: '.$mod.', Total: '.$exp.' EXP'."\n";
			
			// third phase: Victory rating
			if ($win)// if player is winner
			{
				$bonus = array('major' => 1.75, 'minor' => 1.25, 'tactical' => 1);
				$victories = array();
				
				// Resource accumulation victory
				$enemy_stock = $hisdata->Bricks + $hisdata->Gems + $hisdata->Recruits;
				if ($enemy_stock < 150) $victories[] = 'major';
				elseif (($enemy_stock >= 150) AND ($enemy_stock <= 300)) $victories[] = 'minor';
				else $victories[] = 'tactical';
				
				// Tower building victory
				if ($hisdata->Tower < 30) $victories[] = 'major';
				elseif (($hisdata->Tower >= 30) AND ($hisdata->Tower <= 60)) $victories[] = 'minor';
				else $victories[] = 'tactical';
				
				// Tower destruction victory
				if ($mydata->Tower > 60) $victories[] = 'major';
				elseif (($mydata->Tower >= 30) AND ($mydata->Tower <= 60)) $victories[] = 'minor';
				else $victories[] = 'tactical';
				
				sort($victories);
				$victory = array_pop($victories); // pick lowest victory rating
				$mod = $bonus[$victory];
				
				// update exp and message
				$exp = round($exp * $mod);
				$message.= 'Victory rating'."\n".'Modifier: '.$mod.', Total: '.$exp.' EXP'."\n";
			}
			else // if player is loser
			{
				$bonus = array('major' => 1.75, 'minor' => 1.25, 'tactical' => 1);
				$victories = array();
				
				// Resource accumulation victory
				$stock = $mydata->Bricks + $mydata->Gems + $mydata->Recruits;
				if ($stock > 300) $victories[] = 'major';
				elseif (($stock >= 150) AND ($stock <= 300)) $victories[] = 'minor';
				else $victories[] = 'tactical';
				
				// Tower building victory
				if ($mydata->Tower > 60) $victories[] = 'major';
				elseif (($mydata->Tower >= 30) AND ($mydata->Tower <= 60)) $victories[] = 'minor';
				else $victories[] = 'tactical';
				
				// Tower destruction victory
				if ($hisdata->Tower < 30) $victories[] = 'major';
				elseif (($hisdata->Tower >= 30) AND ($hisdata->Tower <= 60)) $victories[] = 'minor';
				else $victories[] = 'tactical';
				
				sort($victories);
				$victory = array_shift($victories); // pick highest victory rating
				$mod = $bonus[$victory];
				
				// update exp and message
				$exp = round($exp * $mod);
				$message.= 'Victory rating'."\n".'Modifier: '.$mod.', Total: '.$exp.' EXP'."\n";
			}
			
			//fourth phase: Awards
			if ($win)
			{
				$mylastcardindex = count($mydata->LastCard);
				$mylast_card = $carddb->GetCard($mydata->LastCard[$mylastcardindex]);
				$mylast_action = $mydata->LastAction[$mylastcardindex];
				$standard_victory = ($endtype == 'Resource' OR $endtype == 'Construction' OR $endtype == 'Destruction');
				
				$awards = array('Assassin' => 0.5, 'Survivor' => 0.9, 'Desolator' => 0.3, 'Builder' => 0.8, 'Gentle touch' => 0.2, 'Collector' => 0.7, 'Titan' => 0.45);
				$recieved = array();
				
				if ($round < 10 AND $standard_victory) $recieved[] = 'Assassin';// Assassin
				if ($hisdata->Quarry == 1 AND $hisdata->Magic == 1 AND $hisdata->Dungeons == 1) $recieved[] = 'Desolator'; // Desolator
				if ($mydata->Wall == 150) $recieved[] = 'Builder'; // Builder
				if ($mylast_card->GetClass() == 'Common' AND $mylast_action == 'play' AND $standard_victory) $recieved[] = 'Gentle touch'; // Gentle touch
				$tmp = 0;
				for ($i = 1; $i <= 8; $i++)
				{
					$cur_card = $carddb->GetCard($mydata->Hand[$i]);
					if ($cur_card->GetClass() == "Rare") $tmp++;
				}
				if ($tmp >= 4) $recieved[] = 'Collector'; // Collector
				if ($mylast_card->GetID() == 315 AND $mylast_action == 'play' AND $endtype == 'Destruction') $recieved[] = 'Titan'; // Titan
				if (($mydata->Tower == 1) AND ($mydata->Wall == 0)) $recieved[] = 'Survivor'; // Survivor
				
				// update exp and message
				if (count($recieved) > 0)
				{
					$mod = 0;
					$award_temp = array();
					foreach ($recieved as $award)
					{
						$mod+= $awards[$award];
						$award_temp[] = $award.' ('.$awards[$award].')';
					}
					$tmp = round($exp * (1 + $mod));
					$message.= 'Awards'."\n".implode(", ", $award_temp)."\n".'Bonus: '.$mod.', Total: '.($tmp - $exp).' EXP'."\n";
					$exp = $tmp;
				}
				else $message.= 'Awards'."\n".'None achieved'."\n";
			}
			
			// finalize report
			$message.= "\n".'You gained '.$exp.' EXP';
			
			return array('exp' => $exp, 'message' => $message);
		}
	}
	
	
	class CGamePlayerData
	{
		public $Deck; // CDeckData
		public $Hand; // array ($i => $cardid)
		public $LastCard; // list of cards played last turn (in the order they were played)
		public $LastMode; // list of modes corresponding to cards played last turn (each is 0 or 1-8)
		public $LastAction; // list of actions corresponding to cards played last turn ('play'/'discard')
		public $NewCards; // associative array, where keys are card positions which have changed (values are arbitrary at the moment)
		public $Revealed; // associative array, where keys are card positions which are revealed (values are arbitrary at the moment)
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
