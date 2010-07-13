<?php
/*
	CNovels - novels
*/
?>
<?php
	class CNovels
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
				$this->db = new SimpleXMLElement('novels.xml', 0, TRUE);
				$this->db->registerXPathNamespace('am', 'http://arcomage.netvor.sk');
			}

			return $this->db;
		}

		public function listNovels()
		{
			$db = $this->getDB();

			$result = $db->xpath("/am:novels/am:book");

			if( $result === false ) return array();

			$list = array();
			foreach ($result as $book) $list[] = $book->attributes()->name;

			return $list;
		}

		public function listChapters($novel)
		{
			$db = $this->getDB();

			$result = $db->xpath('/am:novels/am:book[@name = "'.$novel.'"]/am:chapter');

			if( $result === false ) return array();

			$list = array();
			foreach ($result as $chapter) $list[] = $chapter->attributes()->name;

			return $list;
		}

		public function listParts($novel, $chapter)
		{
			$db = $this->getDB();

			$result = $db->xpath('/am:novels/am:book[@name = "'.$novel.'"]/am:chapter[@name = "'.$chapter.'"]/am:part');

			if( $result === false ) return array();

			$list = array();
			foreach ($result as $part) $list[] = $part->attributes()->name;

			return $list;
		}

		public function listPages($novel, $chapter, $part)
		{
			$db = $this->getDB();

			$result = $db->xpath('/am:novels/am:book[@name = "'.$novel.'"]/am:chapter[@name = "'.$chapter.'"]/am:part[@name = "'.$part.'"]/am:page');

			if( $result === false ) return 0;

			return count($result);
		}

		public function getPage($novel, $chapter, $part, $page)
		{
			$db = $this->getDB();

			$result = $db->xpath('/am:novels/am:book[@name = "'.$novel.'"]/am:chapter[@name = "'.$chapter.'"]/am:part[@name = "'.$part.'"]/am:page[position() = "'.$page.'"]');

			if( $result === false ) return '';

			$result = $result[0];

			return (string)$result;
		}
	}
?>
