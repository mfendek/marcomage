<?php
/*
	CNovels - novels
*/
?>
<?php
	class CNovels
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
		
		public function GetNovelsList()
		{
			$db = $this->db;
			$result = $db->Query('SELECT DISTINCT `Novelname` FROM `novels` ORDER BY `Novelname` ASC');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Novelname'];
			return $names;
		}
		
		public function GetChaptersList($novel)
		{
			$db = $this->db;
			$result = $db->Query('SELECT DISTINCT `Chapter` FROM `novels` WHERE `Novelname` = "'.$novel.'" ORDER BY `Chapter` ASC');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Chapter'];
			return $names;
		}
		
		public function ListPages($novel, $chapter) // get page list, pages with part headings are decorated with them
		{
			$part_query = 'SELECT `Page`, SUBSTRING(`Content`, LOCATE("<h4>Part",`Content`) + 4, LOCATE("</h4>",`Content`) - (LOCATE("<h4>Part",`Content`) + 4)) as `Part` FROM `novels` WHERE `Novelname` = "'.$novel.'" AND `Chapter` = "'.$chapter.'" AND `Content` LIKE "%<h4>Part%</h4>%"';
			
			$nonpart_query = 'SELECT `Page`, "" as `Part` FROM `novels` WHERE `Novelname` = "'.$novel.'" AND `Chapter` = "'.$chapter.'" AND `Content` NOT LIKE "%<h4>Part%</h4>%"';
			
			$db = $this->db;
			$result = $db->Query('SELECT `Page`, `Part` FROM ('.$part_query.' UNION '.$nonpart_query.') as `temp` ORDER BY `Page` ASC');
			if (!$result) return false;
			
			$pages = array();
			while( $data = $result->Next() )
				$pages[$data['Page']] = $data['Page'].(($data['Part'] != "") ? " ".$data['Part']: "");
			
			return $pages;
		}
		
		public function GetPageContent($novel, $chapter, $page)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Content` FROM `novels` WHERE `Novelname` = "'.$novel.'" AND `Chapter` = "'.$chapter.'" AND `Page` = "'.$page.'"');
			if (!$result) return false;
			
			$data = $result->Next();			
			$content = $data['Content'];
			
			return $content;
		}
		
	}

?>
