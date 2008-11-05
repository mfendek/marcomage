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
			$result = $db->Query('INSERT INTO `scores` (`Username`, `Wins`, `Losses`, `Draws`) VALUES ("'.$db->Escape($username).'", '.$score->ScoreData->Wins.', '.$score->ScoreData->Losses.', '.$score->ScoreData->Draws.')');
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
	}
	
	
	class CScore
	{
		private $Username = '';
		private $Scores = false;
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
			$result = $db->Query('SELECT `Wins`, `Losses`, `Draws` FROM `scores` WHERE `Username` = "'.$db->Escape($this->Username).'"');
			if (!$result) return false;
			
			$data = $result->Next();
			$this->ScoreData->Wins = $data['Wins'];
			$this->ScoreData->Losses = $data['Losses'];
			$this->ScoreData->Draws = $data['Draws'];
			
			return true;
		}
		
		public function SaveScore()
		{
			$db = $this->Scores->getDB();
			$result = $db->Query('UPDATE `scores` SET `Wins` = '.$this->ScoreData->Wins.', `Losses` = '.$this->ScoreData->Losses.', `Draws` = '.$this->ScoreData->Draws.' WHERE `Username` = "'.$db->Escape($this->Username).'"');
			if (!$result) return false;
			
			return true;
		}
	}
	
	
	class CScoreData
	{
		public $Wins = 0;
		public $Losses = 0;
		public $Draws = 0;
	}
?>
