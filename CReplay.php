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
			$current = $game->Current;
			
			$data[$player1] = $this->ConvertData($game->GameData[$player1]);
			$data[$player2] = $this->ConvertData($game->GameData[$player2]);
			$data = serialize($data);
			
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
			
			$result = $db->Query('INSERT INTO `replays_head` (`GameID`, `Player1`, `Player2`, `GameModes`, `AI`) VALUES ("'.$db->Escape($game_id).'", "'.$db->Escape($player1).'", "'.$db->Escape($player2).'", "'.$db->Escape($game_modes).'", "'.$db->Escape($ai).'")');
			if ($result === false) return false;
			
			$result = $db->Query('INSERT INTO `replays_data` (`GameID`, `Current`, `Data`) VALUES ("'.$db->Escape($game_id).'", "'.$db->Escape($current).'", "'.$db->Escape($data).'")');
			if ($result === false) return false;
			
			return true;
		}
		
		public function DeleteReplay($gameid) // delete unfinished replay
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `replays_data` WHERE `GameID` = "'.$db->Escape($gameid).'"');
			if ($result === false) return false;
			
			$result = $db->Query('DELETE FROM `replays_head` WHERE `GameID` = "'.$db->Escape($gameid).'"');
			if ($result === false) return false;
			
			return true;
		}
		
		public function UpdateReplay(CGame $game) // update replay data
		{
			$db = $this->db;
			
			$game_id = $game->ID();
			$player1 = $game->Name1();
			$player2 = $game->Name2();
			$current = $game->Current;
			$round = $game->Round;
			
			$data[$player1] = $this->ConvertData($game->GameData[$player1]);
			$data[$player2] = $this->ConvertData($game->GameData[$player2]);
			$data = serialize($data);
			
			$turn = $this->NumberOfTurns($game_id) + 1;
			
			$result = $db->Query('INSERT INTO `replays_data` (`GameID`, `Turn`, `Current`, `Round`, `Data`) VALUES ("'.$db->Escape($game_id).'", "'.$db->Escape($turn).'", "'.$db->Escape($current).'", "'.$db->Escape($round).'", "'.$db->Escape($data).'")');
			if ($result === false) return false;
			
			return true;
		}
		
		public function FinishReplay($game) // finish recording a game
		{
			$db = $this->db;
			
			$turns = $this->NumberOfTurns($game->ID());
			
			$result = $db->Query('UPDATE `replays_head` SET `Winner` = "'.$db->Escape($game->Winner).'", `EndType` = "'.$db->Escape($game->EndType).'", `Rounds` = "'.$db->Escape($game->Round).'", `Turns` = "'.$db->Escape($turns).'", `Finished` = CURRENT_TIMESTAMP WHERE `GameID` = "'.$db->Escape($game->ID()).'"');
			if ($result === false) return false;
			
			return true;
		}
		
		public function GetReplay($game_id, $turn)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `replays_head` WHERE `GameID` = "'.$db->Escape($game_id).'"');
			if ($result === false or count($result) == 0) return false;
			
			$players = $result[0];
			$player1 = $players['Player1'];
			$player2 = $players['Player2'];
			
			$replay = new CReplay($game_id, $turn, $player1, $player2, $this);
			$result = $replay->LoadReplay();
			if (!$result) return false;
			
			return $replay;
		}
		
		public function ListReplays($player, $hidden, $friendly, $long, $ai, $challenge, $victory, $page, $condition, $order)
		{
			$db = $this->db;
			
			$victory_q = ($victory != "none") ? '`EndType` = "'.$db->Escape($victory).'"' : '`EndType` != "Pending"';
			$player_q = ($player != "none") ? 'AND ((`Player1` = "'.$db->Escape($player).'") OR (`Player2` = "'.$db->Escape($player).'"))' : '';
			$hidden_q = ($hidden != "none") ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) '.(($hidden == "include") ? '>' : '=').' 0' : '';
			$friendly_q = ($friendly != "none") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) '.(($friendly == "include") ? '>' : '=').' 0' : '';
			$long_q = ($long != "none") ? ' AND FIND_IN_SET("LongMode", `GameModes`) '.(($long == "include") ? '>' : '=').' 0' : '';
			$ai_q = ($ai != "none") ? ' AND FIND_IN_SET("AIMode", `GameModes`) '.(($ai == "include") ? '>' : '=').' 0' : '';
			$ch_q = ($challenge != "none") ? (($challenge == 'include') ? ' AND `AI` != ""' : (($challenge == 'exclude') ? ' AND `AI` = ""' : ' AND `AI` = "'.$db->Escape($challenge).'"')) : '';
			
			$result = $db->Query('SELECT `GameID`, `Player1`, `Player2`, `Started`, `Finished`, `Rounds`, `Turns`, `GameModes`, `AI`, `Winner`, `EndType`, (CASE WHEN `Deleted` = TRUE THEN "yes" ELSE "no" END) as `Deleted`, `Views` FROM `replays_head` WHERE '.$victory_q.$player_q.$hidden_q.$friendly_q.$long_q.$ai_q.$ch_q.' ORDER BY `'.$db->Escape($condition).'` '.$db->Escape($order).' LIMIT '.(REPLAYS_PER_PAGE * $db->Escape($page)).' , '.REPLAYS_PER_PAGE.'');
			if ($result === false) return false;
			
			return $result;
		}
		
		public function CountPages($player, $hidden, $friendly, $long, $ai, $challenge, $victory)
		{	
			$db = $this->db;
			
			$victory_q = ($victory != "none") ? '`EndType` = "'.$db->Escape($victory).'"' : '`EndType` != "Pending"';
			$player_q = ($player != "none") ? 'AND ((`Player1` = "'.$db->Escape($player).'") OR (`Player2` = "'.$db->Escape($player).'"))' : '';
			$hidden_q = ($hidden != "none") ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) '.(($hidden == "include") ? '>' : '=').' 0' : '';
			$friendly_q = ($friendly != "none") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) '.(($friendly == "include") ? '>' : '=').' 0' : '';
			$long_q = ($long != "none") ? ' AND FIND_IN_SET("LongMode", `GameModes`) '.(($long == "include") ? '>' : '=').' 0' : '';
			$ai_q = ($ai != "none") ? ' AND FIND_IN_SET("AIMode", `GameModes`) '.(($ai == "include") ? '>' : '=').' 0' : '';
			$ch_q = ($challenge != "none") ? (($challenge == 'include') ? ' AND `AI` != ""' : (($challenge == 'exclude') ? ' AND `AI` = ""' : ' AND `AI` = "'.$db->Escape($challenge).'"')) : '';
			
			$result = $db->Query('SELECT COUNT(`GameID`) as `Count` FROM `replays_head` WHERE '.$victory_q.$player_q.$hidden_q.$friendly_q.$long_q.$ai_q.$ch_q.'');
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			
			$pages = ceil($data['Count'] / REPLAYS_PER_PAGE);
			
			return $pages;
		}
		
		public function ListPlayers() // player filter list
		{
			$db = $this->db;

			$result = $db->Query('SELECT DISTINCT `Player1` as `Player` FROM `replays_head` WHERE `EndType` != "Pending" UNION DISTINCT SELECT DISTINCT `Player2` as `Player` FROM `replays_head` WHERE `EndType` != "Pending" ORDER BY `Player` ASC');
			if ($result === false) return false;
			
			$players = array();
			foreach ($result as $data)
				$players[] = $data['Player'];
			
			return $players;
		}
		
		public function ConvertData(CGamePlayerData $data) // convert GameData to ReplayData (remove unnecessary data)
		{
			$converted = new CReplayData;
			$attributes = array('Hand', 'LastCard', 'LastMode', 'LastAction', 'NewCards', 'Revealed', 'Changes', 'DisCards', 'TokenNames', 'TokenValues', 'TokenChanges', 'Tower', 'Wall', 'Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits');
			
			foreach($attributes as $attribute) $converted->$attribute = $data->$attribute;
			
			return $converted;
		}
		
		public function NumberOfTurns($game_id)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT MAX(`Turn`) as `Turns` FROM `replays_data` WHERE `GameID` = "'.$db->Escape($game_id).'"');
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			
			return $data['Turns'];
		}
		
		public function IncrementViews($game_id) // increment number of views for the specified replay
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `replays_head` SET `Views` = `Views` + 1 WHERE `GameID` = "'.$db->Escape($game_id).'"');
			if ($result === false) return false;
			
			return true;
		}

		public function AssignThread($replay_id, $thread_id)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `replays_head` SET `ThreadID` = "'.$db->Escape($thread_id).'" WHERE `GameID` = "'.$db->Escape($replay_id).'"');
			if ($result === false) return false;

			return true;
		}

		public function RemoveThread($replay_id)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `replays_head` SET `ThreadID` = 0 WHERE `GameID` = "'.$db->Escape($replay_id).'"');
			if ($result === false) return false;

			return true;
		}

		public function FindReplay($thread_id)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `GameID` FROM `replays_head` WHERE `ThreadID` = "'.$db->Escape($thread_id).'"');
			if ($result === false or count($result) == 0) return 0;

			$data = $result[0];

			return $data['GameID'];
		}
	}
	
	
	class CReplay
	{
		private $Replays;
		private $GameID;
		private $Turn;
		private $Player1;
		private $Player2;
		private $HiddenCards;
		private $FriendlyPlay;
		private $LongMode;
		private $AIMode;
		public $Current;
		public $Round;
		public $Winner;
		public $EndType;
		public $AI;
		public $ThreadID;
		public $ReplayData;
		
		public function __construct($gameid, $turn, $player1, $player2, CReplays $Replays)
		{
			$this->GameID = $gameid;
			$this->Turn = $turn;
			$this->Player1 = $player1;
			$this->Player2 = $player2;
			$this->Replays = &$Replays;
			$this->ReplayData[$player1] = new CReplayData;
			$this->ReplayData[$player2] = new CReplayData;
		}
		
		public function __destruct()
		{
		}
		
		public function ID()
		{
			return $this->GameID;
		}
		
		public function Turn()
		{
			return $this->Turn;
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
		
		public function LoadReplay()
		{
			$db = $this->Replays->getDB();
			$result = $db->Query('SELECT `Winner`, `EndType`, `GameModes`, `AI`, `ThreadID` FROM `replays_head` WHERE `GameID` = "'.$db->Escape($this->ID()).'"');
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			$this->Winner = $data['Winner'];
			$this->EndType = $data['EndType'];
			$this->HiddenCards = (strpos($data['GameModes'], 'HiddenCards') !== false) ? 'yes' : 'no';
			$this->FriendlyPlay = (strpos($data['GameModes'], 'FriendlyPlay') !== false) ? 'yes' : 'no';
			$this->LongMode = (strpos($data['GameModes'], 'LongMode') !== false) ? 'yes' : 'no';
			$this->AIMode = (strpos($data['GameModes'], 'AIMode') !== false) ? 'yes' : 'no';
			$this->ThreadID = $data['ThreadID'];
			$this->AI = $data['AI'];
			
			$result = $db->Query('SELECT `Current`, `Round`, `Data` FROM `replays_data` WHERE `GameID` = "'.$db->Escape($this->ID()).'" AND `Turn` = "'.$db->Escape($this->Turn()).'"');
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			$this->Current = $data['Current'];
			$this->Round = $data['Round'];
			$this->ReplayData = unserialize($data['Data']);
			
			return true;
		}
		
		public function NumberOfTurns()
		{
			$db = $this->Replays->getDB();
			$result = $db->Query('SELECT `Turns` FROM `replays_head` WHERE `EndType` != "Pending" AND `GameID` = "'.$db->Escape($this->ID()).'"');
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			
			return $data['Turns'];
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
	}
	
	
	class CReplayData
	{
		public $Hand;
		public $LastCard;
		public $LastMode;
		public $LastAction;
		public $NewCards;
		public $Revealed;
		public $Changes;
		public $DisCards;
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
