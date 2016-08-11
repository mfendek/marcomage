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
		
		public function getDB()
		{
			return $this->db;
		}
		
		public function createThread($title, $author, $priority, $section, $card_id = 0)
		{	
			$db = $this->db;
			
			$result = $db->query('INSERT INTO `forum_threads` (`Title`, `Author`, `Priority`, `SectionID`, `Created`, `CardID`) VALUES (?, ?, ?, ?, NOW(), ?)', array($title, $author, $priority, $section, $card_id));
			if ($result === false) return false;

			return $db->lastId();
		}
		
		public function deleteThread($thread_id)
		{
			$db = $this->db;

			$db->txnBegin();

			// delete all posts that are inside this thread
			$result = $db->query('UPDATE `forum_posts` SET `Deleted` = TRUE WHERE `ThreadID` = ?', array($thread_id));
			if ($result === false) { $db->txnRollBack(); return false; }

			// delete thread
			$result = $db->query('UPDATE `forum_threads` SET `Deleted` = TRUE WHERE `ThreadID` = ?', array($thread_id));
			if ($result === false) { $db->txnRollBack(); return false; }

			$db->txnCommit();

			return true;
		}
		
		public function getThread($thread_id)
		{
			$db = $this->db;

			$result = $db->query('SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `SectionID`, `Created`, `CardID` FROM `forum_threads` WHERE `ThreadID` = ? AND `Deleted` = FALSE', array($thread_id));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			return $data;
		}
		
		public function threadExists($title)
		{
			$db = $this->db;

			$result = $db->query('SELECT `ThreadID` FROM `forum_threads` WHERE `Title` = ? AND `Deleted` = FALSE', array($title));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$thread_id = $data['ThreadID'];
			
			return $thread_id;
		}
		
		public function cardThread($card_id)
		{// find matching thread for specified card
			$db = $this->db;

			$result = $db->query('SELECT `ThreadID` FROM `forum_threads` WHERE `CardID` = ? AND `Deleted` = FALSE', array($card_id));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			$thread_id = $data['ThreadID'];
			
			return $thread_id;
		}
		
		public function editThread($thread_id, $title, $priority)
		{
			$db = $this->db;

			$result = $db->query('UPDATE `forum_threads` SET `Title` = ?, `Priority` = ? WHERE `ThreadID` = ?', array($title, $priority, $thread_id));
			if ($result === false) return false;

			return true;
		}
		
		public function refreshThread($thread_id)
		{
			$db = $this->db;
			
			// recalculate last post date and author
			$data = $this->getLastPost($thread_id);
			if (!$data) return false;
			
			// recalculate number of posts
			$count = $this->postCount($thread_id);
			if ($count === false) return false;
			
			$result = $db->query('UPDATE `forum_threads` SET `PostCount` = ?, `LastAuthor` = ?, `LastPost` = ? WHERE `ThreadID` = ?', array($count, $data['Author'], $data['Created'], $thread_id));
			if ($result === false) return false;

			return true;
		}
		
		public function getLastPost($thread_id)
		{
			$db = $this->db;
			
			$result = $db->query('SELECT `Author`, `Created` FROM `forum_posts` WHERE `ThreadID` = ? AND `Deleted` = FALSE AND `Created` = (SELECT MAX(`Created`) FROM `forum_posts` WHERE `ThreadID` = ? AND `Deleted` = FALSE)', array($thread_id, $thread_id));
			if ($result === false) return false;
			if (count($result) == 0) return array('Author' => '', 'Created' => '1970-01-01 00:00:01'); // there are no posts in this thread

			$data = $result[0];

			return $data;
		}
		
		public function lockThread($thread_id)
		{
			$db = $this->db;

			$result = $db->query('UPDATE `forum_threads` SET `Locked` = TRUE WHERE `ThreadID` = ?', array($thread_id));
			if ($result === false) return false;

			return true;
		}
		
		public function unlockThread($thread_id)
		{
			$db = $this->db;

			$result = $db->query('UPDATE `forum_threads` SET `Locked` = FALSE WHERE `ThreadID` = ?', array($thread_id));
			if ($result === false) return false;

			return true;
		}
		
		public function isLocked($thread_id)
		{
			$db = $this->db;
			
			$result = $db->query('SELECT 1 FROM `forum_threads` WHERE `ThreadID` = ? AND `Locked` = TRUE', array($thread_id));
			if ($result === false or count($result) == 0) return false;

			return true;
		}
		
		public function postCount($thread_id)
		{
			$db = $this->db;
			
			$result = $db->query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `ThreadID` = ? AND `Deleted` = FALSE', array($thread_id));
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			
			return $data['Count'];
		}
		
		public function listThreads($section, $page)
		{	// lists threads in one specific section. Used in Section details.
			$db = $this->db;

			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->query('SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost`, CEIL(`PostCount` / '.POSTS_PER_PAGE.') as `LastPage`, 0 as `flag` FROM `forum_threads` WHERE `SectionID` = ? AND `Deleted` = FALSE AND `Priority` = "sticky" UNION SELECT `ThreadID`, `Title`, `Author`, `Priority`, (CASE WHEN `Locked` = TRUE THEN "yes" ELSE "no" END) as `Locked`, `Created`, `PostCount`, `LastAuthor`, `LastPost`, CEIL(`PostCount` / '.POSTS_PER_PAGE.') as `LastPage`, 1 as `flag` FROM `forum_threads` WHERE `SectionID` = ? AND `Deleted` = FALSE AND `Priority` != "sticky" ORDER BY `Flag` ASC, `LastPost` DESC, `Created` DESC LIMIT '.(THREADS_PER_PAGE * $page).' , '.THREADS_PER_PAGE.'', array($section, $section));
			if ($result === false) return false;

			return $result;
		}
		
		public function listTargetThreads($current_thread)
		{	// used to generate all thread names except the current one
			$db = $this->db;

			$result = $db->query('SELECT `ThreadID`, `Title` FROM `forum_threads` WHERE `ThreadID` != ? AND `Deleted` = FALSE ORDER BY `Title` ASC', array($current_thread));
			if ($result === false) return false;

			return $result;
		}
		
		public function moveThread($thread_id, $new_section)
		{
			$db = $this->db;
			
			$result = $db->query('UPDATE `forum_threads` SET `SectionID` = ? WHERE `ThreadID` = ?', array($new_section, $thread_id));
			if ($result === false) return false;

			return true;
		}
		
		public function countPages($section)
		{
			$db = $this->db;

			$result = $db->query('SELECT COUNT(`ThreadID`) as `Count` FROM `forum_threads` WHERE `SectionID` = ? AND `Deleted` = FALSE', array($section));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];

			$pages = ceil($data['Count'] / THREADS_PER_PAGE);

			return $pages;
		}
	}
?>
