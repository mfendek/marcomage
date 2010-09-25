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
		public $Description;
		public $Code;

		public function __construct($name, CKeywords &$Keywords)
		{
			$this->Keywords = &$Keywords;

			$db = $this->Keywords->getDB();
			$result = $db->xpath('/am:keywords/am:keyword[am:name="'.$name.'"]');

			if( $result === false || count($result) == 0 )
                $arr = array ('Invalid keyword', '', '');
			else
			{
				$data = &$result[0];
				$arr = array ((string)$data->name, (string)$data->description, (string)$data->code);
			}

			// initialize self
			list($this->Name, $this->Description, $this->Code) = $arr;
		}

		public function __destruct()
		{
			$this->Name = '';
			$this->Description = '';
			$this->Code = '';
			$this->Keywords = false;
		}
	}
?>
