<?php
/*
	CScore - the ranking of a player
*/
?>
<?php
	class CScores
	{
		private $db;
		public $Awards;
		
		public function __construct(CDatabase &$database)
		{
			$this->db = &$database;
			$this->Awards = new CAwards();
		}
		
		public function getDB()
		{
			return $this->db;
		}
		
		public function createScore($username)
		{
			$db = $this->db;

			$result = $db->query('INSERT INTO `scores` (`Username`) VALUES (?)', array($username));
			if ($result === false) return false;

			return true;
		}
		
		public function deleteScore($username)
		{
			$db = $this->db;

			$result = $db->query('DELETE FROM `scores` WHERE `Username` = ?', array($username));
			if ($result === false) return false;
			
			return true;
		}
		
		public function getScore($username)
		{
			$db = $this->db;

			$result = $db->query('SELECT 1 FROM `scores` WHERE `Username` = ?', array($username));
			if ($result === false or count($result) == 0) return false;
			
			$score = new CScore($username, $this);
			$score->loadScore();
			
			return $score;
		}
		
		public function nextLevel($level)
		{
			return (500 + 50 * $level + 200 * floor($level / 5) + 100 * pow(floor($level / 10), 2));
		}
	}
	
	
	class CScore
	{
		private $Username = '';
		private $Scores = false;
		private $AwardsList;
		public $ScoreData = false;
		
		public function __construct($username, CScores &$Scores)
		{
			$this->Username = $username;
			$this->Scores = &$Scores;
			$this->ScoreData = new CScoreData;
			$this->AwardsList = $this->Scores->Awards->awardsNames();
		}
		
		public function __destruct()
		{
			$this->Username = '';
			$this->Scores = false;
			$this->ScoreData = false;
		}
		
		public function username()
		{
			return $this->Username;
		}
		
		public function loadScore()
		{
			$db = $this->Scores->getDB();

			$awards_q = '';
			foreach ($this->AwardsList as $award) $awards_q.= ', `'.$award.'`';
			
			$result = $db->query('SELECT `Level`, `Exp`, `Gold`, `Wins`, `Losses`, `Draws`, `GameSlots`'.$awards_q.' FROM `scores` WHERE `Username` = ?', array($this->Username));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$this->ScoreData->Level = $data['Level'];
			$this->ScoreData->Exp = $data['Exp'];
			$this->ScoreData->Gold = $data['Gold'];
			$this->ScoreData->Wins = $data['Wins'];
			$this->ScoreData->Losses = $data['Losses'];
			$this->ScoreData->Draws = $data['Draws'];
			$this->ScoreData->GameSlots = $data['GameSlots'];
			
			// load awards
			foreach ($this->AwardsList as $award) $this->ScoreData->Awards[$award] = $data[$award];
			
			return true;
		}
		
		public function saveScore()
		{
			$db = $this->Scores->getDB();
			
			$params = array($this->ScoreData->Level, $this->ScoreData->Exp, $this->ScoreData->Gold, $this->ScoreData->Wins, $this->ScoreData->Losses, $this->ScoreData->Draws, $this->ScoreData->GameSlots);

			$awards_q = '';
			foreach ($this->AwardsList as $award)
			{
				$awards_q.= ', `'.$award.'` = ?';
				$params[] = $this->ScoreData->Awards[$award];
			}
			$params[] = $this->Username;
			
			$result = $db->query('UPDATE `scores` SET `Level` = ?, `Exp` = ?, `Gold` = ?, `Wins` = ?, `Losses` = ?, `Draws` = ?, `GameSlots` = ?'.$awards_q.' WHERE `Username` = ?', $params);
			if ($result === false) return false;
			
			return true;
		}
		
		public function addExp($exp)
		{
			$level_up = false;
			$nextlevel = $this->Scores->nextLevel($this->ScoreData->Level);
			$current_exp = $this->ScoreData->Exp + $exp;
			
			if ($current_exp >= $nextlevel) // level up (gains 100 gold at levelup)
			{
				$current_exp-= $nextlevel;
				$this->ScoreData->Level++;
				$this->ScoreData->Gold+= 100;
				$level_up = true;
			}
			
			$this->ScoreData->Exp = $current_exp;
			
			return $level_up;
		}
		
		public function resetExp()
		{
			$this->ScoreData->Exp = 0;
			$this->ScoreData->Level = 0;
			$this->ScoreData->Gold = 0;
			$this->ScoreData->GameSlots = 0;
		}
		
		public function addGold($gold)
		{
			$this->ScoreData->Gold+= $gold;
		}
		
		public function buyItem($gold) // purchase item if player can afford it
		{
			if ($this->ScoreData->Gold < $gold) return false;
			$this->ScoreData->Gold-= $gold;
			
			return true;
		}
		
		public function gainAwards(array $awards) // update score on specified game awards
		{
			if (count($awards) == 0) return false;
			
			foreach ($awards as $award) $this->updateAward($award);
			
			return true;
		}
		
		public function updateAward($award, $amount = 1) // update score on specified game award by specified amount
		{
			global $messagedb;
			
			$award = str_replace(' ', '_', $award); // replace WS with _
			
			// check if award is supported
			if (!in_array($award, $this->AwardsList)) return false;
			
			$before = $this->ScoreData->Awards[$award];
			$this->ScoreData->Awards[$award]+= $amount;
			$after = $this->ScoreData->Awards[$award];
			
			// check if player gained achievement of specified award
			$achievement = $this->checkAward($award, $before, $after);
			
			if ($achievement)
			{
				// reward player with gold
				$this->addGold($achievement['reward']);
				
				// inform player about achievement gain
				$messagedb->achievementNotification($this->username(), $achievement['name'], $achievement['reward']);
				
				// check final achievement of the same tier as recently gained achievement
				if ($this->checkFinalAchievement($achievement['tier']))
				{
					// get final achievement data
					$final = $this->finalAchievements($achievement['tier']);
					
					// reward player with gold
					$this->addGold($final['reward']);
					
					// inform player about achievement gain
					$messagedb->achievementNotification($this->username(), $final['name'], $final['reward']);
				}
			}
			
			return true;
		}
		
		public function achievementsData() // get all achievements data (group by tier)
		{
			$data = array();
			
			// prepare achievements data
			foreach ($this->AwardsList as $award)
			{
				$achievements = $this->Scores->Awards->getAchievements($award);
				foreach ($achievements as $achievement)
				{
					$achievement['count'] = $this->ScoreData->Awards[$award];
					$data[$achievement['tier']][] = $achievement;
				}
			}
			
			// add final achievement data
			$final = $this->finalAchievements();
			foreach ($final as $tier => $achievement)
			{
				// in this case condition holds the information if player has this achievement (yes/no)
				$achievement['condition'] = ($this->checkFinalAchievement($tier)) ? 'yes' : 'no';
				$achievement['count'] = '';
				$achievement['tier'] = $tier;
				$data[$tier][] = $achievement;
			}
			
			return $data;
		}
		
		private function checkAward($award, $before, $after) // check if any achievement of specified award was gained
		{
			$achievements = $this->Scores->Awards->getAchievements($award);
			
			foreach ($achievements as $achievement)
				if ($before < $achievement['condition'] AND $after >= $achievement['condition']) return $achievement;
			
			return false;
		}
		
		private function checkFinalAchievement($tier) // check if player has final achievement with specified tier
		{
			foreach ($this->AwardsList as $award)
				if (!$this->checkAchievement($award, $tier)) return false;
			
			return true;
		}
		
		private function checkAchievement($award, $tier) // check if player has achievement of specified award with specified tier
		{
			$achievement = $this->Scores->Awards->getAchievement($award, $tier);
			
			if ($this->ScoreData->Awards[$award] < $achievement['condition']) return false;
			
			return true;
		}
		
		private function finalAchievements($tier = '') // returns final achievement(s) data based on specified tier (optional)
		{
			// final achievement is gained only if player already has all other achievements of the same tier
			
			// Veteran (final achievement tier 1)
			$final[1]['name'] = 'Veteran';
			$final[1]['reward'] = '1250';
			$final[1]['desc'] = 'gain every tier 1 achievement';
			
			// Champion (final achievement tier 2)
			$final[2]['name'] = 'Champion';
			$final[2]['reward'] = '2500';
			$final[2]['desc'] = 'gain every tier 2 achievement';
			
			// Grandmaster (final achievement tier 3)
			$final[3]['name'] = 'Grandmaster';
			$final[3]['reward'] = '3750';
			$final[3]['desc'] = 'gain every tier 3 achievement';
			
			return (($tier != '') ? $final[$tier] : $final);
		}
	}
	
	
	class CScoreData
	{
		public $Level = 0;
		public $Exp = 0;
		public $Gold = 0;
		public $Wins = 0;
		public $Losses = 0;
		public $Draws = 0;
		public $GameSlots = 0;
		public $Awards; // game awards gained by player 'award_name' => 'award_count'
	}
?>
