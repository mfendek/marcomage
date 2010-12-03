<?php
/*
	CScore - the ranking of a player
*/
?>
<?php
	class CScores
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
		
		public function CreateScore($username)
		{
			$score = new CScore($username, $this);
			
			$db = $this->db;
			$result = $db->Query('INSERT INTO `scores` (`Username`) VALUES ("'.$db->Escape($username).'")');
			if (!$result) return false;
			
			return $score;
		}
		
		public function DeleteScore($username)
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `scores` WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function GetScore($username)
		{
			$db = $this->db;
			$result = $db->Query('SELECT 1 FROM `scores` WHERE `Username` = "'.$db->Escape($username).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$score = new CScore($username, $this);
			$score->LoadScore();
			
			return $score;
		}
		
		public function NextLevel($level)
		{
			return (500 + 50 * $level + 200 * floor($level / 5) + 100 * pow(floor($level / 10), 2));
		}
	}
	
	
	class CScore
	{
		private $Username = '';
		private $Scores = false;
		private $AwardsList = array('Assassin', 'Builder', 'Carpenter', 'Collector', 'Desolator', 'Dragon', 'Gentle_touch', 'Snob', 'Survivor', 'Titan');
		public $ScoreData = false;
		
		public function __construct($username, CScores &$Scores)
		{
			$this->Username = $username;
			$this->Scores = &$Scores;
			$this->ScoreData = new CScoreData;
		}
		
		public function __destruct()
		{
			$this->Username = '';
			$this->Scores = false;
			$this->ScoreData = false;
		}
		
		public function Username()
		{
			return $this->Username;
		}
		
		public function LoadScore()
		{
			$db = $this->Scores->getDB();
			$awards_q = '';
			foreach ($this->AwardsList as $award) $awards_q.= ', `'.$award.'`';
			
			$result = $db->Query('SELECT `Level`, `Exp`, `Gold`, `Wins`, `Losses`, `Draws`, `GameSlots`'.$awards_q.' FROM `scores` WHERE `Username` = "'.$db->Escape($this->Username).'"');
			if (!$result) return false;
			
			$data = $result->Next();
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
		
		public function SaveScore()
		{
			$db = $this->Scores->getDB();
			
			$awards_q = '';
			foreach ($this->AwardsList as $award) $awards_q.= ', `'.$award.'` = '.$this->ScoreData->Awards[$award];
			
			$result = $db->Query('UPDATE `scores` SET `Level` = '.$this->ScoreData->Level.', `Exp` = '.$this->ScoreData->Exp.', `Gold` = '.$this->ScoreData->Gold.', `Wins` = '.$this->ScoreData->Wins.', `Losses` = '.$this->ScoreData->Losses.', `Draws` = '.$this->ScoreData->Draws.', `GameSlots` = '.$this->ScoreData->GameSlots.''.$awards_q.' WHERE `Username` = "'.$db->Escape($this->Username).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function AddExp($exp)
		{
			$level_up = false;
			$nextlevel = $this->Scores->NextLevel($this->ScoreData->Level);
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
		
		public function ResetExp()
		{
			$this->ScoreData->Exp = 0;
			$this->ScoreData->Level = 0;
			$this->ScoreData->Gold = 0;
			$this->ScoreData->GameSlots = 0;
		}
		
		public function AddGold($gold)
		{
			$this->ScoreData->Gold+= $gold;
		}
		
		public function BuyItem($gold) // purchase item if player can afford it
		{
			if ($this->ScoreData->Gold < $gold) return false;
			$this->ScoreData->Gold-= $gold;
			
			return true;
		}
		
		public function GainAwards(array $awards) // update score on specified game awards
		{
			if (count($awards) == 0) return false;
			
			foreach ($awards as $award)
			{
				$award = str_replace(' ', '_', $award); // replace WS with _
				if (in_array($award, $this->AwardsList)) $this->ScoreData->Awards[$award]++;
			}
			
			return true;
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
		public $Awards;
	}
?>
