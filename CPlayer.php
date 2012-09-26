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
			global $messagedb;
			
			$db = $this->db;
			$db->txnBegin();
			
			// add all associated entries (login, score, decks, settings)
			if (!$logindb->Register($playername, $password)) { $db->txnRollBack(); return false; }
			if (!$scoredb->CreateScore($playername)) { $db->txnRollBack(); return false; }
			$starter_decks = $deckdb->StarterDecks();
			foreach (array('deck 1', 'deck 2', 'deck 3', 'deck 4', 'deck 5', 'deck 6', 'deck 7', 'deck 8') as $deckname)
			{
				$deck = $deckdb->CreateDeck($playername, $deckname);
				if ($deck === false) { $db->txnRollBack(); return false; }
				
				// create starter deck
				if (isset($starter_decks[$deck->Deckname()]))
				{
					$starter_deck = $starter_decks[$deck->Deckname()];
					$deck->LoadData($starter_deck->DeckData);
					if (!$deck->SaveDeck()) { $db->txnRollBack(); return false; }
				}
			}
			if (!$settingdb->CreateSettings($playername)) { $db->txnRollBack(); return false; }
			if (!$messagedb->WelcomeMessage($playername)) { $db->txnRollBack(); return false; }
			
			$db->txnCommit();
			
			// create the object
			return new CPlayer($playername, "user", $this);
		}
		
		public function DeletePlayer($playername)
		{
			global $logindb;
			global $scoredb;
			global $deckdb;
			global $settingdb;
			global $gamedb;
			global $messagedb;

			$db = $this->db;
			$db->txnBegin();

			// delete every indication that the player ever existed ^^
			if (!$logindb->Unregister($playername)) { $db->txnRollBack(); return false; }
			if (!$scoredb->DeleteScore($playername)) { $db->txnRollBack(); return false; }

			foreach ($deckdb->ListDecks($playername) as $deck_data)
				if (!$deckdb->DeleteDeck($playername, $deck_data['DeckID'])) { $db->txnRollBack(); return false; }

			if (!$settingdb->DeleteSettings($playername)) { $db->txnRollBack(); return false; }
			if (!$gamedb->DeleteGames($playername)) { $db->txnRollBack(); return false; }
			if (!$messagedb->DeleteMessages($playername)) { $db->txnRollBack(); return false; }

			$db->txnCommit();

			return true;
		}
		
		public function RenamePlayer($playername, $new_name)
		{
			$db = $this->db;
			$db->txnBegin();
			
			$success = true;
			$success = $success && $db->Query('UPDATE `chats` SET `Name` = ? WHERE `Name` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `concepts` SET `Author` = ? WHERE `Author` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `decks` SET `Username` = ? WHERE `Username` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `forum_posts` SET `Author` = ? WHERE `Author` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `forum_threads` SET `Author` = ? WHERE `Author` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `forum_threads` SET `LastAuthor` = ? WHERE `LastAuthor` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `games` SET `Player1` = ? WHERE `Player1` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `games` SET `Player2` = ? WHERE `Player2` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `games` SET `Current` = ? WHERE `Current` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `games` SET `Winner` = ? WHERE `Winner` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `games` SET `Surrender` = ? WHERE `Surrender` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `logins` SET `Username` = ? WHERE `Username` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `messages` SET `Author` = ? WHERE `Author` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `messages` SET `Recipient` = ? WHERE `Recipient` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `replays` SET `Player1` = ? WHERE `Player1` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `replays` SET `Player2` = ? WHERE `Player2` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `replays` SET `Winner` = ? WHERE `Winner` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `scores` SET `Username` = ? WHERE `Username` = ?', array($new_name, $playername));
			$success = $success && $db->Query('UPDATE `settings` SET `Username` = ? WHERE `Username` = ?', array($new_name, $playername));

			if( $success ) $db->txnCommit(); else $db->txnRollBack();
			return $success;
		}
		
		public function GetPlayer($playername)
		{
			$db = $this->db;
			//TODO: instead of this, use a multijoin to check if $playername is in all required tables
			$result = $db->Query('SELECT `UserType` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$type = $data['UserType'];
			
			return new CPlayer($playername, $type, $this);
		}
		
		public function ListPlayers($activity, $status, $name, $condition, $order, $page)
		{
			$db = $this->db;

			$interval = ( $activity == "active"  ? "10 MINUTE"
			          : ( $activity == "offline" ? "1 WEEK"
			          : ( $activity == "none"    ? "3 WEEK"
			          : ( $activity == "all"     ? ""
			          : ""))));

			$name_q = ($name != '') ? ' AND `Username` LIKE ?' : '';
			$status_q = ($status != 'none') ? ' AND `Status` = ?' : '';
			$activity_q = ($interval != '') ? ' AND `Last Query` >= NOW() - INTERVAL '.$interval.'' : '';

			$params = array();
			if ($name != '') $params[] = '%'.$name.'%';
			if ($status != 'none') $params[] = $status;

			$valid_conditions = array('Level', 'Username', 'Country', 'Quarry', 'Magic', 'Dungeons', 'Rares', 'Challenges', 'Tower', 'Wall', 'TowerDamage', 'WallDamage', 'Assassin', 'Builder', 'Carpenter', 'Collector', 'Desolator', 'Dragon', 'Gentle_touch', 'Saboteur', 'Snob', 'Survivor', 'Titan');
			$condition = (in_array($condition, $valid_conditions)) ? $condition : 'Level';
			$order = ($order == 'ASC') ? 'ASC' : 'DESC';
			$page = (is_numeric($page)) ? $page : 0;

			$query = "
				SELECT `Username`, `UserType`, `Level`, `Wins`, `Losses`, `Draws`, `Avatar`, `Status`, `FriendlyFlag`, `BlindFlag`, `LongFlag`, `settings`.`Country`, `Last Query`
				FROM `logins` JOIN `settings` USING (`Username`) JOIN `scores` USING (`Username`)
				WHERE 1 {$name_q}{$status_q}{$activity_q}
				ORDER BY `".$condition."` ".$order.", `Username` ASC
				LIMIT ".(PLAYERS_PER_PAGE * $page).", ".PLAYERS_PER_PAGE."";
    
			$result = $db->Query($query, $params);
			if ($result === false) return false;

			return $result;
		}
		
		public function CountPages($activity, $status, $name)
		{
			$db = $this->db;

			$interval = ( $activity == "active"  ? "10 MINUTE"
			          : ( $activity == "offline" ? "1 WEEK"
			          : ( $activity == "none"    ? "3 WEEK"
			          : ( $activity == "all"     ? ""
			          : ""))));

			$name_q = ($name != '') ? ' AND `Username` LIKE ?' : '';
			$status_q = ($status != 'none') ? ' JOIN (SELECT `Username` FROM `settings` WHERE `Status` = ?) as `settings` USING (`Username`)' : '';
			$activity_q = ($interval != '') ? ' AND `Last Query` >= NOW() - INTERVAL '.$interval.'' : '';

			$params = array();
			if ($status != 'none') $params[] = $status;
			if ($name != '') $params[] = '%'.$name.'%';

			$result = $db->Query('SELECT COUNT(`Username`) as `Count` FROM `logins`'.$status_q.' WHERE 1'.$activity_q.$name_q.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			$pages = ceil($data['Count'] / PLAYERS_PER_PAGE);
			
			return $pages;
		}
		
		public function ChangeAccessRights($playername, $access_right)
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `logins` SET `UserType` = ? WHERE `Username` = ?', array($access_right, $playername));
			if ($result === false) return false;
			
			return true;
		}
		
		public function ResetNotification($playername)
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `logins` SET `Notification` = `Last Query` WHERE `Username` = ?', array($playername));
			if ($result === false) return false;
			
			return true;
		}
		
		public function RestartTutorial($playername)
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `logins` SET `Notification` = "0000-00-00 00:00:00" WHERE `Username` = ?', array($playername));
			if ($result === false) return false;
			
			return true;
		}
		
		public function isOnline($playername)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `Last Query` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return( time() - strtotime($data['Last Query']) < 60*10 );
		}
		
		public function isDead($playername)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `Last Query` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return ( time() - strtotime($data['Last Query']) > 60*60*24*7*3 );
		}
		
		public function GetNotification($playername)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `Notification` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['Notification'];
		}
		
		public function LastQuery($playername)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `Last Query` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['Last Query'];
		}
		
		public function Registered($playername)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `Registered` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['Registered'];
		}
		
		public function GetLevel($playername)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `Level` FROM `scores` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['Level'];
		}
		
		public function GetGameSlots($playername)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `GameSlots` FROM `scores` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['GameSlots'];
		}
		
		public function GetGuest()
		{
			return new CGuest();
		}
	}
	
	
	class CPlayer
	{
		private $Name = '';
		protected $Type = '';
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
		
		public function ResetNotification()
		{
			return $this->Players->ResetNotification($this->Name);
		}
		
		public function RestartTutorial()
		{
			return $this->Players->RestartTutorial($this->Name);
		}
		
		public function isOnline()
		{
			return $this->Players->isOnline($this->Name);
		}
		
		public function isDead()
		{
			return $this->Players->isDead($this->Name);
		}
		
		public function GetNotification()
		{
			return $this->Players->GetNotification($this->Name);
		}
		
		public function LastQuery()
		{
			return $this->Players->LastQuery($this->Name);
		}
		
		public function Registered()
		{
			return $this->Players->Registered($this->Name);
		}
		
		public function GetScore()
		{
			global $scoredb;
			return $scoredb->GetScore($this->Name);
		}
		
		public function GetLevel()
		{
			return $this->Players->GetLevel($this->Name);
		}
		
		public function GetGameSlots()
		{
			return $this->Players->GetGameSlots($this->Name);
		}
		
		public function GetDeck($deck_id)
		{
			global $deckdb;
			return $deckdb->GetDeck($this->Name, $deck_id);
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

		public function GetSettings()
		{
			global $settingdb;
			return $settingdb->GetSettings($this->Name);
		}

		public function GetVersusStats($opponent)
		{
			global $statistics;
			return ($this->Name == $opponent) ? $statistics->GameStats($this->Name) : $statistics->VersusStats($this->Name, $opponent);
		}

		public function FreeSlots()
		{
			global $gamedb;
			return $gamedb->CountFreeSlots1($this->Name);
		}

		public function ChangeAccessRights($access_right)
		{
			return $this->Players->ChangeAccessRights($this->Name, $access_right);
		}

		public function ChangePassword($password)
		{
			global $logindb;
			return $logindb->ChangePassword($this->Name, $password);
		}
	}
	
	
	class CGuest extends CPlayer
	{
		public function __construct()
		{
			$this->Type = 'guest';
		}

		public function isOnline()
		{
			return true;
		}

		public function isDead()
		{
			return false;
		}

		public function GetNotification()
		{
			return date('Y-m-d H:i:s', time() + 24*60*60); // disable notification
		}

		public function GetSettings()
		{
			global $settingdb;

			$settings = $settingdb->GetGuestSettings();
			$settings->ChangeSetting('Skin', 0);
			$settings->ChangeSetting('Timezone', 0);
			$settings->ChangeSetting('Autorefresh', 0);
			$settings->ChangeSetting('Images', 'yes');
			$settings->ChangeSetting('OldCardLook', 'no');
			$settings->ChangeSetting('Insignias', 'yes');
			$settings->ChangeSetting('Country', 'Unknown');
			$settings->ChangeSetting('Avatar', 'noavatar.jpg');
			$settings->ChangeSetting('Nationality', 'no');
			$settings->ChangeSetting('Avatarlist', 'yes');
			$settings->ChangeSetting('DefaultFilter', 'none');
			$settings->ChangeSetting('FoilCards', '');

			return $settings;
		}
	}
?>
