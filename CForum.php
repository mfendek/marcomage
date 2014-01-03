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
		
		public function getDB()
		{
			return $this->db;
		}
		
		public function listSections()
		{
			$db = $this->db;

			// get section list with thread count, ordered by custom order (alphabetical order is not suited for our needs)
			$result = $db->query('SELECT `forum_sections`.`SectionID`, `SectionName`, `Description`, IFNULL(`count`, 0) as `count` FROM `forum_sections` LEFT OUTER JOIN (SELECT `SectionID`, COUNT(`ThreadID`) as `count` FROM `forum_threads` WHERE `Deleted` = FALSE GROUP BY `SectionID`) as `threads` USING (`SectionID`) ORDER BY `SectionOrder` ASC');
			if ($result === false) return false;

			$sections = $params = array();
			foreach( $result as $data )
			{
				$section_id = $data['SectionID'];
				$sections[$section_id] = $data;
				$params[$section_id] = array($section_id);
			}

			// get threads list for current section
			$result = $db->multiquery('SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost`, CEIL(`PostCount` / '.POSTS_PER_PAGE.') as `LastPage`, `SectionID` FROM `forum_threads` WHERE `SectionID` = ? AND `Deleted` = FALSE ORDER BY `LastPost` DESC, `Created` DESC LIMIT '.NUM_THREADS.'', $params);
			if ($result === false) return false;

			foreach ($result as $section_id => $data)
				$sections[$section_id]['threadlist'] = $data;

			return $sections;
		}

		public function listTargetSections($current_section = 0)
		{	// used to generate list of all section except the current section
			$db = $this->db;
			
			$result = $db->query('SELECT `SectionID`, `SectionName` FROM `forum_sections` WHERE `SectionID` != ? ORDER BY `SectionOrder`', array($current_section));
			if ($result === false) return false;

			$sections = array();
			foreach( $result as $data )
				$sections[$data['SectionID']] = $data;

			return $sections;
		}

		public function getSection($section_id)
		{	
			$db = $this->db;
			
			$result = $db->query('SELECT `SectionID`, `SectionName`, `Description` FROM `forum_sections` WHERE `SectionID` = ?', array($section_id));
			if ($result === false or count($result) == 0) return false;
			
			$section = $result[0];
			
			return $section;
		}
		
		public function newPosts($time)
		{	
			$db = $this->db;
			
			$result = $db->query('SELECT 1 FROM `forum_posts` WHERE `Created` > ? AND `Deleted` = FALSE LIMIT 1', array($time));
			if ($result === false or count($result) == 0) return false;
			
			return true;
		}
		
		public function search($phrase, $target = 'all', $section = 'any')
		{
			$db = $this->db;

			$params = array();
			$post_section_q = $thread_section_q = $post_q = $thread_q = '';
			if ($target == 'posts' or $target == 'all')
			{
				if ($section != 'any') $post_section_q = ' AND `SectionID` = ?';
				// search post text content
				$post_q = 'SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost` FROM (SELECT DISTINCT `ThreadID` FROM `forum_posts` WHERE `Deleted` = FALSE AND `Content` LIKE ?) as `posts` INNER JOIN (SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost` FROM `forum_threads` WHERE `Deleted` = FALSE'.$post_section_q.') as `threads` USING(`ThreadID`)';
				$params[] = '%'.$phrase.'%';
				if ($section != 'any') $params[] = $section;
			}
			if ($target == 'threads' or $target == 'all')
			{
				if ($section != 'any') $thread_section_q = ' AND `SectionID` = ?';
				// search thread title
				$thread_q = 'SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost` FROM `forum_threads` WHERE `Deleted` = FALSE AND `Title` LIKE ?'.$thread_section_q.'';
				$params[] = '%'.$phrase.'%';
				if ($section != 'any') $params[] = $section;
			}
			
			// merge results
			$query = $post_q.(($target == 'all') ? ' UNION DISTINCT ' : '').$thread_q.' ORDER BY `LastPost` DESC';
			
			$result = $db->query($query, $params);
			if ($result === false) return false;

			return $result;
		}
	}
?>
