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
		
		public function CreateThread($title, $author, $priority, $section)
		{	
			$db = $this->db;
			
			$result = $db->Query('INSERT INTO `forum_threads` (`Title`, `Author`, `Priority`, `Section`, `Created`) VALUES ("'.$db->Escape($title).'", "'.$db->Escape($author).'", "'.$priority.'", "'.$section.'", NOW())');
			if (!$result) return false;
			
			return $db->LastID();
		}
		
		public function DeleteThread($thread_id)
		{
			$db = $this->db;
			
			// delete all posts that are inside this thread
			$result = $db->Query('UPDATE `forum_posts` SET `Deleted` = "yes" WHERE `Thread` = "'.$thread_id.'"');
			
			if (!$result) return false;
			
			// delete thread
			$result = $db->Query('UPDATE `forum_threads` SET `Deleted` = "yes" WHERE `ThreadID` = "'.$thread_id.'"');
			
			if (!$result) return false;
			
			return true;
		}
		
		public function GetThread($thread_id)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Section`, `Created` FROM `forum_threads` WHERE `ThreadID` = "'.$thread_id.'" AND `Deleted` = "no"');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data;
		}
		
		public function ThreadExists($title)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT `ThreadID` FROM `forum_threads` WHERE `Title` = "'.$title.'" AND `Deleted` = "no"');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			$thread_id = $data['ThreadID'];
			
			return $thread_id;
		}
		
		public function EditThread($thread_id, $title, $priority)
		{	
			$db = $this->db;
									
			$result = $db->Query('UPDATE `forum_threads` SET `Title` = "'.$db->Escape($title).'", `Priority` = "'.$priority.'" WHERE `ThreadID` = "'.$thread_id.'"');
			if (!$result) return false;
									
			return true;
		}
				
		public function LockThread($thread_id)
		{	
			$db = $this->db;
									
			$result = $db->Query('UPDATE `forum_threads` SET `Locked` = "yes" WHERE `ThreadID` = "'.$thread_id.'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function UnlockThread($thread_id)
		{	
			$db = $this->db;
									
			$result = $db->Query('UPDATE `forum_threads` SET `Locked` = "no" WHERE `ThreadID` = "'.$thread_id.'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function IsLocked($thread_id)
		{	
			$db = $this->db;
			
			$result = $db->Query('SELECT `Locked` FROM `forum_threads` WHERE `ThreadID` = "'.$thread_id.'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return ($data['Locked'] == "yes");
		}
		
		public function PostCount($thread_id)
		{	
			$db = $this->db;
									
			$result = $db->Query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `Thread` = "'.$thread_id.'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data['Count'];
		}
		
		private function GenerateQuery($type, $section)
		{	// support function for ListThreads
			if ($type == "sticky")
			{
				$sign = "";
				$flag = 0;
			}
			elseif ($type == "nonsticky")
			{
				$sign = "!";
				$flag = 1;
			}
			
			return 'SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Created`, `PostAuthor`, `last_post`, COALESCE(`post_count`, 0) as `post_count`, `flag` FROM (SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Created`, '.$flag.' as `flag` FROM `forum_threads` WHERE `Section` = "'.$section.'" AND `Deleted` = "no" AND `Priority` '.$sign.'= "sticky") as `threads` LEFT OUTER JOIN (SELECT `PostAuthor`, `posts1`.`Thread`, `last_post`, `post_count` FROM (SELECT `Author` as `PostAuthor`, `Thread`, `Created` FROM `forum_posts` WHERE `Deleted` = "no") as `posts1` INNER JOIN (SELECT `Thread`, MAX(`Created`) as `last_post`, COUNT(`PostID`) as `post_count` FROM `forum_posts` WHERE `Deleted` = "no" GROUP BY `Thread`) as `posts2` ON `posts1`.`Thread` = `posts2`.`Thread` AND `posts1`.`Created` = `posts2`.`last_post`) as `posts` ON `threads`.`ThreadID` = `posts`.`Thread`';
		}
				
		public function ListThreads($section, $page, $limit)
		{			
			$db = $this->db;
			
			// get the thread list with last post date, sticky part
			$sticky_query = $this->GenerateQuery("sticky", $section);
			
			// get the thread list with last post date, non-sticky part
			$nonsticky_query = $this->GenerateQuery("nonsticky", $section);
			
			// limit option "" - no limit, N - limit N threads to output			
			$limit_query = (($limit == "") ? (THREADS_PER_PAGE * $page).' , '.THREADS_PER_PAGE.'' : '0 , '.$limit);
			
			// combine queries into one query
			$result = $db->Query(''.$sticky_query.' UNION '.$nonsticky_query.' ORDER BY `Flag` ASC, `last_post` DESC, `Created` DESC LIMIT '.$limit_query.'');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$threads = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$threads[$i] = $result->Next();
			
			return $threads;
		}
		
		public function ListThreadsMain($section)
		{	// lists threads in one specific section, ignoring sticky flag. Used in Forum main page.
			$db = $this->db;
						
			$result = $db->Query('SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Created`, `PostAuthor`, `last_post`, COALESCE(`post_count`, 0) as `post_count` FROM (SELECT `ThreadID`, `Title`, `Author`, `Priority`, `Locked`, `Created` FROM `forum_threads` WHERE `Section` = "'.$section.'" AND `Deleted` = "no") as `threads` LEFT OUTER JOIN (SELECT `PostAuthor`, `posts1`.`Thread`, `last_post`, `post_count` FROM (SELECT `Author` as `PostAuthor`, `Thread`, `Created` FROM `forum_posts` WHERE `Deleted` = "no") as `posts1` INNER JOIN (SELECT `Thread`, MAX(`Created`) as `last_post`, COUNT(`PostID`) as `post_count` FROM `forum_posts` WHERE `Deleted` = "no" GROUP BY `Thread`) as `posts2` ON `posts1`.`Thread` = `posts2`.`Thread` AND `posts1`.`Created` = `posts2`.`last_post`) as `posts` ON `threads`.`ThreadID` = `posts`.`Thread` ORDER BY `last_post` DESC, `Created` DESC LIMIT 0 , '.NUM_THREADS.'');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$threads = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$threads[$i] = $result->Next();
			
			return $threads;
		}
		
		public function ListTargetThreads($current_thread)
		{	// used to generate all thread names except the current one
			$db = $this->db;
								
			$result = $db->Query('SELECT `ThreadID`, `Title` FROM `forum_threads` WHERE `ThreadID` != "'.$current_thread.'" AND `Deleted` = "no" ORDER BY `Title` ASC');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$threads = array();
			while( $data = $result->Next() )
			{
				$threads[$data['ThreadID']] = $data['Title'];				
			}
			
			return $threads;
		}
				
		public function MoveThread($thread_id, $new_section)
		{	
			$db = $this->db;
			
			$result = $db->Query('UPDATE `forum_threads` SET `Section` = "'.$new_section.'" WHERE `ThreadID` = "'.$thread_id.'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function CountPages($section)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT COUNT(`ThreadID`) as `Count` FROM `forum_threads` WHERE `Section` = "'.$section.'" AND `Deleted` = "no"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			$pages = ceil($data['Count'] / THREADS_PER_PAGE);
			
			return $pages;
		}
	}
?>
