<?php
/*
	CForum - MArcomage discussion forum
*/
?>
<?php
	class CForum
	{
		private $db;
		public $Threads;
		
		public function __construct(CDatabase &$database)
		{
			$this->db = &$database;
			$this->Threads = new CThread($database);
		}
		
		public function GetDB()
		{
			return $this->db;
		}
		
		public function ListSections()
		{	
			$db = $this->db;
			
			// return section list with thread count, ordered by sectionID (alphabetical order is not suited for our needs)
			$result = $db->Query('SELECT `forum_sections`.`SectionID`, `SectionName`, `Description`, IFNULL(`count`, 0) as `count` FROM `forum_sections` LEFT OUTER JOIN (SELECT `SectionID`, COUNT(`ThreadID`) as `count` FROM `forum_threads` WHERE `Deleted` = "no" GROUP BY `SectionID`) as `threads` USING (`SectionID`) ORDER BY `SectionID`');
			if (!$result) return false;
			
			$sections = array();
			while( $data = $result->Next() )
				$sections[$data['SectionID']] = $data;
			
			return $sections;
		}
		
		public function ListTargetSections($current_section)
		{	// used to generate list of all section except the current section
			$db = $this->db;
			
			$result = $db->Query('SELECT `SectionID`, `SectionName` FROM `forum_sections` WHERE `SectionID` != "'.$current_section.'" ORDER BY `SectionID`');
			if (!$result) return false;
			
			$sections = array();
			while( $data = $result->Next() )
				$sections[$data['SectionID']] = $data;
						
			return $sections;
		}
		
		public function GetSection($section_id)
		{	
			$db = $this->db;
			
			$result = $db->Query('SELECT `SectionID`, `SectionName`, `Description` FROM `forum_sections` WHERE `SectionID` = "'.$section_id.'"');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$section = array();
			$section = $result->Next();
			
			return $section;
		}
		
		public function IsSomethingNew($time)
		{	
			$db = $this->db;
			
			$result = $db->Query('SELECT 1 FROM `forum_posts` WHERE `Created` > "'.$time.'"');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			return true;
		}
	}
?>
