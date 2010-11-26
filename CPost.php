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
			
			// verifiy if thread exists and isn't locked
			$result = $db->Query('SELECT 1 FROM `forum_threads` WHERE `ThreadID` = "'.$db->Escape($thread_id).'" AND `Locked` = FALSE');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$result = $db->Query('INSERT INTO `forum_posts` (`Author`, `Content`, `ThreadID`, `Created`) VALUES ("'.$db->Escape($author).'", "'.$db->Escape($content).'", "'.$db->Escape($thread_id).'", NOW())');
			if (!$result) return false;
			
			return true;
		}
		
		public function DeletePost($post_id)
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `forum_posts` SET `Deleted` = TRUE WHERE `PostID` = "'.$db->Escape($post_id).'"');
			
			if (!$result) return false;
			
			return true;
		}
				
		/*public function MassDeletePost(array $deleted_posts)
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
		}*/
		
		public function GetPost($post_id)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT `PostID`, `Author`, `Content`, `ThreadID`, `Created` FROM `forum_posts` WHERE `PostID` = "'.$db->Escape($post_id).'" AND `Deleted` = FALSE');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data;
		}
		
		public function GetLatestPost($author)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT `PostID`, `Author`, `Content`, `ThreadID`, `Created` FROM `forum_posts` WHERE `Author` = "'.$db->Escape($author).'" AND `Deleted` = FALSE ORDER BY `Created` DESC LIMIT 1');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data;
		}
		
		public function EditPost($post_id, $content)
		{	
			$db = $this->db;
									
			$result = $db->Query('UPDATE `forum_posts` SET `Content` = "'.$db->Escape($content).'" WHERE `PostID` = "'.$db->Escape($post_id).'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function MovePost($post_id, $new_thread)
		{	
			$db = $this->db;
					
			$result = $db->Query('UPDATE `forum_posts` SET `ThreadID` = "'.$db->Escape($new_thread).'" WHERE `PostID` = "'.$db->Escape($post_id).'"');
			if (!$result) return false;
			
			return true;
		}
		
		/*public function MassMovePost(array $moved_posts, $new_thread)
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
		}*/
		
		public function ListPosts($thread_id, $page)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT `PostID`, `Author`, `Content`, `Created`, IFNULL(`Avatar`,"noavatar.jpg") as `Avatar` FROM `forum_posts` LEFT OUTER JOIN `settings` ON `forum_posts`.`Author` = `settings`.`Username` WHERE `ThreadID` = "'.$db->Escape($thread_id).'" AND `Deleted` = FALSE ORDER BY `Created` ASC LIMIT '.(POSTS_PER_PAGE * $db->Escape($page)).' , '.POSTS_PER_PAGE.'');
			
			if (!$result) return false;
			
			$posts = array();
			while( $data = $result->Next() )
				$posts[] = $data;
			
			return $posts;
		}
		
		public function CountPages($thread_id)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `ThreadID` = "'.$db->Escape($thread_id).'" AND `Deleted` = FALSE');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			$pages = ceil($data['Count'] / POSTS_PER_PAGE);
			
			return $pages;
		}
		
		public function CountPosts($author)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `Author` = "'.$db->Escape($author).'" AND `Deleted` = FALSE');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data['Count'];
		}
	}
?>
