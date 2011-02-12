<?php
/*
	CPost - Post database
*/
?>
<?php
	class CPost
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
		
		public function CreatePost($thread_id, $author, $content)
		{	
			$db = $this->db;
			
			// verify if thread exists and isn't locked
			$result = $db->Query('SELECT 1 FROM `forum_threads` WHERE `ThreadID` = ? AND `Locked` = FALSE', array($thread_id));
			if ($result === false or count($result) == 0) return false;
			
			$result = $db->Query('INSERT INTO `forum_posts` (`Author`, `Content`, `ThreadID`, `Created`) VALUES (?, ?, ?, NOW())', array($author, $content, $thread_id));
			if ($result === false) return false;

			return true;
		}
		
		public function DeletePost($post_id)
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `forum_posts` SET `Deleted` = TRUE WHERE `PostID` = ?', array($post_id));
			if ($result === false) return false;
			
			return true;
		}
		
		/* TODO - in case of implementation prepare one UPDATE statement and run it multiple times
		public function MassDeletePost(array $deleted_posts)
		{
			$db = $this->db;
			
			$first = true;
			$post_query = "";
			
			foreach($deleted_posts as $post_id)
			{
				if ($first)
				{
					$post_query.= '`PostID` = "'.$db->Escape($post_id).'"';
					$first = false;
				}
				else $post_query.= ' OR `PostID` = "'.$db->Escape($post_id).'"';
			}
			
			$result = $db->Query('UPDATE `forum_posts` SET `Deleted` = TRUE WHERE '.$post_query.'');
			
			if (!$result) return false;
			
			return true;
		}
		*/
		
		public function GetPost($post_id)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `PostID`, `Author`, `Content`, `ThreadID`, `Created` FROM `forum_posts` WHERE `PostID` = ? AND `Deleted` = FALSE', array($post_id));
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			
			return $data;
		}
		
		public function GetLatestPost($author)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT `PostID`, `Author`, `Content`, `ThreadID`, `Created` FROM `forum_posts` WHERE `Author` = ? AND `Deleted` = FALSE ORDER BY `Created` DESC LIMIT 1', array($author));
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			
			return $data;
		}
		
		public function EditPost($post_id, $content)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `forum_posts` SET `Content` = ? WHERE `PostID` = ?', array($content, $post_id));
			if ($result === false) return false;
			
			return true;
		}
		
		public function MovePost($post_id, $new_thread)
		{
			$db = $this->db;

			$result = $db->Query('UPDATE `forum_posts` SET `ThreadID` = ? WHERE `PostID` = ?', array($new_thread, $post_id));
			if ($result === false) return false;

			return true;
		}
		
		/* TODO - in case of implementation prepare one UPDATE statement and run it multiple times
		public function MassMovePost(array $moved_posts, $new_thread)
		{	
			$db = $this->db;
			
			$first = true;
			$post_query = "";
			
			foreach($moved_posts as $post_id)
			{
				if ($first)
				{
					$post_query.= '`PostID` = "'.$db->Escape($post_id).'"';
					$first = false;
				}
				else $post_query.= ' OR `PostID` = "'.$db->Escape($post_id).'"';
			}
				
			$result = $db->Query('UPDATE `forum_posts` SET `ThreadID` = "'.$db->Escape($new_thread).'" WHERE '.$post_query.'');
			if (!$result) return false;
			
			return true;
		}
		*/
		
		public function ListPosts($thread_id, $page)
		{	
			$db = $this->db;

			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->Query('SELECT `PostID`, `Author`, `Content`, `Created`, IFNULL(`Avatar`,"noavatar.jpg") as `Avatar` FROM `forum_posts` LEFT OUTER JOIN `settings` ON `forum_posts`.`Author` = `settings`.`Username` WHERE `ThreadID` = ? AND `Deleted` = FALSE ORDER BY `Created` ASC LIMIT '.(POSTS_PER_PAGE * $page).' , '.POSTS_PER_PAGE.'', array($thread_id));
			if ($result === false) return false;
			
			return $result;
		}
		
		public function CountPages($thread_id)
		{	
			$db = $this->db;

			$result = $db->Query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `ThreadID` = ? AND `Deleted` = FALSE', array($thread_id));
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			
			$pages = ceil($data['Count'] / POSTS_PER_PAGE);
			
			return $pages;
		}
		
		public function CountPosts($author)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `Author` = ? AND `Deleted` = FALSE', array($author));
			if ($result === false or count($result) == 0) return false;
			
			$data = $result[0];
			
			return $data['Count'];
		}
	}
?>
