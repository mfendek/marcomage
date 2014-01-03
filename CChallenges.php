<?php
	// AI challenges - AI challenges configuration database
?>
<?php
	class CChallenges
	{
		private $db;

		public function __construct()
		{
			$this->db = false;
		}

		public function __destruct()
		{
			$this->db = false;
		}

		public function getDB()
		{
			// initialize on first use
			if( $this->db === false )
			{
				$this->db = new SimpleXMLElement('templates/challenges.xml', 0, TRUE);
				$this->db->registerXPathNamespace('am', 'http://arcomage.netvor.sk');
			}

			return $this->db;
		}

		public function listChallenges() // get list of available AI challenges
		{
			$db = $this->getDB();
			$result = $db->xpath('/am:challenges/am:challenge');

			if ($result === false OR count($result) == 0) return array();

			$challenges = array();
			foreach ($result as $challenge)
			{
				$cur_challenge['name'] = (string)$challenge->attributes()->name;
				$cur_challenge['fullname'] = (string)$challenge->fullname;
				$cur_challenge['description'] = (string)$challenge->description;
				$challenges[] = $cur_challenge;
			}

			return $challenges;
		}

		public function listChallengeNames() // get list of AI challenges names
		{
			$db = $this->getDB();
			$result = $db->xpath('/am:challenges/am:challenge');

			if ($result === false OR count($result) == 0) return array();

			$challenges = array();
			foreach ($result as $challenge)
				$challenges[] = (string)$challenge->attributes()->name;

			return $challenges;
		}

		public function getChallenge($challenge_name)
		{
			$db = $this->getDB();
			$result = $db->xpath('/am:challenges/am:challenge[@name="'.$challenge_name.'"]');

			if ($result === false OR count($result) == 0) return false;

			$data = &$result[0];

			foreach (array('mine', 'his') as $player)
				foreach (array('Quarry', 'Magic', 'Dungeons', 'Bricks', 'Gems', 'Recruits', 'Tower', 'Wall') as $attr)
				{
					$attribute = strtolower($attr);
					$init[$player][$attr] = (int)$data->initialization->$player->$attribute;
					$config[$player][$attr] = (int)$data->config->$player->$attribute;
				}

			return new CChallenge($challenge_name, $init, $config);
		}
	}

	class CChallenge
	{
		public $Name;
		public $Init; // game intialization data
		public $Config; // AI custom configuration data

		public function __construct($name, $init, $config)
		{
			$this->Name = $name;
			$this->Init = $init;
			$this->Config = $config;
		}
	}
?>
