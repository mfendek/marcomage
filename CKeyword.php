<?php
/*
	CKeyword - the representation of a keyword
*/
?>
<?php
	class CKeywords
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
				$this->db = new SimpleXMLElement('templates/keywords.xml', 0, TRUE);
				$this->db->registerXPathNamespace('am', 'http://arcomage.netvor.sk');
			}

			return $this->db;
		}

		public function GetKeyword($name)
		{
			return new CKeyword($name, $this);
		}
	}


	class CKeyword
	{
		private $Keywords;
		public $Name;
		public $Basic_gain;
		public $Bonus_gain;
		public $Description;
		public $Code;

		public function __construct($name, CKeywords &$Keywords)
		{
			$this->Keywords = &$Keywords;

			$db = $this->Keywords->getDB();
			$result = $db->xpath('/am:keywords/am:keyword[am:name="'.$name.'"]');

			if( $result === false || count($result) == 0 )
                $arr = array ('Invalid keyword', 0, 0, '', '');
			else
			{
				$data = &$result[0];
				$arr = array ((string)$data->name, (int)$data->basic_gain, (int)$data->bonus_gain, (string)$data->description, (string)$data->code);
			}

			// initialize self
			list($this->Name, $this->Basic_gain, $this->Bonus_gain, $this->Description, $this->Code) = $arr;
		}

		public function __destruct()
		{
			$this->Name = '';
			$this->Basic_gain = 0;
			$this->Bonus_gain = 0;
			$this->Description = '';
			$this->Code = '';
			$this->Keywords = false;
		}

		public function isTokenKeyword()
		{
			return ($this->Basic_gain > 0 or $this->Bonus_gain > 0);
		}
	}
?>
