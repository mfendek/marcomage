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
			$game_modes = array();
			if ($hidden_cards == "yes") $game_modes[] = 'HiddenCards';
			if ($friendly_play == "yes") $game_modes[] = 'FriendlyPlay';
			$game_modes = implode(',', $game_modes);
			
			$result = $db->Query('INSERT INTO `replays` (`GameID`, `Player1`, `Player2`, `Current`, `Data`, `GameModes`) VALUES ("'.$db->Escape($game_id).'", "'.$db->Escape($player1).'", "'.$db->Escape($player2).'", "'.$db->Escape($current).'", "'.$db->Escape($data).'", "'.$db->Escape($game_modes).'")');
			if (!$result) return false;
			
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
			
			$hidden_cards = $game->GetGameMode('HiddenCards');
			$friendly_play = $game->GetGameMode('FriendlyPlay');
			$game_modes = array();
			if ($hidden_cards == "yes") $game_modes[] = 'HiddenCards';
			if ($friendly_play == "yes") $game_modes[] = 'FriendlyPlay';
			$game_modes = implode(',', $game_modes);
			
			$result = $db->Query('SELECT IFNULL(MAX(`Turn`), 0) as `Turn` FROM `replays` WHERE `GameID` = "'.$db->Escape($game_id).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$tmp_data = $result->Next();
			if ($tmp_data['Turn'] == 0) return false; // game replay does not exist
			$turn = $tmp_data['Turn'] + 1;
			
			$result = $db->Query('INSERT INTO `replays` (`GameID`, `Turn`, `Player1`, `Player2`, `Current`, `Round`, `Data`, `GameModes`) VALUES ("'.$db->Escape($game_id).'", "'.$db->Escape($turn).'", "'.$db->Escape($player1).'", "'.$db->Escape($player2).'", "'.$db->Escape($current).'", "'.$db->Escape($round).'", "'.$db->Escape($data).'", "'.$db->Escape($game_modes).'")');
			if (!$result) return false;
			
			return true;
		}
		
		public function FinishReplay($game) // finish recording a game
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `replays` SET `Winner` = "'.$db->Escape($game->Winner).'", `EndType` = "'.$db->Escape($game->EndType).'", `Final` = "yes" WHERE `GameID` = "'.$db->Escape($game->ID()).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function GetReplay($game_id, $turn)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Player1`, `Player2` FROM `replays` WHERE `Final` = "yes" AND `GameID` = "'.$db->Escape($game_id).'" AND `Turn` = "'.$db->Escape($turn).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$players = $result->Next();
			$player1 = $players['Player1'];
			$player2 = $players['Player2'];
			
			$replay = new CReplay($game_id, $turn, $player1, $player2, $this);
			$replay->LoadReplay();
			
			return $replay;
		}
		
		public function ListReplays($player, $hidden, $friendly, $victory, $page)
		{
			$db = $this->db;
			
			$victory_q = ($victory != "none") ? ' AND `EndType` = "'.$db->Escape($victory).'"' : '';
			$player_q = ($player != "none") ? 'AND ((`Player1` = "'.$db->Escape($player).'") OR (`Player2` = "'.$db->Escape($player).'"))' : '';
			$hidden_q = ($hidden != "ignore") ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) '.(($hidden == "include") ? '>' : '=').' 0' : '';
			$friendly_q = ($friendly != "ignore") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) '.(($friendly == "include") ? '>' : '=').' 0' : '';
			
			$result = $db->Query('SELECT `GameID`, `Player1`, `Player2`, MIN(`Created`) as `Started`, MAX(`Created`) as `Finished`, MAX(`Round`) as `Rounds`, MAX(`Turn`) as `Turns`, `GameModes`, `Winner`, `EndType` FROM `replays` WHERE `Final` = "yes"'.$player_q.$hidden_q.$friendly_q.$victory_q.' GROUP BY `GameID` ORDER BY `Finished` DESC LIMIT '.(REPLAYS_PER_PAGE * $page).' , '.REPLAYS_PER_PAGE.'');
			if (!$result) return false;
			
			$replays = array();
			while( $data = $result->Next() )
				$replays[] = $data;
			
			return $replays;
		}
		
		public function CountPages($player, $hidden, $friendly, $victory)
		{	
			$db = $this->db;
			
			$victory_q = ($victory != "none") ? ' AND `EndType` = "'.$db->Escape($victory).'"' : '';
			$player_q = ($player != "none") ? 'AND ((`Player1` = "'.$db->Escape($player).'") OR (`Player2` = "'.$db->Escape($player).'"))' : '';
			$hidden_q = ($hidden != "ignore") ? ' AND FIND_IN_SET("HiddenCards", `GameModes`) '.(($hidden == "include") ? '>' : '=').' 0' : '';
			$friendly_q = ($friendly != "ignore") ? ' AND FIND_IN_SET("FriendlyPlay", `GameModes`) '.(($friendly == "include") ? '>' : '=').' 0' : '';
			
			$result = $db->Query('SELECT COUNT(`GameID`) as `Count` FROM (SELECT`GameID` FROM `replays` WHERE `Final` = "yes"'.$player_q.$hidden_q.$friendly_q.$victory_q.' GROUP BY `GameID`) as `tmp`');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			$pages = ceil($data['Count'] / REPLAYS_PER_PAGE);
			
			return $pages;
		}
		
		public function ListPlayers() // player filter list
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Player1` as `Player` FROM `replays` WHERE `Final` = "yes" UNION DISTINCT SELECT `Player2` as `Player` FROM `replays` WHERE `Final` = "yes" ORDER BY `Player` ASC');
			if (!$result) return false;
			
			$players = array();
			while( $data = $result->Next() )
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
		public $Current;
		public $Round;
		public $Winner;
		public $EndType;
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
			$result = $db->Query('SELECT `Current`, `Round`, `Winner`, `EndType`, `Data`, `GameModes` FROM `replays` WHERE `Final` = "yes" AND `GameID` = "'.$db->Escape($this->ID()).'" AND `Turn` = "'.$db->Escape($this->Turn()).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$this->Current = $data['Current'];
			$this->Round = $data['Round'];
			$this->Winner = $data['Winner'];
			$this->EndType = $data['EndType'];
			$this->HiddenCards = (strpos($data['GameModes'], 'HiddenCards') !== false) ? 'yes' : 'no';
			$this->FriendlyPlay = (strpos($data['GameModes'], 'FriendlyPlay') !== false) ? 'yes' : 'no';
			$this->ReplayData = unserialize($data['Data']);
			
			return true;
		}
		
		public function NumberOfTurns()
		{
			$db = $this->Replays->getDB();
			$result = $db->Query('SELECT MAX(`Turn`) as `Turns` FROM `replays` WHERE `Final` = "yes" AND `GameID` = "'.$db->Escape($this->ID()).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data['Turns'];
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
