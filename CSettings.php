<?php
/*
	CSettings - player settings
*/
?>
<?php
	class CSettings
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
		
		public function CreateSettings($username) //creates default settings
		{		
			$db = $this->db;
			$result = $db->Query('INSERT INTO `settings` (`Username`) VALUES ("'.$db->Escape($username).'")');
			if (!$result) return false;
			
			return true;
		}
		
		public function ChangeSetting($username, $setting, $value) //change a specific setting
		{						
			$db = $this->db;
			$result = $db->Query('UPDATE `settings` SET `'.$setting.'` = "'.$db->Escape($value).'" WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function GetSetting($username, $setting) //retrieve a specific setting
		{						
			$db = $this->db;
			$result = $db->Query('SELECT `'.$setting.'` FROM `settings` WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data[$setting];
		}
		
		public function UserSettingsList() //returns list of all user setting names
		{			
			//translates input names to settings names
			$settings = array("Firstname" => "Firstname", "Surname" => "Surname", "Birthdate" => "Birthdate", "Gender" => "Gender", "Email" => "Email", "Imnumber" => "Imnumber", "Country" => "Country", "Hobby" => "Hobby", "Avatar" => "Avatar");
			
			return $settings;
		}
		
		public function GameSettingsList() //returns list of all game setting names
		{			
			//translates input names to settings names
			$settings = array("Timezone" => "Timezone", "Minimize" => "Minimize", "Cardtext" => "Cardtext", "Images" => "Images", "Keywords" => "Keywords", "Nationality" => "Nationality", "Chatorder" => "Chatorder", "Avatargame" => "Avatargame", "Avatarlist" => "Avatarlist", "Correction" => "Correction", "OldCardLook" => "OldCardLook", "Reports" => "Reports", "Forum_notification" => "Forum_notification", "Concepts_notification" => "Concepts_notification", "Skin" => "Skin", "Background" => "Background", "GamesDetails" => "GamesDetails", "PlayerFilter" => "PlayerFilter");
			
			return $settings;
		}		
		
		public function GetSettings($username, array $settings = array()) //retrieve settings specified in the array
		{									
			// build settings list
			$query = "";
			$first = true;
			foreach($settings as $sname => $svalue)
			{
				if( !$first ) $query .= ', ';
				$query .= "`$svalue`";
				$first = false;
			}
			if( $query == "" )
				$query = "*"; // get everything
			
			// get settings from database
			$db = $this->db;
			$result = $db->Query('SELECT '.$query.' FROM `settings` WHERE `Username` = "'.$db->Escape($username).'"');
			if( !$result or !$result->Rows() ) return false;
			
			$data = $result->Next();
			
			return $data;
		}
		
		// Calculates age from birthdate (standard date string)
		public function CalculateAge($birthdate)
		{
			list($year, $month, $day) = explode("-", $birthdate);
			
			$age = date("Y") - $year;
			if (date('m') < $month) $age--;
			elseif (date('m') == $month and date('d') < $day) $age--;
			
			return $age;
		}
		
		// Calculates sign from birthdate (date string).
		public function CalculateSign($birthdate)
		{
			$birthdate = strtotime($birthdate);
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
	
	}
?>
