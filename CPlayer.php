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
			global $deckdb;
			global $settingdb;
			global $gamedb;
			global $messagedb;

			$result = array();
			// delete every indication that the player ever existed ^^
			$res = $logindb->Unregister($playername);
			$result[] = (($res) ? 'Success' : 'FAILED!!!').' at Unregister';

			$res = $scoredb->DeleteScore($playername);
			$result[] = (($res) ? 'Success' : 'FAILED!!!').' at DeleteScore';

			foreach ($deckdb->ListDecks($playername) as $deck_data)
			{
				$res = $deckdb->DeleteDeck($playername, $deck_data['Deckname']);
				$result[] = (($res) ? 'Success' : 'FAILED!!!').' at DeleteDeck '.htmlencode($deck_data['Deckname']);
			}

			$res = $settingdb->DeleteSettings($playername);
			$result[] = (($res) ? 'Success' : 'FAILED!!!').' at DeleteSettings';

			$res = $gamedb->DeleteGames($playername);
			$result[] = (($res) ? 'Success' : 'FAILED!!!').' at DeleteGames';

			$res = $messagedb->DeleteMessages($playername);
			$result[] = (($res) ? 'Success' : 'FAILED!!!').' at DeleteMessages';

			return $result;
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
		
		public function ListPlayers($activity, $status, $name, $condition, $order, $page)
		{
			$db = $this->db;

			$interval = ( $activity == "active"  ? "10 MINUTE"
			          : ( $activity == "offline" ? "1 WEEK"
			          : ( $activity == "none"    ? "3 WEEK"
			          : ( $activity == "all"     ? ""
			          : ""))));

			$name_q = ($name != '') ? ' AND `Username` LIKE "%'.$db->Escape($name).'%"' : '';
			$status_q = ($status != 'none') ? ' AND `Status` = "'.$db->Escape($status).'"' : '';
			$activity_q = ($interval != '') ? ' AND `Last Query` >= NOW() - INTERVAL '.$interval.'' : '';

			$query = "
				SELECT `Username`, `UserType`, `Level`, `Exp`, `Wins`, `Losses`, `Draws`, `Avatar`, `Status`, `FriendlyFlag`, `BlindFlag`, `settings`.`Country`, `Last Query`
				FROM `logins` JOIN `settings` USING (`Username`) JOIN `scores` USING (`Username`)
				WHERE 1 {$name_q}{$status_q}{$activity_q}
				ORDER BY `".$db->Escape($condition)."` ".$db->Escape($order).", `Username` ASC
				LIMIT ".(PLAYERS_PER_PAGE * $db->Escape($page)).", ".PLAYERS_PER_PAGE."";
    
			$result = $db->Query($query);
			if (!$result) return false;
			
			$list = array();
			while( $data = $result->Next() )
				$list[] = $data;

			return $list;
		}
		
		public function CountPages($activity, $status, $name)
		{
			$db = $this->db;

			$interval = ( $activity == "active"  ? "10 MINUTE"
			          : ( $activity == "offline" ? "1 WEEK"
			          : ( $activity == "none"    ? "3 WEEK"
			          : ( $activity == "all"     ? ""
			          : ""))));

			$name_q = ($name != '') ? ' AND `Username` LIKE "%'.$db->Escape($name).'%"' : '';
			$status_q = ($status != 'none') ? ' JOIN (SELECT `Username` FROM `settings` WHERE `Status` = "'.$db->Escape($status).'") as `settings` USING (`Username`)' : '';
			$activity_q = ($interval != '') ? ' AND `Last Query` >= NOW() - INTERVAL '.$interval.'' : '';

			$result = $db->Query('SELECT COUNT(`Username`) as `Count` FROM `logins`'.$status_q.' WHERE 1'.$activity_q.$name_q.'');
			if (!$result) return false;

			$data = $result->Next();
			
			$pages = ceil($data['Count'] / PLAYERS_PER_PAGE);
			
			return $pages;
		}
		
		public function ChangeAccessRights($playername, $access_right)
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `logins` SET `UserType` = "'.$db->Escape($access_right).'" WHERE `Username` = "'.$db->Escape($playername).'"');
			
			if (!$result) return false;
			
			return true;
		}
		
		public function ResetNotification($playername)
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `logins` SET `Notification` = `Last Query` WHERE `Username` = "'.$db->Escape($playername).'"');
			
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
			return( time() - strtotime($data['Last Query']) < 60*10 );
		}
		
		public function isDead($playername)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Last Query` FROM `logins` WHERE `Username` = "'.$db->Escape($playername).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			return ( time() - strtotime($data['Last Query']) > 60*60*24*7*3 );
		}
		
		public function GetNotification($playername)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Notification` FROM `logins` WHERE `Username` = "'.$db->Escape($playername).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			return $data['Notification'];
		}
		
		public function LastQuery($playername)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Last Query` FROM `logins` WHERE `Username` = "'.$db->Escape($playername).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			return $data['Last Query'];
		}
		
		public function Registered($playername)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Registered` FROM `logins` WHERE `Username` = "'.$db->Escape($playername).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			return $data['Registered'];
		}
		
		public function GetLevel($playername)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Level` FROM `scores` WHERE `Username` = "'.$db->Escape($playername).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			return $data['Level'];
		}
	}
	
	
	class CPlayer
	{
		private $Name = '';
		private $Type = '';
		private $Players = false;
		private $Settings = false; // cache
		
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

		public function GetSettings()
		{
			global $settingdb;

			if (!$this->Settings) $this->Settings = &$settingdb->GetSettings($this->Name);
			return $this->Settings;
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
	}
?>
