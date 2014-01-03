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
		
		public function createPlayer($playername, $password)
		{
			global $logindb;
			global $scoredb;
			global $deckdb;
			global $settingdb;
			global $messagedb;
			
			$db = $this->db;
			$db->txnBegin();
			
			// add all associated entries (login, score, decks, settings)
			if (!$logindb->register($playername, $password)) { $db->txnRollBack(); return false; }
			if (!$scoredb->createScore($playername)) { $db->txnRollBack(); return false; }

			// create starter decks
			$starter_decks = $deckdb->starterDecks();
			foreach ($starter_decks as $deckname => $starter_deck)
			{
				$deck = $deckdb->createDeck($playername, $deckname);
				if ($deck === false) { $db->txnRollBack(); return false; }

				$deck->loadData($starter_deck->DeckData);
				if (!$deck->saveDeck()) { $db->txnRollBack(); return false; }
			}

			// fill remaining decks slots with empty decks
			$remaining_decks_slots = DECK_SLOTS - count($starter_decks);
			for ($i = 1; $i <= $remaining_decks_slots; $i++)
			{
				$deck = $deckdb->createDeck($playername, 'deck '.$i);
				if ($deck === false) { $db->txnRollBack(); return false; }
			}

			if (!$settingdb->createSettings($playername)) { $db->txnRollBack(); return false; }
			if (!$messagedb->welcomeMessage($playername)) { $db->txnRollBack(); return false; }
			
			$db->txnCommit();
			
			// create the object
			return new CPlayer($playername, "user", $this);
		}
		
		public function deletePlayer($playername)
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
			if (!$logindb->unregister($playername)) { $db->txnRollBack(); return false; }
			if (!$scoredb->deleteScore($playername)) { $db->txnRollBack(); return false; }

			foreach ($deckdb->listDecks($playername) as $deck_data)
				if (!$deckdb->deleteDeck($deck_data['DeckID'])) { $db->txnRollBack(); return false; }

			if (!$settingdb->deleteSettings($playername)) { $db->txnRollBack(); return false; }
			if (!$gamedb->deleteGames($playername)) { $db->txnRollBack(); return false; }
			if (!$messagedb->deleteMessages($playername)) { $db->txnRollBack(); return false; }

			$db->txnCommit();

			return true;
		}
		
		public function renamePlayer($playername, $new_name)
		{
			$db = $this->db;
			$db->txnBegin();
			
			$success = true;
			$success = $success && false !== $db->query('UPDATE `chats` SET `Name` = ? WHERE `Name` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `concepts` SET `Author` = ? WHERE `Author` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `decks` SET `Username` = ? WHERE `Username` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `forum_posts` SET `Author` = ? WHERE `Author` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `forum_threads` SET `Author` = ? WHERE `Author` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `forum_threads` SET `LastAuthor` = ? WHERE `LastAuthor` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `games` SET `Player1` = ? WHERE `Player1` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `games` SET `Player2` = ? WHERE `Player2` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `games` SET `Current` = ? WHERE `Current` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `games` SET `Winner` = ? WHERE `Winner` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `games` SET `Surrender` = ? WHERE `Surrender` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `logins` SET `Username` = ? WHERE `Username` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `messages` SET `Author` = ? WHERE `Author` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `messages` SET `Recipient` = ? WHERE `Recipient` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `replays` SET `Player1` = ? WHERE `Player1` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `replays` SET `Player2` = ? WHERE `Player2` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `replays` SET `Winner` = ? WHERE `Winner` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `scores` SET `Username` = ? WHERE `Username` = ?', array($new_name, $playername));
			$success = $success && false !== $db->query('UPDATE `settings` SET `Username` = ? WHERE `Username` = ?', array($new_name, $playername));

			if( $success ) $db->txnCommit(); else $db->txnRollBack();
			return $success;
		}
		
		public function getPlayer($playername)
		{
			$db = $this->db;
			//TODO: instead of this, use a multijoin to check if $playername is in all required tables
			$result = $db->query('SELECT `UserType` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$type = $data['UserType'];
			
			return new CPlayer($playername, $type, $this);
		}
		
		public function listPlayers($activity, $status, $name, $condition, $order, $page)
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
    
			$result = $db->query($query, $params);
			if ($result === false) return false;

			return $result;
		}
		
		public function countPages($activity, $status, $name)
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

			$result = $db->query('SELECT COUNT(`Username`) as `Count` FROM `logins`'.$status_q.' WHERE 1'.$activity_q.$name_q.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			$pages = ceil($data['Count'] / PLAYERS_PER_PAGE);
			
			return $pages;
		}
		
		public function changeAccessRights($playername, $access_right)
		{
			$db = $this->db;
			
			$result = $db->query('UPDATE `logins` SET `UserType` = ? WHERE `Username` = ?', array($access_right, $playername));
			if ($result === false) return false;
			
			return true;
		}
		
		public function resetNotification($playername)
		{
			$db = $this->db;
			
			$result = $db->query('UPDATE `logins` SET `Notification` = `Last Query` WHERE `Username` = ?', array($playername));
			if ($result === false) return false;
			
			return true;
		}
		
		public function isOnline($playername)
		{
			$db = $this->db;

			$result = $db->query('SELECT `Last Query` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return( time() - strtotime($data['Last Query']) < 60*10 );
		}
		
		public function isDead($playername)
		{
			$db = $this->db;

			$result = $db->query('SELECT `Last Query` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return ( time() - strtotime($data['Last Query']) > 60*60*24*7*3 );
		}
		
		public function getNotification($playername)
		{
			$db = $this->db;

			$result = $db->query('SELECT `Notification` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['Notification'];
		}
		
		public function lastquery($playername)
		{
			$db = $this->db;

			$result = $db->query('SELECT `Last Query` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['Last Query'];
		}
		
		public function registered($playername)
		{
			$db = $this->db;

			$result = $db->query('SELECT `Registered` FROM `logins` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['Registered'];
		}
		
		public function getLevel($playername)
		{
			$db = $this->db;

			$result = $db->query('SELECT `Level` FROM `scores` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['Level'];
		}
		
		public function getGameSlots($playername)
		{
			$db = $this->db;

			$result = $db->query('SELECT `GameSlots` FROM `scores` WHERE `Username` = ?', array($playername));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data['GameSlots'];
		}
		
		public function getGuest()
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
		
		public function name()
		{
			return $this->Name;
		}
		
		public function type()
		{
			return $this->Type;
		}
		
		public function resetNotification()
		{
			return $this->Players->resetNotification($this->Name);
		}
		
		public function isOnline()
		{
			return $this->Players->isOnline($this->Name);
		}
		
		public function isDead()
		{
			return $this->Players->isDead($this->Name);
		}
		
		public function getNotification()
		{
			return $this->Players->getNotification($this->Name);
		}
		
		public function lastquery()
		{
			return $this->Players->lastquery($this->Name);
		}
		
		public function registered()
		{
			return $this->Players->registered($this->Name);
		}
		
		public function getScore()
		{
			global $scoredb;
			return $scoredb->getScore($this->Name);
		}
		
		public function getLevel()
		{
			return $this->Players->getLevel($this->Name);
		}
		
		public function getGameSlots()
		{
			return $this->Players->getGameSlots($this->Name);
		}
		
		public function getDeck($deck_id)
		{
			global $deckdb;

			$deck = $deckdb->getDeck($deck_id);
			if (!$deck or $deck->username() != $this->Name) return false;

			return $deck;
		}
		
		public function listDecks()
		{
			global $deckdb;
			return $deckdb->listDecks($this->Name);
		}
		
		public function listReadyDecks()
		{
			global $deckdb;
			return $deckdb->listReadyDecks($this->Name);
		}

		public function getSettings()
		{
			global $settingdb;
			return $settingdb->getSettings($this->Name);
		}

		public function getversusStats($opponent)
		{
			global $statistics;
			return ($this->Name == $opponent) ? $statistics->gameStats($this->Name) : $statistics->versusStats($this->Name, $opponent);
		}

		public function freeSlots()
		{
			global $gamedb;
			return $gamedb->countFreeSlots1($this->Name);
		}

		public function changeAccessRights($access_right)
		{
			return $this->Players->changeAccessRights($this->Name, $access_right);
		}

		public function changePassword($password)
		{
			global $logindb;
			return $logindb->changePassword($this->Name, $password);
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

		public function getNotification()
		{
			return date('Y-m-d H:i:s', time() + 24*60*60); // disable notification
		}

		public function getSettings()
		{
			global $settingdb;

			$settings = $settingdb->getGuestSettings();
			$settings->changeSetting('Skin', 0);
			$settings->changeSetting('Timezone', 0);
			$settings->changeSetting('Autorefresh', 0);
			$settings->changeSetting('Images', 'yes');
			$settings->changeSetting('OldCardLook', 'no');
			$settings->changeSetting('Insignias', 'yes');
			$settings->changeSetting('Country', 'Unknown');
			$settings->changeSetting('Avatar', 'noavatar.jpg');
			$settings->changeSetting('Nationality', 'no');
			$settings->changeSetting('Avatarlist', 'yes');
			$settings->changeSetting('DefaultFilter', 'none');
			$settings->changeSetting('FoilCards', '');

			return $settings;
		}
	}
?>
