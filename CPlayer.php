<?php
/*
	CPlayer - in-game identity of a player
*/
?>
<?php
	class CPlayers
	{
		private $db;
		
		public function __construct(CDatabase &$db)
		{
			$this->db = &$db;
		}
		
		public function CreatePlayer($playername, $password)
		{
			global $logindb;
			global $scoredb;
			global $deckdb;
			global $settingdb;
			
			// add all associated entries (login, score, decks, settings)
			$logindb->Register($playername, $password);
			$scoredb->CreateScore($playername);
			foreach (array('deck 1', 'deck 2', 'deck 3', 'deck 4', 'deck 5', 'deck 6', 'deck 7', 'deck 8') as $deckname)
				$deckdb->CreateDeck($playername, $deckname);
			$settingdb->CreateSettings($playername);
			
			// create 3 starter decks
			
			$deck = $deckdb->GetDeck($playername, 'deck 1');
			$deck->DeckData->Common = array(1=>54, 240, 71, 256, 250, 259, 261, 113, 247, 79, 57, 140, 7, 236, 257);
			$deck->DeckData->Uncommon =  array(1=>28, 189, 83, 10, 204, 211, 230, 36, 150, 201, 53, 96, 180, 164, 208);
			$deck->DeckData->Rare = array(1=>32, 197, 75, 74, 151, 61, 69, 66, 232, 229, 291, 21, 126, 182, 181);
			
			$deck->SaveDeck();
			
			$deck = $deckdb->GetDeck($playername, 'deck 2');
			$deck->DeckData->Common = array(1=>1, 289, 23, 149, 359, 18, 260, 119, 26, 275, 271, 176, 60, 122, 272) ;
			$deck->DeckData->Uncommon = array(1=>146, 163, 162, 164, 175, 266, 5, 154, 49, 136, 195, 35, 174, 270, 89);
			$deck->DeckData->Rare = array(1=>235, 295, 178, 379, 161, 192, 4, 167, 233, 156, 67, 339, 169, 141, 148);
			
			$deck->SaveDeck();
			
			$deck = $deckdb->GetDeck($playername, 'deck 3');
			$deck->DeckData->Common = array(1=>356, 45, 1, 260, 79, 238, 140, 368, 274, 269, 160, 362, 26, 300, 91);
			$deck->DeckData->Uncommon = array(1=>29, 267, 84, 19, 47, 191, 320, 123, 98, 3, 8, 58, 109, 96, 52);
			$deck->DeckData->Rare = array(1=>115, 108, 127, 86, 110, 138, 181, 242, 222, 249, 4, 277, 293, 199, 128);
			
			$deck->SaveDeck();
			
			// create the object
			return new CPlayer($playername, "user", $this);
		}
		
		public function DeletePlayer($playername)
		{
			global $logindb;
			global $scoredb;

			// delete every indication that the player ever existed ^^
			$logindb->Unregister($playername);
			$scoredb->DeleteScore($playername);
			foreach ($deckdb->ListDecks($playername) as $deckname)
				$deckdb->DeleteDeck($playername, $deckname);
			
			//TODO: also set all games to 'game was aborted' and 'has already confirmed the result' so it can be deleted.
			//TODO: delete settings
			//FIXME: needs a thorough examination of all that needs to be deleted
			
			return true;
		}
		
		public function GetPlayer($playername)
		{
			$db = $this->db;
			//TODO: instead of this, use a multijoin to check if $playername is in all required tables
			$result = $db->Query('SELECT `UserType` FROM `logins` WHERE `Username` = "'.$db->Escape($playername).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next(); 
			$type = $data['UserType'];
			
			return new CPlayer($playername, $type, $this);
		}
		
		public function ListPlayers($showdead, $filter_cond, $condition, $order)
		{
			$db = $this->db;
			//$result = $db->Query('SELECT `Username`, `Wins`, `Losses`, `Draws`, `Last Query` FROM ((SELECT `Username`, `Wins`, `Losses`, `Draws`, 0 AS `Rank1`, `Wins`/`Losses` AS `Rank2` FROM `scores` WHERE `Losses` != 0) UNION (SELECT `Username`, `Wins`, `Losses`, `Draws`, 1 AS `Rank1`, `Wins` AS `Rank2` FROM `scores` WHERE `Losses` = 0 AND `Draws` = 0 AND `Wins` > 0) UNION (SELECT `Username`, `Wins`, `Losses`, `Draws`, -1 AS `Rank1`, 0 AS `Rank2` FROM `scores` WHERE `Wins` = 0 AND `Losses` = 0 AND `Draws` = 0)) AS t1 JOIN `logins` AS t2 USING (`Username`) ORDER BY `Rank1` DESC, `Rank2` DESC, `Draws` ASC, `Username` ASC');
			
			if ($filter_cond == "active") $activity_q = "60*10";
			elseif ($filter_cond == "offline") $activity_q = "60*60*24*7*1";
			else $activity_q = "60*60*24*7*3";
			
			$p1_part = 'SELECT `Player1` as `Username` FROM `games` WHERE (`State` != "waiting" AND `State` != "P1 over")';
			
			$p2_part = 'SELECT `Player2` as `Username` FROM `games` WHERE (`State` != "waiting" AND `State` != "P2 over")';
			
			$active_games = $p1_part.' UNION ALL '.$p2_part;
			
			$challenges_from = 'SELECT `Player1` as `Username` FROM `games` WHERE `State` = "waiting"';	
			
			$mixed = 'SELECT `Username`, COUNT(`Username`) as `Free slots` FROM ('.$active_games.' UNION ALL '.$challenges_from.') as `tmp` GROUP BY `Username`';
			
			$nondead = 'SELECT `scores`.`Username`, `Wins`, `Losses`, `Draws`, 1 AS `Rank1`, `Wins`*3+`Draws` AS `Rank2`, `Last Query`, '.MAX_GAMES.' - COALESCE(`Free slots`, 0) as `Free slots`, `Avatar`, `Country` FROM `scores` INNER JOIN (SELECT `Username`, `Last Query` FROM `logins` WHERE (UNIX_TIMESTAMP() - `Last Query` <= '.$activity_q.')) as `logins` ON `scores`.`Username` = `logins`.`Username` INNER JOIN (SELECT `Username`, `Avatar`, `Country` FROM `settings`) as `settings` ON `scores`.`Username` = `settings`.`Username` LEFT OUTER JOIN ('.$mixed.') as `temp` ON `scores`.`Username` = `temp`.`Username`';
			
			$dead = 'SELECT `scores`.`Username`, `Wins`, `Losses`, `Draws`, 0 AS `Rank1`, `Wins`*3+`Draws` AS `Rank2`, `Last Query`, '.MAX_GAMES.' - COALESCE(`Free slots`, 0) as `Free slots`, `Avatar`, `Country` FROM `scores` INNER JOIN (SELECT `Username`, `Last Query` FROM `logins` WHERE (UNIX_TIMESTAMP() - `Last Query` > 60*60*24*7*3)) as `logins` ON `scores`.`Username` = `logins`.`Username` INNER JOIN (SELECT `Username`, `Avatar`, `Country` FROM `settings`) as `settings` ON `scores`.`Username` = `settings`.`Username` LEFT OUTER JOIN ('.$mixed.') as `temp` ON `scores`.`Username` = `temp`.`Username`';
			
			$list_q = ( $showdead == "yes" ) ? $nondead.' UNION '.$dead : $nondead;

			$result = $db->Query('SELECT `Username`, `Wins`, `Losses`, `Draws`, `Last Query`, `Rank1`, `Rank2`, `Free slots`, `Avatar`, `Country` FROM ('.$list_q.') as `t` ORDER BY `Rank1` DESC, `'.$condition.'` '.$order); 
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$list = array();
			while( $data = $result->Next() )
				$list[] = $data;
			return $list;
		}
		
		public function ChangeAccessRights($playername, $access_right)
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `logins` SET `UserType` = "'.$access_right.'" WHERE `Username` = "'.$db->Escape($playername).'"');
			
			if (!$result) return false;
			
			return true;
		}
		
		public function isOnline($playername)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Last Query` FROM `logins` WHERE `Username` = "'.$db->Escape($playername).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			return (time() - $data['Last Query'] < 60*10);
		}
		
		public function isDead($playername)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Last Query` FROM `logins` WHERE `Username` = "'.$db->Escape($playername).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			return (time() - $data['Last Query'] > (60*60*24*7*3));
		}
		
		public function PreviousLogin($playername)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `PreviousLogin` FROM `logins` WHERE `Username` = "'.$db->Escape($playername).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			return $data['PreviousLogin'];
		}
	}
	
	
	class CPlayer
	{
		private $Name = '';
		private $Type = '';
		private $Players = false;
		
		public function __construct($username, $type, CPlayers &$Players)
		{
			$this->Name = $username;
			$this->Type = $type;
			$this->Players = &$Players;
		}
		
		public function __destruct()
		{
			$this->Name = '';
			$this->Players = false;
		}
		
		public function Name()
		{
			return $this->Name;
		}
		
		public function Type()
		{
			return $this->Type;
		}
		
		public function isOnline()
		{
			return $this->Players->isOnline($this->Name);
		}
		
		public function isDead()
		{
			return $this->Players->isDead($this->Name);
		}
		
		public function PreviousLogin()
		{
			return $this->Players->PreviousLogin($this->Name);
		}
		
		public function GetScore()
		{
			global $scoredb;
			return $scoredb->GetScore($this->Name);
		}
		
		public function GetDeck($deckname)
		{
			global $deckdb;
			return $deckdb->GetDeck($this->Name, $deckname);
		}
		
		public function ListDecks()
		{
			global $deckdb;
			return $deckdb->ListDecks($this->Name);
		}
		
		public function ListReadyDecks()
		{
			global $deckdb;
			return $deckdb->ListReadyDecks($this->Name);
		}
		
		public function GetSetting($setting)
		{
            global $settingdb;
			return $settingdb->GetSetting($this->Name, $setting);
		}

		public function ChangeSetting($setting, $value)
		{
            global $settingdb;
			return $settingdb->ChangeSetting($this->Name, $setting, $value);
		}
	
		public function GetSettings()
		{
            global $settingdb;
			return $settingdb->GetSettings($this->Name);
		}
		
		public function GetUserSettings()
		{
            global $settingdb;
			return $settingdb->GetSettings($this->Name, $settingdb->UserSettingsList());
		}
		
		public function GetGameSettings()
		{
            global $settingdb;
			return $settingdb->GetSettings($this->Name, $settingdb->GameSettingsList());
		}
		
		public function ChangeAccessRights($access_right)
		{
            return $this->Players->ChangeAccessRights($this->Name, $access_right);
		}
	}
?>
