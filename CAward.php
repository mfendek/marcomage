<?php
/*
	CAwards - game awards XML database (contains player achievements)
*/
?>
<?php
	class CAwards
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
				$this->db = new SimpleXMLElement('templates/awards.xml', 0, TRUE);
				$this->db->registerXPathNamespace('am', 'http://arcomage.netvor.sk');
			}

			return $this->db;
		}

		public function getAchievements($award_name) // get achievement list of specified award
		{
			$db = $this->getDB();
			$result = $db->xpath('/am:awards/am:award[@name="'.$award_name.'"]');

			if ($result === false OR count($result) == 0) return array();

			$award_data = &$result[0];
			$description = $award_data->attributes()->desc;
			$i = 1;
			$achievements = array();
			foreach ($award_data->children() as $achievement)
			{
				foreach ($achievement->attributes() as $attr_name => $attr_value)
					$achievements[$i][$attr_name] = (string)$attr_value;

				// achievement description (replace # in the award description template by the achievement condition)
				$achievements[$i]['desc'] = str_replace('#', $achievements[$i]['condition'], $description);
				$achievements[$i]['tier'] = $i; // achievement tier (depends on position in the XML file)

				$i++;
			}

			return $achievements;
		}

		public function getAchievement($award_name, $tier) // get single achievement of specified award with specific tier
		{
			$db = $this->getDB();
			$result = $db->xpath('/am:awards/am:award[@name="'.$award_name.'"]/am:achievement[position() = '.$tier.']');

			if ($result === false OR count($result) == 0) return false;

			$data = &$result[0];
			$achievement = array();

			foreach ($data->attributes() as $attr_name => $attr_value)
				$achievement[$attr_name] = (string)$attr_value;

			return $achievement;
		}

		public function awardsNames() // list names of all awards
		{
			$db = $this->getDB();
			$result = $db->xpath('/am:awards/am:award');

			if ($result === false OR count($result) == 0) return array();

			$awards = array();
			foreach ($result as $award) $awards[] = (string)$award->attributes()->name;

			return $awards;
		}
	}
?>
