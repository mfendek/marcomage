<?php
/*
	CConcepts - the representation of a card concept
*/
?>
<?php
	class CConcepts
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

		public function GetConcept($cardid)
		{
			return new CConcept($cardid, $this);
		}

		public function CreateConcept(array $data)
		{
			$db = $this->db;

			$result = $db->Query('INSERT INTO `concepts` (`Name`, `Class`, `Bricks`, `Gems`, `Recruits`, `Effect`, `Keywords`, `Note`, `Author`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', array($data['name'], $data['class'], $data['bricks'], $data['gems'], $data['recruits'], $data['effect'], $data['keywords'], $data['note'], $data['author']));
			if ($result === false) return false;

			return $db->LastID();
		}

		public function EditConcept($concept_id, array $data) // standard edit (normal user)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `Name` = ?, `Class` = ?, `Bricks` = ?, `Gems` = ?, `Recruits` = ?, `Effect` = ?, `Keywords` = ?, `Note` = ?, `LastChange` = NOW() WHERE `CardID` = ?', array($data['name'], $data['class'], $data['bricks'], $data['gems'], $data['recruits'], $data['effect'], $data['keywords'], $data['note'], $concept_id));
			if ($result === false) return false;

			return true;
		}

		public function EditConceptSpecial($concept_id, array $data) // special edit (admin)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `Name` = ?, `Class` = ?, `Bricks` = ?, `Gems` = ?, `Recruits` = ?, `Effect` = ?, `Keywords` = ?, `Note` = ?, `State` = ? WHERE `CardID` = ?', array($data['name'], $data['class'], $data['bricks'], $data['gems'], $data['recruits'], $data['effect'], $data['keywords'], $data['note'], $data['state'], $concept_id));
			if ($result === false) return false;

			return true;
		}

		public function EditPicture($concept_id, $picture)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `Picture` = ?, `LastChange` = NOW()  WHERE `CardID` = ?', array($picture, $concept_id));
			if ($result === false) return false;

			return true;
		}

		public function ResetPicture($concept_id)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `Picture` = "blank.jpg", `LastChange` = NOW()  WHERE `CardID` = ?', array($concept_id));
			if ($result === false) return false;

			return true;
		}

		public function DeleteConcept($concept_id)
		{
			$db = $this->db;

			$result = $db->Query('DELETE FROM `concepts` WHERE `CardID` = ?', array($concept_id));
			if ($result === false) return false;

			return true;
		}

		public function GetList($name, $author, $date, $state, $condition, $order, $page)
		{
			$db = $this->db;

			$name_query = ($name != '') ? ' AND `Name` LIKE ?' : '';
			$author_query = (($author != "none") ? ' AND `Author` = ?' : '');
			$date_query = (($date != "none") ? ' AND `LastChange` >= NOW() - INTERVAL ? DAY' : '');
			$state_query = (($state != "none") ? ' AND `State` = ?' : '');

			$params = array();
			if ($name != '') $params[] = '%'.$name.'%';
			if ($author != "none") $params[] = $author;
			if ($date != "none") $params[] = $date;
			if ($state != "none") $params[] = $state;

			$condition = (in_array($condition, array('Name', 'LastChange'))) ? $condition : 'LastChange';
			$order = ($order == 'ASC') ? 'ASC' : 'DESC';
			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->Query('SELECT `CardID` as `id`, `Name` as `name`, `Class` as `class`, `Bricks` as `bricks`, `Gems` as `gems`, `Recruits` as `recruits`, `Effect` as `effect`, `Keywords` as `keywords`, `Picture` as `picture`, `Note` as `note`, `State` as `state`, `Author` as `author`, `LastChange` as `lastchange` FROM `concepts` WHERE 1'.$name_query.$author_query.$date_query.$state_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(CARDS_PER_PAGE * $page).' , '.CARDS_PER_PAGE.'', $params);
			if ($result === false) return false;

			return $result;
		}

		public function CountPages($name, $author, $date, $state)
		{
			$db = $this->db;

			$name_query = ($name != '') ? ' AND `Name` LIKE ?' : '';
			$author_query = (($author != "none") ? ' AND `Author` = ?' : '');
			$date_query = (($date != "none") ? ' AND `LastChange` >= NOW() - INTERVAL ? DAY' : '');
			$state_query = (($state != "none") ? ' AND `State` = ?' : '');

			$params = array();
			if ($name != '') $params[] = '%'.$name.'%';
			if ($author != "none") $params[] = $author;
			if ($date != "none") $params[] = $date;
			if ($state != "none") $params[] = $state;

			$result = $db->Query('SELECT COUNT(`CardID`) as `Count` FROM `concepts` WHERE 1'.$name_query.$author_query.$date_query.$state_query.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			$pages = ceil($data['Count'] / CARDS_PER_PAGE);

			return $pages;
		}

		public function ListAuthors($date)
		{
			$db = $this->db;

			$date_query = (($date != "none") ? ' AND `LastChange` >= NOW() - INTERVAL ? DAY' : '');

			$params = array();
			if ($date != "none") $params[] = $date;

			$result = $db->Query('SELECT DISTINCT `Author` FROM `concepts` WHERE 1'.$date_query.' ORDER BY `Author` ASC', $params);
			if ($result === false) return false;

			$authors = array();

			foreach ($result as $data)
				$authors[] = $data['Author'];

			return $authors;
		}

		public function Exists($concept_id)
		{
			$db = $this->db;

			$result = $db->Query('SELECT 1 FROM `concepts` WHERE `CardID` = ?', array($concept_id));
			if ($result === false or count($result) == 0) return false;

			return true;
		}

		public function NewConcepts($time)
		{
			$db = $this->db;

			$result = $db->Query('SELECT 1 FROM `concepts` WHERE `LastChange` > ?', array($time));
			if ($result === false or count($result) == 0) return false;

			return true;
		}

		public function CheckInputs(array $data)
		{
			$error = '';
			// check mandatory inputs (Name is mandatory and also either Keywords or Effect must be specified as well)
			if ((trim($data['name']) == "") OR ((trim($data['keywords']) == "") AND (trim($data['effect']) == ""))) $error = "Fill in the mandatory inputs";

			// check card class
			if (!in_array($data['class'], array('Common', 'Uncommon', 'Rare'))) $error = "Invalid card class";

			// check card cost - numeric inputs
			if ((!is_numeric($data['bricks'])) OR (!is_numeric($data['gems'])) OR (!is_numeric($data['recruits']))) $error = "Invalid numeric input";

			// check card cost -  negative values are not allowed
			if (($data['bricks'] < 0) OR ($data['gems'] < 0) OR ($data['recruits'] < 0)) $error = "Card cost cannot be negative";

			// check card cost - value validity (cannot have 3 different values)
			if (($data['bricks'] > 0) AND ($data['gems'] > 0) AND ($data['recruits'] > 0) AND !(($data['bricks'] == $data['gems']) AND ($data['gems'] == $data['recruits']))) $error = "Invalid cost input";

			// check state
			if (isset($data['state']) and !in_array($data['state'], array('waiting','rejected','interesting','implemented'))) $error = "Invalid concept state";

			// check input length
			if (strlen($data['effect']) > EFFECT_LENGTH) $error = "Card effect text is too long";
			if (strlen($data['note']) > MESSAGE_LENGTH) $error = "Note text is too long";

			return $error;
		}

		public function AssignThread($concept_id, $thread_id)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `ThreadID` = ? WHERE `CardID` = ?', array($thread_id, $concept_id));
			if ($result === false) return false;

			return true;
		}

		public function RemoveThread($concept_id)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `ThreadID` = 0 WHERE `CardID` = ?', array($concept_id));
			if ($result === false) return false;

			return true;
		}

		public function FindConcept($thread_id)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `CardID` FROM `concepts` WHERE `ThreadID` = ?', array($thread_id));
			if ($result === false or count($result) == 0) return 0;

			$data = $result[0];

			return $data['CardID'];
		}
	}


	class CConcept
	{
		private $Concepts;

		public $ID;
		public $Name;
		public $Class;
		public $Bricks;
		public $Gems;
		public $Recruits;
		public $Effect;
		public $Keywords;
		public $Picture;
		public $Note;
		public $State;
		public $Author;
		public $LastChange;
		public $ThreadID;

		public function __construct($cardid, CConcepts &$Concepts)
		{
			$this->Concepts = &$Concepts;

			$db = $this->Concepts->getDB();
			$result = $db->Query('SELECT `Name`, `Class`, `Bricks`, `Gems`, `Recruits`, `Effect`, `Keywords`, `Picture`, `Note`, `State`, `Author`, `LastChange`, `ThreadID` FROM `concepts` WHERE `CardID` = ?', array($cardid));
			if ($result === false or count($result) == 0)
        $concept_data = array('id'=>-1, 'name'=>'Invalid Concept', 'class'=>'None', 'bricks'=>0, 'gems'=>0, 'recruits'=>0, 'keywords'=>'', 'effect'=>'', 'picture'=>'', 'note'=>'', 'state'=>'', 'author'=>'', 'lastchange'=>'', 'threadid'=>0);
			else
			{
				$data = $result[0];
				$concept_data = array('id'=>$cardid, 'name'=>$data['Name'], 'class'=>$data['Class'], 'bricks'=>$data['Bricks'], 'gems'=>$data['Gems'], 'recruits'=>$data['Recruits'], 'keywords'=>$data['Keywords'], 'effect'=>$data['Effect'], 'picture'=>$data['Picture'], 'note'=>$data['Note'], 'state'=>$data['State'], 'author'=>$data['Author'], 'lastchange'=>$data['LastChange'], 'threadid'=>$data['ThreadID']);
			}

			// initialize self
			$this->SetData($concept_data);
		}

		public function __destruct()
		{
			$this->Concepts = false;
		}

		public function EditConcept(array $data) // standard edit (normal user)
		{
			return $this->Concepts->EditConcept($this->ID, $data);
		}

		public function EditConceptSpecial(array $data) // special edit (admin)
		{
			return $this->Concepts->EditConceptSpecial($this->ID, $data);
		}

		public function EditPicture($picture)
		{
			return $this->Concepts->EditPicture($this->ID, $picture);
		}

		public function ResetPicture()
		{
			return $this->Concepts->ResetPicture($this->ID);
		}

		public function DeleteConcept()
		{
			return $this->Concepts->DeleteConcept($this->ID);
		}

		public function AssignThread($thread_id)
		{
			return $this->Concepts->AssignThread($this->ID, $thread_id);
		}

		public function GetData()
		{
			$data['id']         = $this->ID;
			$data['name']       = $this->Name;
			$data['class']      = $this->Class;
			$data['bricks']     = $this->Bricks;
			$data['gems']       = $this->Gems;
			$data['recruits']   = $this->Recruits;
			$data['keywords']   = $this->Keywords;
			$data['effect']     = $this->Effect;
			$data['picture']    = $this->Picture;
			$data['note']       = $this->Note;
			$data['state']      = $this->State;
			$data['author']     = $this->Author;
			$data['lastchange'] = $this->LastChange;
			$data['threadid']   = $this->ThreadID;

			return $data;
		}

		private function SetData($data)
		{
			$this->ID         = $data['id'];
			$this->Name       = $data['name'];
			$this->Class      = $data['class'];
			$this->Bricks     = $data['bricks'];
			$this->Gems       = $data['gems'];
			$this->Recruits   = $data['recruits'];
			$this->Keywords   = $data['keywords'];
			$this->Effect     = $data['effect'];
			$this->Picture    = $data['picture'];
			$this->Note       = $data['note'];
			$this->State      = $data['state'];
			$this->Author     = $data['author'];
			$this->LastChange = $data['lastchange'];
			$this->ThreadID   = $data['threadid'];
		}
	}
?>
