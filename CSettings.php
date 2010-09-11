<?php
/*
	CSettings - player settings
*/
?>
<?php
	class CSettings
	{
		private $db;
		private $cache;

		public function __construct(CDatabase &$database)
		{
			$this->db = &$database;
			$this->cache = array();
		}

		public function GetDB()
		{
			return $this->db;
		}

		public function CreateSettings($username) //creates default settings
		{
			$db = $this->db;
			$result = $db->Query('INSERT INTO `settings` (`Username`) VALUES ("'.$db->Escape($username).'")');
			if (!$result) return false;

			return true;
		}

		public function DeleteSettings($username) //delete user settings
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `settings` WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) return false;

			return true;
		}

		public function GetSettings($username)
		{
			if( isset($this->cache[$username]) )
				return $this->cache[$username]; // load from cache

			$settings = new CSetting($username, $this);
			if( !$settings->LoadSettings() ) // load from db
				return false;

			$this->cache[$username] = $settings; // cache it
			return $settings;
		}
	}

	class CSetting
	{
		private $Settings;
		private $Username;
		private $Data;

		public function __construct($username, CSettings $Settings)
		{
			$this->Settings = &$Settings;
			$this->Username = $username;

			$bool_settings = $this->ListBooleanSettings();
			$other_settings = $this->ListOtherSettings();
			$all_settings = array_merge($bool_settings, $other_settings);

			$this->Data = array_combine($all_settings, array_fill(0, count($all_settings), ''));
		}

		public function LoadSettings()
		{
			$db = $this->Settings->getDB();

			$result = $db->Query('SELECT * FROM `settings` WHERE `Username` = "'.$db->Escape($this->Username).'"');
			if( !$result or !$result->Rows() ) return false;

			$data = $result->Next();

			$bool_settings = $this->ListBooleanSettings();
			$other_settings = $this->ListOtherSettings();

			foreach ($bool_settings as $setting) $this->Data[$setting] = ($data[$setting] == 1) ? 'yes' : 'no';
			foreach ($other_settings as $setting) $this->Data[$setting] = $data[$setting];

			return true;
		}

		public function SaveSettings()
		{
			$db = $this->Settings->getDB();

			$bool_settings = $this->ListBooleanSettings();
			$other_settings = $this->ListOtherSettings();
			$query = array();

			foreach ($bool_settings as $setting) $query[] = '`'.$setting.'` = "'.(($this->Data[$setting] == 'yes') ? '1' : '0').'"';
			foreach ($other_settings as $setting) $query[] = '`'.$setting.'` = "'.$db->Escape($this->Data[$setting]).'"';
			$query = implode(", ", $query);

			$result = $db->Query('UPDATE `settings` SET '.$query.' WHERE `Username` = "'.$db->Escape($this->Username).'"');
			if (!$result) return false;

			return true;
		}

		public function GetAll() // return all settings
		{
			return $this->Data;
		}

		public function GetSetting($setting) // get specific setting
		{
			return $this->Data[$setting];
		}

		public function ChangeSetting($setting, $value) // change specific setting
		{
			$this->Data[$setting] = $value;
		}

		public function Age() // Calculates age from birthdate (standard date string)
		{
			list($year, $month, $day) = explode("-", $this->Data['Birthdate']);

			$age = date("Y") - $year;
			if (date('m') < $month) $age--;
			elseif (date('m') == $month and date('d') < $day) $age--;

			return $age;
		}

		public function Sign() // Calculates sign from birthdate (date string)
		{
			$birthdate = strtotime($this->Data['Birthdate']);
			$month = intval(date("m", $birthdate));
			$day = intval(date("j", $birthdate));

			if ((($month == 3) AND ($day >= 21)) OR (($month == 4) AND ($day <= 19))) $sign = "Aries";
			elseif ((($month == 4) AND ($day >= 20)) OR (($month == 5) AND ($day <= 20))) $sign = "Taurus";
			elseif ((($month == 5) AND ($day >= 21)) OR (($month == 6) AND ($day <= 20))) $sign = "Gemini";
			elseif ((($month == 6) AND ($day >= 21)) OR (($month == 7) AND ($day <= 22))) $sign = "Cancer";
			elseif ((($month == 7) AND ($day >= 23)) OR (($month == 8) AND ($day <= 22))) $sign = "Leo";
			elseif ((($month == 8) AND ($day >= 23)) OR (($month == 9) AND ($day <= 22))) $sign = "Virgo";
			elseif ((($month == 9) AND ($day >= 23)) OR (($month == 10) AND ($day <= 22))) $sign = "Libra";
			elseif ((($month == 10) AND ($day >= 23)) OR (($month == 11) AND ($day <= 21))) $sign = "Scorpio";
			elseif ((($month == 11) AND ($day >= 22)) OR (($month == 12) AND ($day <= 21))) $sign = "Sagittarius";
			elseif ((($month == 12) AND ($day >= 22)) OR (($month == 1) AND ($day <= 19))) $sign = "Capricorn";
			elseif ((($month == 1) AND ($day >= 20)) OR (($month == 2) AND ($day <= 18))) $sign = "Aquarius";
			elseif ((($month == 2) AND ($day >= 19)) OR (($month == 3) AND ($day <= 20))) $sign = "Pisces";
			else $sign = "Unknown";

			return $sign;
		}

		public function ListBooleanSettings() // returns list of all boolean type setting names
		{
			return array('FriendlyFlag', 'BlindFlag', 'Minimize', 'Cardtext', 'Images', 'Keywords', 'Nationality', 'Chatorder', 'Avatargame', 'Avatarlist', 'Correction', 'OldCardLook', 'Reports', 'Forum_notification', 'Concepts_notification', 'GamesDetails', 'RandomDeck');
		}

		public function ListOtherSettings() // returns list of all non-boolean type setting names
		{
			return array('Firstname', 'Surname', 'Birthdate', 'Gender', 'Email', 'Imnumber', 'Country', 'Hobby', 'Avatar', 'Status', 'Timezone', 'Skin', 'Background', 'DefaultFilter', 'Autorefresh');
		}
	}
?>
