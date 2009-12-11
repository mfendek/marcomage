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
			$result = $db->Query('SELECT 1 FROM `forum_threads` WHERE `ThreadID` = "'.$thread_id.'" AND `Locked` = "no"');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$result = $db->Query('INSERT INTO `forum_posts` (`Author`, `Content`, `ThreadID`, `Created`) VALUES ("'.$db->Escape($author).'", "'.$db->Escape($content).'", "'.$thread_id.'", NOW())');
			if (!$result) return false;
			
			return true;
		}
		
		public function DeletePost($post_id)
		{
			$db = $this->db;
			
			$result = $db->Query('UPDATE `forum_posts` SET `Deleted` = "yes" WHERE `PostID` = "'.$post_id.'"');
			
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
					$post_query.= '`PostID` = "'.$post_id.'"';
					$first = false;
				}
				else $post_query.= ' OR `PostID` = "'.$post_id.'"';
			}
			
			$result = $db->Query('UPDATE `forum_posts` SET `Deleted` = "yes" WHERE '.$post_query.'');
			
			if (!$result) return false;
			
			return true;
		}*/
		
		public function GetPost($post_id)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT `PostID`, `Author`, `Content`, `ThreadID`, `Created` FROM `forum_posts` WHERE `PostID` = "'.$post_id.'" AND `Deleted` = "no"');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data;
		}
		
		public function EditPost($post_id, $content)
		{	
			$db = $this->db;
									
			$result = $db->Query('UPDATE `forum_posts` SET `Content` = "'.$db->Escape($content).'" WHERE `PostID` = "'.$post_id.'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function MovePost($post_id, $new_thread)
		{	
			$db = $this->db;
					
			$result = $db->Query('UPDATE `forum_posts` SET `ThreadID` = "'.$new_thread.'" WHERE `PostID` = "'.$post_id.'"');
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
					$post_query.= '`PostID` = "'.$post_id.'"';
					$first = false;
				}
				else $post_query.= ' OR `PostID` = "'.$post_id.'"';
			}
				
			$result = $db->Query('UPDATE `forum_posts` SET `ThreadID` = "'.$new_thread.'" WHERE '.$post_query.'');
			if (!$result) return false;
			
			return true;
		}*/
		
		public function ListPosts($thread_id, $page)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT `PostID`, `Author`, `Content`, `Created`, IFNULL(`Avatar`,"noavatar.jpg") as `Avatar` FROM `forum_posts` LEFT OUTER JOIN `settings` ON `forum_posts`.`Author` = `settings`.`Username` WHERE `ThreadID` = "'.$thread_id.'" AND `Deleted` = "no" ORDER BY `Created` ASC LIMIT '.(POSTS_PER_PAGE * $page).' , '.POSTS_PER_PAGE.'');
			
			if (!$result) return false;
			
			$posts = array();
			while( $data = $result->Next() )
				$posts[] = $data;
			
			return $posts;
		}
		
		public function CountPages($thread_id)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT COUNT(`PostID`) as `Count` FROM `forum_posts` WHERE `ThreadID` = "'.$thread_id.'" AND `Deleted` = "no"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			$pages = ceil($data['Count'] / POSTS_PER_PAGE);
			
			return $pages;
		}
	}
?>
