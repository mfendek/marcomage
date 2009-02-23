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
			$settings = array("Timezone" => "Timezone", "Minimize" => "Minimize", "Cardtext" => "Cardtext", "Images" => "Images", "Keywords" => "Keywords", "Nationality" => "Nationality", "Chatorder" => "Chatorder", "Avatargame" => "Avatargame", "Avatarlist" => "Avatarlist", "Showdead" => "Showdead", "Correction" => "Correction", "OldCardLook" => "OldCardLook");
			
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
		
		/// Returns the list of all supported timezones with GMT offset values.
		public function TimeZones()
		{
			return array(
			"-12" => "Eniwetok, Kwajalein",
			"-11" => "Midway Island, Samoa",
			"-10" => "Hawaii",
			"-9" => "Alaska",
			"-8" => "Pacific Time (US &amp; Canada), Tijuana",
			"-7" => "Mountain Time (US &amp; Canada), Arizona",
			"-6" => "Central Time (US &amp; Canada), Mexico City",
			"-5" => "Eastern Time (US &amp; Canada), Bogota, Lima, Quito",
			"-4" => "Atlantic Time (Canada), Caracas, La Paz",
			"-3" => "Brassila, Buenos Aires, Georgetown, Falkland Is",
			"-2" => "Mid-Atlantic, Ascension Is., St. Helena",
			"-1" => "Azores, Cape Verde Islands",
			"+0" => "Casablanca, Dublin, Edinburgh, London, Lisbon, Monrovia",
			"+1" => "Prague, Amsterdam, Berlin, Brussels, Madrid, Paris",
			"+2" => "Cairo, Helsinki, Kaliningrad, South Africa",
			"+3" => "Baghdad, Riyadh, Moscow, Nairobi",
			"+4" => "Abu Dhabi, Baku, Muscat, Tbilisi",
			"+5" => "Ekaterinburg, Islamabad, Karachi, Tashkent",
			"+6" => "Almaty, Colombo, Dhaka, Novosibirsk",
			"+7" => "Bangkok, Hanoi, Jakarta",
			"+8" => "Beijing, Hong Kong, Perth, Singapore, Taipei",
			"+9" => "Osaka, Sapporo, Seoul, Tokyo, Yakutsk",
			"+10" => "Canberra, Guam, Melbourne, Sydney, Vladivostok",
			"+11" => "Magadan, New Caledonia, Solomon Islands",
			"+12" => "Auckland, Wellington, Fiji, Marshall Island");
		}
		
		// Returns list of all supported country names.
		public function CountryNames()
		{
			return array(
				"I'm a pirate - no country" => "Unknown",
				"Albania" => "Albania",
				"Algeria" => "Algeria",
				"Argentina" => "Argentina",
				"Armenia" => "Armenia",
				"Australia" => "Australia",
				"Austria" => "Austria",
				"Azerbaijan" => "Azerbaijan",
				"Bahamas" => "Bahamas",
				"Barbados" => "Barbados",
				"Belarus" => "Belarus",
				"Belgium" => "Belgium",
				"Bolivia" => "Bolivia",
				"Bosnia and Herzegovina" => "Bosnia and Herzegovina",
				"Brazil" => "Brazil",
				"Bulgaria" => "Bulgaria",
				"Cambodia" => "Cambodia",
				"Canada" => "Canada",
				"Chile" => "Chile",
				"China" => "China",
				"Chinese Taipei" => "Chinese Taipei",
				"Colombia" => "Colombia",
				"Costa Rica" => "Costa Rica",
				"Croatia" => "Croatia",
				"Cuba" => "Cuba",
				"Cyprus" => "Cyprus",
				"Czech Republic" => "Czech Republic",
				"Denmark" => "Denmark",
				"Dominican Republic" => "Dominican Republic",
				"Ecuador" => "Ecuador",
				"United Kingdom" => "United Kingdom",
				"Eritrea" => "Eritrea",
				"Estonia" => "Estonia",
				"Ethiopia" => "Ethiopia",
				"Europe" => "Europe",
				"Fiji Islands" => "Fiji Islands",
				"Finland" => "Finland",
				"France" => "France",
				"Germany" => "Germany",
				"Ghana" => "Ghana",
				"Greece" => "Greece",
				"Greenland" => "Greenland",
				"Guatemala" => "Guatemala",
				"Hungary" => "Hungary",
				"Iceland" => "Iceland",
				"India" => "India",
				"Indonesia" => "Indonesia",
				"Iran" => "Iran",
				"Iraq" => "Iraq",
				"Ireland" => "Ireland",
				"Israel" => "Israel",
				"Italy" => "Italy",
				"Ivory Coast" => "Ivory Coast",
				"Japan" => "Japan",
				"Jamaica" => "Jamaica",
				"Kazakstan" => "Kazakstan",
				"Kenya" => "Kenya",
				"Laos" => "Laos",
				"Latvia" => "Latvia",
				"Liechtenstein" => "Liechtenstein",
				"Lithuania" => "Lithuania",
				"Macedonia" => "Macedonia",
				"Malaysia" => "Malaysia",
				"Mexico" => "Mexico",
				"Moldova" => "Moldova",
				"Morocco" => "Morocco",
				"Netherlands" => "Netherlands",
				"New Zealand" => "New Zealand",
				"North Korea" => "North Korea",
				"Norway" => "Norway",
				"Pakistan" => "Pakistan",
				"Panama" => "Panama",
				"Paraguay" => "Paraguay",
				"Peru" => "Peru",
				"Philippines" => "Philippines",
				"Poland" => "Poland",
				"Portugal" => "Portugal",
				"Puerto Rico" => "Puerto Rico",
				"Russia" => "Russia",
				"Romania" => "Romania",
				"Salvador" => "Salvador",
				"San Marino" => "San Marino",
				"Saudi Arabia" => "Saudi Arabia",
				"Serbia" => "Serbia",
				"Singapore" => "Singapore",
				"Slovakia" => "Slovakia",
				"Slovenia" => "Slovenia",
				"Somalia" => "Somalia",
				"South Africa" => "South Africa",
				"South Korea" => "South Korea",
				"Spain" => "Spain",
				"Sri Lanka" => "Sri Lanka",
				"Sudan" => "Sudan",
				"Sweden" => "Sweden",
				"Switzerland" => "Switzerland",
				"Taiwan" => "Taiwan",
				"Thailand" => "Thailand",
				"Togo" => "Togo",
				"Trinidad" => "Trinidad",
				"Turkey" => "Turkey",
				"Ukraine" => "Ukraine",
				"United Arab Emirates" => "United Arab Emirates",
				"United Kingdom" => "United Kingdom",
				"United States" => "United States",
				"Uzbekistan" => "Uzbekistan",
				"Venezuela" => "Venezuela",
				"Vietnam" => "Vietnam",
				"Zimbabwe" => "Zimbabwe");
		}
		
	}

?>
