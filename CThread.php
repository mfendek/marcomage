<?php
/*
	CThread - Thread database
*/
?>
<?php
	class CThread
	{
		private $db;
		public $Posts;
		
		public function __construct(CDatabase &$database)
		{
			$this->db = &$database;
			$this->Posts = new CPost($database);
		}
		
		public function GetDB()
		{
			return $this->db;
		}
		
		public function CreateThread($title, $author, $priority, $section, $card_id = 0)
		{	
			$db = $this->db;
			
			$result = $db->Query('INSERT INTO `forum_threads` (`Title`, `Author`, `Priority`, `SectionID`, `Created`, `CardID`) VALUES ("'.$db->Escape($title).'", "'.$db->Escape($author).'", "'.$db->Escape($priority).'", "'.$db->Escape($section).'", NOW(), "'.$db->Escape($card_id).'")');
			if (!$result) return false;
			
			return $db->LastID();
		}
		
		public function DeleteThread($thread_id)
		{
			$db = $this->db;
			
			// delete all posts that are inside this thread
			$result = $db->Query('UPDATE `forum_posts` SET `Deleted` = TRUE WHERE `ThreadID` = "'.$db->Escape($thread_id).'"');
			
			if (!$result) return false;
			
			// delete thread
			$result = $db->Query('UPDATE `forum_threads` SET `Deleted` = TRUE WHERE `ThreadID` = "'.$db->Escape($thread_id).'"');
			
			if (!$result) return false;
			
			return true;
		}
		
		public function GetThread($thread_id)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `SectionID`, `Created`, `CardID` FROM `forum_threads` WHERE `ThreadID` = "'.$db->Escape($thread_id).'" AND `Deleted` = FALSE');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data;
		}
		
		public function ThreadExists($title)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT `ThreadID` FROM `forum_threads` WHERE `Title` = "'.$db->Escape($title).'" AND `Deleted` = FALSE');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$thread_id = $data['ThreadID'];
			
			return $thread_id;
		}
		
		public function CardThread($card_id) /* find matching thread for specified card */
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT `ThreadID` FROM `forum_threads` WHERE `CardID` = "'.$db->Escape($card_id).'" AND `Deleted` = FALSE');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$thread_id = $data['ThreadID'];
			
			return $thread_id;
		}
		
		public function EditThread($thread_id, $title, $priority)
		{	
			$db = $this->db;
									
			$result = $db->Query('UPDATE `forum_threads` SET `Title` = "'.$db->Escape($title).'", `Priority` = "'.$db->Escape($priority).'" WHERE `ThreadID` = "'.$db->Escape($thread_id).'"');
			if (!$result) return false;
									
			return true;
		}
		
		public function RefreshThread($thread_id)
		{
			$db = $this->db;
			
			// recalculate last post date and author
			$data = $this->GetLastPost($thread_id);
			if (!$data) return false;
			
			// recalculate number of posts
			$count = $this->PostCount($thread_id);
			if ($count === false) return false;
			
			$result = $db->Query('UPDATE `forum_threads` SET `PostCount` = "'.$db->Escape($count).'", `LastAuthor` = "'.$db->Escape($data['Author']).'", `LastPost` = "'.$db->Escape($data['Created']).'" WHERE `ThreadID` = "'.$db->Escape($thread_id).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function GetLastPost($thread_id)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT `Author`, `Created` FROM `forum_posts` WHERE `ThreadID` = "'.$db->Escape($thread_id).'" AND `Deleted` = FALSE AND `Created` = (SELECT MAX(`Created`) FROM `forum_posts` WHERE `ThreadID` = "'.$db->Escape($thread_id).'" AND `Deleted` = FALSE)');
			if (!$result) return false;
			if (!$result->Rows()) return array('Author' => '', 'Created' => '0000-00-00 00:00:00'); // there are no posts in this thread
			
			$data = $result->Next();
			
			return $data;
		}
		
		public function LockThread($thread_id)
		{	
			$db = $this->db;
									
			$result = $db->Query('UPDATE `forum_threads` SET `Locked` = TRUE WHERE `ThreadID` = "'.$db->Escape($thread_id).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function UnlockThread($thread_id)
		{	
			$db = $this->db;
									
			$result = $db->Query('UPDATE `forum_threads` SET `Locked` = FALSE WHERE `ThreadID` = "'.$db->Escape($thread_id).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function IsLocked($thread_id)
		{	
			$db = $this->db;
			
			$result = $db->Query('SELECT 1 FROM `forum_threads` WHERE `ThreadID` = "'.$db->Escape($thread_id).'" AND `Locked` = TRUE');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			return true;
		}
		
		public function PostCount($thread_id)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `ThreadID` = "'.$db->Escape($thread_id).'" AND `Deleted` = FALSE');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data['Count'];
		}
				
		public function ListThreads($section, $page)
		{	// lists threads in one specific section. Used in Section details.
			$db = $this->db;
			
			$result = $db->Query('SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost`, 0 as `flag` FROM `forum_threads` WHERE `SectionID` = "'.$db->Escape($section).'" AND `Deleted` = FALSE AND `Priority` = "sticky" UNION SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost`, 1 as `flag` FROM `forum_threads` WHERE `SectionID` = "'.$db->Escape($section).'" AND `Deleted` = FALSE AND `Priority` != "sticky" ORDER BY `Flag` ASC, `LastPost` DESC, `Created` DESC LIMIT '.(THREADS_PER_PAGE * $db->Escape($page)).' , '.THREADS_PER_PAGE.'');
			
			if (!$result) return false;
			
			$threads = array();
			while( $data = $result->Next() )
				$threads[] = $data;
			
			return $threads;
		}
		
		public function ListThreadsMain($section)
		{	// lists threads in one specific section, ignoring sticky flag. Used in Forum main page.
			$db = $this->db;
			
			$result = $db->Query('SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost` FROM `forum_threads` WHERE `SectionID` = "'.$db->Escape($section).'" AND `Deleted` = FALSE ORDER BY `LastPost` DESC, `Created` DESC LIMIT '.NUM_THREADS.'');
			
			if (!$result) return false;
			
			$threads = array();
			while( $data = $result->Next() )
				$threads[] = $data;
			
			return $threads;
		}
		
		public function ListTargetThreads($current_thread)
		{	// used to generate all thread names except the current one
			$db = $this->db;
								
			$result = $db->Query('SELECT `ThreadID`, `Title` FROM `forum_threads` WHERE `ThreadID` != "'.$db->Escape($current_thread).'" AND `Deleted` = FALSE ORDER BY `Title` ASC');
			
			if (!$result) return false;
			
			$threads = array();
			while( $data = $result->Next() )
				$threads[] = $data;				
			
			return $threads;
		}
				
		public function MoveThread($thread_id, $new_section)
		{	
			$db = $this->db;
			
			$result = $db->Query('UPDATE `forum_threads` SET `SectionID` = "'.$db->Escape($new_section).'" WHERE `ThreadID` = "'.$db->Escape($thread_id).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function CountPages($section)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT COUNT(`ThreadID`) as `Count` FROM `forum_threads` WHERE `SectionID` = "'.$db->Escape($section).'" AND `Deleted` = FALSE');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			$pages = ceil($data['Count'] / THREADS_PER_PAGE);
			
			return $pages;
		}
	}
?>
