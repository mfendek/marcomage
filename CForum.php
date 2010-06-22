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
			
			// return section list with thread count, ordered by custom order (alphabetical order is not suited for our needs)
			$result = $db->Query('SELECT `forum_sections`.`SectionID`, `SectionName`, `Description`, IFNULL(`count`, 0) as `count` FROM `forum_sections` LEFT OUTER JOIN (SELECT `SectionID`, COUNT(`ThreadID`) as `count` FROM `forum_threads` WHERE `Deleted` = FALSE GROUP BY `SectionID`) as `threads` USING (`SectionID`) ORDER BY `SectionOrder` ASC');
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
			
			$result = $db->Query('SELECT 1 FROM `forum_posts` WHERE `Deleted` = FALSE AND `Created` > "'.$time.'"');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			return true;
		}
		
		public function Search($phrase, $target = 'all', $section = 'any')
		{
			$db = $this->db;
			
			$section_q = ($section != 'any') ? ' AND `SectionID` = "'.$db->Escape($section).'"' : '';
			
			// search post text content
			$post_q = (($target == 'posts') OR ($target == 'all')) ? 'SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost` FROM (SELECT DISTINCT `ThreadID` FROM `forum_posts` WHERE `Deleted` = FALSE AND `Content` LIKE "%'.$db->Escape($phrase).'%") as `posts` INNER JOIN (SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost` FROM `forum_threads` WHERE `Deleted` = FALSE'.$section_q.') as `threads` USING(`ThreadID`)' : '';
			
			// search thread title
			$thread_q = (($target == 'threads') OR ($target == 'all')) ? 'SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost` FROM `forum_threads` WHERE `Deleted` = FALSE AND `Title` LIKE "%'.$db->Escape($phrase).'%"'.$section_q.'' : '';
			
			// merge results
			$query = $post_q.(($target == 'all') ? ' UNION DISTINCT ' : '').$thread_q.' ORDER BY `LastPost` DESC';
			
			$result = $db->Query($query);
			
			if (!$result) return false;
			
			$threads = array();
			while( $data = $result->Next() )
				$threads[] = $data;
			
			return $threads;
		}
	}
?>
