<?php
/*
	CReplay - game replay
*/
?>
<?php
	class CReplays
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
		
		public function CreateReplay(CGame $game) // start recording a game
		{
			$db = $this->db;
			
			$game_id = $game->ID();
			$player1 = $game->Name1();
			$player2 = $game->Name2();
			
			// transform real names to symbolic names (clone prevents original game object from being damaged)
			$game_data[1] = clone $game->GameData[$player1];
			$game_data[2] = clone $game->GameData[$player2];
			
			// remove decks (replays don't need them)
			unset($game_data[1]->Deck);
			unset($game_data[2]->Deck);
			
			// prepare data of the first turn of the replay
			$turn_data = new CReplayTurn;
			$turn_data->Current = ($game->Current == $player1) ? 1 : 2;
			$turn_data->Round = 1; // first round
			$turn_data->GameData = $game_data;
			$replay_data[1] = $turn_data;
			
			$hidden_cards = $game->GetGameMode('HiddenCards');
			$friendly_play = $game->GetGameMode('FriendlyPlay');
			$long_mode = $game->GetGameMode('LongMode');
			$ai_mode = $game->GetGameMode('AIMode');
			$game_modes = array();
			if ($hidden_cards == "yes") $game_modes[] = 'HiddenCards';
			if ($friendly_play == "yes") $game_modes[] = 'FriendlyPlay';
			if ($long_mode == "yes") $game_modes[] = 'LongMode';
			if ($ai_mode == "yes") $game_modes[] = 'AIMode';
			$game_modes = implode(',', $game_modes);
			$ai = $game->AI;
			
			$result = $db->Query('INSERT INTO `replays` (`GameID`, `Player1`, `Player2`, `Data`, `GameModes`, `AI`) VALUES (?, ?, ?, ?, ?, ?)', array($game_id, $player1, $player2, gzcompress(serialize($replay_data)), $game_modes, $ai));
			
			return true;
		}
		
		public function DeleteReplay($gameid) // delete unfinished replay
		{
			$db = $this->db;
			
			$result = $db->Query('DELETE FROM `replays` WHERE `GameID` = ?', array($gameid));
			if ($result === false) return false;
			
			return true;
		}
		
		public function GetReplay($game_id)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `Player1`, `Player2` FROM `replays` WHERE `GameID` = ?', array($game_id));
			if ($result === false) return false;
			if (count($result) == 0) return 0;
			
			$players = $result[0];
			$player1 = $players['Player1'];
			$player2 = $players['Player2'];
			
			$replay = new CReplay($game_id, $player1, $player2, $this);
			$result = $replay->Load();
			if (!$result) return $result;
			
			return $replay;
		}
		
		public function ListReplays($player, $hidden, $friendly, $long, $ai, $challenge, $victory, $page, $condition, $order)
		{
			$db = $this->db;
			
			$victory_q = ($victory != "none") ? '`EndType` = ?' : '`EndType` != "Pending"';
			$player_q = ($player != "") ? 'AND ((`Player1` LIKE ?) OR (`Player2` LIKE ?))' : '';
			$hidden_q = ($hidden != "none") ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) '.(($hidden == "include") ? '>' : '=').' 0' : '';
			$friendly_q = ($friendly != "none") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) '.(($friendly == "include") ? '>' : '=').' 0' : '';
			$long_q = ($long != "none") ? ' AND FIND_IN_SET("LongMode", `GameModes`) '.(($long == "include") ? '>' : '=').' 0' : '';
			$ai_q = ($ai != "none") ? ' AND FIND_IN_SET("AIMode", `GameModes`) '.(($ai == "include") ? '>' : '=').' 0' : '';
			$ch_q = ($challenge != "none") ? (($challenge == 'include') ? ' AND `AI` != ""' : (($challenge == 'exclude') ? ' AND `AI` = ""' : ' AND `AI` = ?')) : '';

			$params = array();
			if ($victory != "none") $params[] = $victory;
			if ($player != "") { $params[] = '%'.$player.'%'; $params[] = '%'.$player.'%'; }
			if (!in_array($challenge, array('none', 'include', 'exclude'))) $params[] = $challenge;

			$condition = (in_array($condition, array('Winner', 'Rounds', 'Turns', 'Started', 'Finished'))) ? $condition : 'Finished';
			$order = ($order == 'ASC') ? 'ASC' : 'DESC';
			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->Query('SELECT `GameID`, `Player1`, `Player2`, `Started`, `Finished`, `Rounds`, `Turns`, `GameModes`, `AI`, `Winner`, `EndType`, (CASE WHEN `Deleted` = TRUE THEN "yes" ELSE "no" END) as `Deleted`, `Views` FROM `replays` WHERE '.$victory_q.$player_q.$hidden_q.$friendly_q.$long_q.$ai_q.$ch_q.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(REPLAYS_PER_PAGE * $page).' , '.REPLAYS_PER_PAGE.'', $params);
			if ($result === false) return false;

			return $result;
		}
		
		public function CountPages($player, $hidden, $friendly, $long, $ai, $challenge, $victory)
		{	
			$db = $this->db;
			
			$victory_q = ($victory != "none") ? '`EndType` = ?' : '`EndType` != "Pending"';
			$player_q = ($player != "") ? 'AND ((`Player1` LIKE ?) OR (`Player2` LIKE ?))' : '';
			$hidden_q = ($hidden != "none") ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) '.(($hidden == "include") ? '>' : '=').' 0' : '';
			$friendly_q = ($friendly != "none") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) '.(($friendly == "include") ? '>' : '=').' 0' : '';
			$long_q = ($long != "none") ? ' AND FIND_IN_SET("LongMode", `GameModes`) '.(($long == "include") ? '>' : '=').' 0' : '';
			$ai_q = ($ai != "none") ? ' AND FIND_IN_SET("AIMode", `GameModes`) '.(($ai == "include") ? '>' : '=').' 0' : '';
			$ch_q = ($challenge != "none") ? (($challenge == 'include') ? ' AND `AI` != ""' : (($challenge == 'exclude') ? ' AND `AI` = ""' : ' AND `AI` = ?')) : '';

			$params = array();
			if ($victory != "none") $params[] = $victory;
			if ($player != "") { $params[] = '%'.$player.'%'; $params[] = '%'.$player.'%'; }
			if (!in_array($challenge, array('none', 'include', 'exclude'))) $params[] = $challenge;
			
			$result = $db->Query('SELECT COUNT(`GameID`) as `Count` FROM `replays` WHERE '.$victory_q.$player_q.$hidden_q.$friendly_q.$long_q.$ai_q.$ch_q.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			$pages = ceil($data['Count'] / REPLAYS_PER_PAGE);
			
			return $pages;
		}
		
		public function IncrementViews($game_id) // increment number of views for the specified replay
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `replays` SET `Views` = `Views` + 1 WHERE `GameID` = ?', array($game_id));
			if ($result === false) return false;
			
			return true;
		}

		public function AssignThread($replay_id, $thread_id)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `replays` SET `ThreadID` = ? WHERE `GameID` = ?', array($thread_id, $replay_id));
			if ($result === false) return false;

			return true;
		}

		public function RemoveThread($replay_id)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `replays` SET `ThreadID` = 0 WHERE `GameID` = ?', array($replay_id));
			if ($result === false) return false;

			return true;
		}

		public function FindReplay($thread_id)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `GameID` FROM `replays` WHERE `ThreadID` = ?', array($thread_id));
			if ($result === false or count($result) == 0) return 0;

			$data = $result[0];

			return $data['GameID'];
		}
	}
	
	
	class CReplay
	{
		private $Replays;
		private $GameID;
		private $Player1;
		private $Player2;
		private $HiddenCards;
		private $FriendlyPlay;
		private $LongMode;
		private $AIMode;
		private $ReplayData; // array (turn => CReplayTurn)
		public $Rounds;
		public $Turns;
		public $Winner;
		public $EndType;
		public $AI;
		public $ThreadID;
		
		public function __construct($gameid, $player1, $player2, CReplays $Replays)
		{
			$this->GameID = $gameid;
			$this->Player1 = $player1;
			$this->Player2 = $player2;
			$this->Replays = &$Replays;
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
		
		public function GetGameMode($game_mode)
		{
			return $this->$game_mode;
		}
		
		public function Load()
		{
			$db = $this->Replays->getDB();

			$result = $db->Query('SELECT `Rounds`, `Turns`, `Data`, `Winner`, `EndType`, `GameModes`, `AI`, `ThreadID` FROM `replays` WHERE `Deleted` = FALSE AND `GameID` = ?', array($this->ID()));
			if ($result === false) return false;
			if (count($result) == 0) return 0;

			$data = $result[0];
			$this->Rounds = $data['Rounds'];
			$this->Turns = $data['Turns'];
			$this->Winner = $data['Winner'];
			$this->EndType = $data['EndType'];
			$this->HiddenCards = (strpos($data['GameModes'], 'HiddenCards') !== false) ? 'yes' : 'no';
			$this->FriendlyPlay = (strpos($data['GameModes'], 'FriendlyPlay') !== false) ? 'yes' : 'no';
			$this->LongMode = (strpos($data['GameModes'], 'LongMode') !== false) ? 'yes' : 'no';
			$this->AIMode = (strpos($data['GameModes'], 'AIMode') !== false) ? 'yes' : 'no';
			$this->ThreadID = $data['ThreadID'];
			$this->AI = $data['AI'];
			$this->ReplayData = unserialize(gzuncompress($data['Data']));

			return true;
		}
		
		public function Save()
		{
			$db = $this->Replays->getDB();
			
			$result = $db->Query('UPDATE `replays` SET `Rounds` = ?, `Turns` = ?, `Data` = ? WHERE `GameID` = ?', array($this->Rounds, $this->Turns, gzcompress(serialize($this->ReplayData)), $this->GameID));
			if ($result === false) return false;
			
			return true;
		}
		
		public function Update(CGame $game) // update replay data
		{
			$this->Turns++;
			$this->Rounds = $game->Round;
			$player1 = $game->Name1();
			$player2 = $game->Name2();
			
			// transform real names to symbolic names (clone prevents original game object from being damaged)
			$game_data[1] = clone $game->GameData[$player1];
			$game_data[2] = clone $game->GameData[$player2];
			
			// remove decks (replays don't need them)
			unset($game_data[1]->Deck);
			unset($game_data[2]->Deck);
			
			// prepare data of the current turn of the replay
			$turn_data = new CReplayTurn;
			$turn_data->Current = ($game->Current == $player1) ? 1 : 2;
			$turn_data->Round = $game->Round;
			$turn_data->GameData = $game_data;
			$this->ReplayData[$this->Turns] = $turn_data;
			
			// finish replay in case the game is finished
			if ($game->State == 'finished' and !$this->Finish($game)) return false;
			
			return $this->Save();
		}
		
		public function Finish(CGame $game) // finish recording a game
		{
			$db = $this->Replays->getDB();
			
			$result = $db->Query('UPDATE `replays` SET `Winner` = ?, `EndType` = ?, `Finished` = CURRENT_TIMESTAMP WHERE `GameID` = ?', array($game->Winner, $game->EndType, $game->ID()));
			if ($result === false) return false;
			
			return true;
		}
		
		public function GetTurn($turn_number)
		{
			if (!is_numeric($turn_number) or $turn_number < 1 or $turn_number > $this->Turns or !isset($this->ReplayData[$turn_number])) return false;

			$turn_data = clone $this->ReplayData[$turn_number];

			// transform symbolic names to real names
			$data[$this->Player1] = $turn_data->GameData[1];
			$data[$this->Player2] = $turn_data->GameData[2];
			$turn_data->GameData = $data;
			$turn_data->Current = ($turn_data->Current == 1) ? $this->Player1 : $this->Player2;

			return $turn_data;
		}
		
		public function IncrementViews() // increment number of views for the specified replay
		{
			return $this->Replays->IncrementViews($this->ID());
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

		public function AssignThread($thread_id)
		{
			return $this->Replays->AssignThread($this->ID(), $thread_id);
		}

		public function LastRound() // search for the first turn of the current round
		{
			$turn = $this->Turns;
			$round = $this->Rounds;
			$result = false;

			while( ($round == $this->Rounds) and ($turn >= 1) )
			{
				$turn_data = $this->GetTurn($turn);
				if (!$turn_data) return false;

				$round = $turn_data->Round;
				if ($round == $this->Rounds) $result = $turn_data;

				$turn--;
			}

			return $result;
		}
	}
	
	class CReplayTurn
	{
		public $Current;
		public $Round;
		public $GameData;
	}
?>
