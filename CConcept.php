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

			$result = $db->Query('INSERT INTO `concepts` (`Name`, `Class`, `Bricks`, `Gems`, `Recruits`, `Effect`, `Keywords`, `Note`, `Owner`) VALUES ("'.$db->Escape($data['name']).'", "'.$db->Escape($data['class']).'", "'.$db->Escape($data['bricks']).'", "'.$db->Escape($data['gems']).'", "'.$data['recruits'].'", "'.$db->Escape($data['effect']).'", "'.$db->Escape($data['keywords']).'", "'.$db->Escape($data['note']).'", "'.$db->Escape($data['owner']).'")');

			if (!$result) return false;

			return $db->LastID();
		}

		public function EditConcept($concept_id, array $data) // standard edit (normal user)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `Name` = "'.$db->Escape($data['name']).'", `Class` = "'.$db->Escape($data['class']).'", `Bricks` = "'.$db->Escape($data['bricks']).'", `Gems` = "'.$db->Escape($data['gems']).'", `Recruits` = "'.$data['recruits'].'", `Effect` = "'.$db->Escape($data['effect']).'", `Keywords` = "'.$db->Escape($data['keywords']).'", `Note` = "'.$db->Escape($data['note']).'", `LastChange` = NOW() WHERE `CardID` = "'.$concept_id.'"');

			if (!$result) return false;

			return true;
		}

		public function EditConceptSpecial($concept_id, array $data) // special edit (admin)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `Name` = "'.$db->Escape($data['name']).'", `Class` = "'.$db->Escape($data['class']).'", `Bricks` = "'.$db->Escape($data['bricks']).'", `Gems` = "'.$db->Escape($data['gems']).'", `Recruits` = "'.$data['recruits'].'", `Effect` = "'.$db->Escape($data['effect']).'", `Keywords` = "'.$db->Escape($data['keywords']).'", `Note` = "'.$db->Escape($data['note']).'", `State` = "'.$db->Escape($data['state']).'" WHERE `CardID` = "'.$concept_id.'"');

			if (!$result) return false;

			return true;
		}

		public function EditPicture($concept_id, $picture)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `Picture` = "'.$db->Escape($picture).'", `LastChange` = NOW()  WHERE `CardID` = "'.$concept_id.'"');

			if (!$result) return false;

			return true;
		}

		public function ResetPicture($concept_id)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `concepts` SET `Picture` = "blank.jpg", `LastChange` = NOW()  WHERE `CardID` = "'.$concept_id.'"');

			if (!$result) return false;

			return true;
		}

		public function DeleteConcept($concept_id)
		{
			$db = $this->db;

			$result = $db->Query('DELETE FROM `concepts` WHERE `CardID` = "'.$concept_id.'"');

			if (!$result) return false;

			return true;
		}

		public function GetList($owner, $date, $state, $condition, $order, $page)
		{
			$db = $this->db;

			$owner_query = (($owner != "none") ? ' AND `Owner` = "'.$db->Escape($owner).'"' : '');
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`LastChange`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			$state_query = (($state != "none") ? ' AND `State` = "'.$db->Escape($state).'"' : '');

			$result = $db->Query('SELECT `CardID` as `id`, `Name` as `name`, `Class` as `class`, `Bricks` as `bricks`, `Gems` as `gems`, `Recruits` as `recruits`, `Effect` as `effect`, `Keywords` as `keywords`, `Picture` as `picture`, `Note` as `note`, `State` as `state`, `Owner` as `owner`, `LastChange` as `lastchange` FROM `concepts` WHERE 1'.$owner_query.$date_query.$state_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(CARDS_PER_PAGE * $page).' , '.CARDS_PER_PAGE.'');

			if (!$result) return false;

			$cards = array();

			while ($data = $result->Next())
				$cards[] = $data;

			return $cards;
		}

		public function CountPages($owner, $date, $state)
		{
			$db = $this->db;

			$owner_query = (($owner != "none") ? ' AND `Owner` = "'.$db->Escape($owner).'"' : '');
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`LastChange`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			$state_query = (($state != "none") ? ' AND `State` = "'.$db->Escape($state).'"' : '');

			$result = $db->Query('SELECT COUNT(`CardID`) as `Count` FROM `concepts` WHERE 1'.$owner_query.$date_query.$state_query.'');

			if (!$result) return false;

			$data = $result->Next();

			$pages = ceil($data['Count'] / CARDS_PER_PAGE);

			return $pages;
		}

		public function ListOwners($date)
		{
			$db = $this->db;

			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`LastChange`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');

			$result = $db->Query('SELECT DISTINCT `Owner` FROM `concepts` WHERE 1'.$date_query.'');

			if (!$result) return false;

			$owners = array();

			while ($data = $result->Next())
				$owners[] = $data['Owner'];

			return $owners;
		}

		public function Exists($cardid)
		{
			$db = $this->db;

			$result = $db->Query('SELECT 1 FROM `concepts` WHERE `CardID` = '.$cardid.'');

			if (!$result) return false;
			if (!$result->Rows()) return false;

			return true;
		}

		public function NewConcepts($time)
		{
			$db = $this->db;

			$result = $db->Query('SELECT 1 FROM `concepts` WHERE `LastChange` > "'.$time.'"');

			if (!$result) return false;
			if (!$result->Rows()) return false;

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

			// check input length
			if (strlen($data['effect']) > EFFECT_LENGTH) $error = "Card effect text is too long";
			if (strlen($data['note']) > MESSAGE_LENGTH) $error = "Note text is too long";

			return $error;
		}

	}


	class CConcept
	{
		private $CardID;
		private $Concepts;
		public $ConceptData;

		public function __construct($cardid, CConcepts &$Concepts)
		{
			$this->CardID = (int)$cardid; 

			$this->Concepts = &$Concepts;
			$this->ConceptData = new CConceptData;

			$cd = &$this->ConceptData;

			$db = $this->Concepts->getDB();
			$result = $db->Query('SELECT `Name`, `Class`, `Bricks`, `Gems`, `Recruits`, `Effect`, `Keywords`, `Picture`, `Note`, `State`, `Owner`, `LastChange` FROM `concepts` WHERE `CardID` = '.$this->CardID.'');

			if( !$result OR !$result->Rows() ) return false;

			$data = $result->Next();
			$arr = array ($data['Name'], $data['Class'], $data['Bricks'], $data['Gems'], $data['Recruits'], $data['Effect'], $data['Keywords'], $data['Picture'], $data['Note'], $data['State'], $data['Owner'], $data['LastChange']);

			// initialize self
			list($cd->Name, $cd->Class, $cd->Bricks, $cd->Gems, $cd->Recruits, $cd->Effect, $cd->Keywords, $cd->Picture, $cd->Note, $cd->State, $cd->Owner, $cd->LastChange) = $arr;
		}

		public function __destruct()
		{
			$this->CardID = -1;
			$this->Concepts = false;
			$this->ConceptData = false;
		}

		public function EditConcept(array $data) // standard edit (normal user)
		{
			return $this->Concepts->EditConcept($this->CardID, $data);
		}

		public function EditConceptSpecial(array $data) // special edit (admin)
		{
			return $this->Concepts->EditConceptSpecial($this->CardID, $data);
		}

		public function EditPicture($picture)
		{
			return $this->Concepts->EditPicture($this->CardID, $picture);
		}

		public function ResetPicture()
		{
			return $this->Concepts->ResetPicture($this->CardID);
		}

		public function DeleteConcept()
		{
			return $this->Concepts->DeleteConcept($this->CardID);
		}
	}


	class CConceptData
	{
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
		public $Owner;
		public $LastChange;
	}
?>
