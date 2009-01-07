<?php
/*
	CMessage - message database
*/
?>
<?php
	class CMessage
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
		
		public function SendMessage($author, $recipient, $subject, $content)
		{
			$db = $this->db;
			
			$result = $db->Query('INSERT INTO `messages` (`Author`, `Recipient`, `Subject`, `Content`) VALUES ("'.$db->Escape($author).'", "'.$db->Escape($recipient).'", "'.$db->Escape($subject).'", "'.$db->Escape($content).'")');
			if (!$result) return false;
			
			return true;
		}
		
		public function GetMessage($messageid, $player_name)
		{	// get message for message details section
			$db = $this->db;
			
			$result = $db->Query('SELECT `Author`, `Recipient`, `Subject`, `Content`, `Created`, `AuthorDelete`, `RecipientDelete` FROM `messages` WHERE `MessageID` = "'.$messageid.'"');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
				
			$message = $result->Next();
			$author = $message['Author'];
			$recipient = $message['Recipient'];
			
			// if message is deleted from player's perspective return false
			if ((($player_name == $author) AND ($message['AuthorDelete'] == "yes")) OR (($player_name == $recipient) AND ($message['RecipientDelete'] == "yes"))) return false;
			
			// unmark message from unread to read, when player is a recipient
			if ($recipient == $player_name)
			{
				$result = $db->Query('UPDATE `messages` SET `Unread`= "no" WHERE `MessageID` = "'.$messageid.'"');
				if (!$result) return false;
			}
			
			return $message;
		}
		
		public function RetrieveMessage($messageid)
		{	// used when admin wants to see any message (including deleted one)
			$db = $this->db;
												
			$result = $db->Query('SELECT `Author`, `Recipient`, `Subject`, `Content`, `Created` FROM `messages` WHERE `MessageID` = "'.$messageid.'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
				
			$message = $result->Next();
						
			return $message;
		}
		
		/*public function DeleteMessage($messageid)
		{	
			$db = $this->db;
			$result = $db->Query('DELETE FROM `messages` WHERE `MessageID` = "'.$messageid.'"');
			if (!$result) return false;
			
			return true;
		}*/
		
		public function DeleteMessage($messageid, $player_name)
		{	
			$db = $this->db;
			
			$result = $db->Query('SELECT `Author`, `Recipient` FROM `messages` WHERE `MessageID` = "'.$messageid.'"');
			
			if (!$result) return false;
			if (!$result->Rows()) return false;
				
			$message = $result->Next();
			$author = $message['Author'];
			$recipient = $message['Recipient'];
			
			$author_query = ($author == $player_name) ? '`AuthorDelete` = "yes"' : '';
			$recipient_query = ($recipient == $player_name) ? '`RecipientDelete` = "yes"' : '';
			$sup_query = (($author_query != "") AND ($recipient_query != "")) ? ', ' : '';
			
			$result = $db->Query('UPDATE `messages` SET '.$author_query.$sup_query.$recipient_query.' WHERE `MessageID` = "'.$messageid.'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function ListMessagesTo($player, $filter_type, $filter_cond, $condition, $order)
		{	
			$db = $this->db;
			
			if ($filter_type == "Name") $filter_query = ' AND `Author` =  "'.$filter_cond.'"';
			elseif ($filter_type == "Created") $filter_query = ' AND `Created` >=  "'.date("Y-m-d H:i:s", (time() - (60 * 60 * 24 * $filter_cond ))).'"';
			else $filter_query = '';
									
			$result = $db->Query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = "'.$db->Escape($player).'" AND `RecipientDelete` = "no"'.$filter_query.' ORDER BY `'.$condition.'` '.$order.'');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$messages = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$messages[$i] = $result->Next();
			
			return $messages;
		}
		
		public function ListMessagesFrom($player, $filter_type, $filter_cond, $condition, $order)
		{	
			$db = $this->db;
			
			if ($filter_type == "Name") $filter_query = ' AND `Recipient` =  "'.$filter_cond.'"';
			elseif ($filter_type == "Created") $filter_query = ' AND `Created` >=  "'.date("Y-m-d H:i:s", (time() - (60 * 60 * 24 * $filter_cond ))).'"';
			else $filter_query = '';
			
			$result = $db->Query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` = "'.$db->Escape($player).'" AND `AuthorDelete` = "no"'.$filter_query.' ORDER BY `'.$condition.'` '.$order.'');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$messages = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$messages[$i] = $result->Next();
			
			return $messages;
		}
		
		public function ListAllMessages($player, $filter_type, $filter_cond, $condition, $order)
		{	// used when admin want to see list of all messages (including deleted ones)
			$db = $this->db;
			
			if ($filter_type == "Name") $filter_query = ' AND `Author` =  "'.$filter_cond.'"';
			elseif ($filter_type == "Created") $filter_query = ' AND `Created` >=  "'.date("Y-m-d H:i:s", (time() - (60 * 60 * 24 * $filter_cond ))).'"';
			else $filter_query = '';
						
			$result = $db->Query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` != "'.SYSTEM_NAME.'"'.$filter_query.' ORDER BY `'.$condition.'` '.$order.'');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$messages = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$messages[$i] = $result->Next();
			
			return $messages;
		}
		
		public function CountUndreadMessages($player)
		{	
			$db = $this->db;
												
			$result = $db->Query('SELECT COUNT(`MessageID`) as `CountUnread` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = "'.$db->Escape($player).'" AND `RecipientDelete` = "no" AND `Unread` = "yes"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data['CountUnread'];
		}
			
		public function ListNamesTo($player)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT DISTINCT `Author` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = "'.$db->Escape($player).'" AND `RecipientDelete` = "no" ORDER BY `Author` ASC');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$names = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$names[$i] = $result->Next();
			
			return $names;
		}
		
		public function ListNamesFrom($player)
		{	
			$db = $this->db;
						
			$result = $db->Query('SELECT DISTINCT `Recipient` FROM `messages` WHERE `GameID` = 0 AND `Author` = "'.$db->Escape($player).'" AND `AuthorDelete` = "no" ORDER BY `Recipient` ASC');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$names = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$names[$i] = $result->Next();
			
			return $names;
		}
		
		public function ListAllNames($player)
		{	// get list of authors of all messages
			$db = $this->db;
									
			$result = $db->Query('SELECT DISTINCT `Author` FROM `messages` WHERE `GameID` = 0 ORDER BY `Recipient` ASC');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$names = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$names[$i] = $result->Next();
			
			return $names;
		}
		
		function TimeSections() // date filter options
		{
			$section = array();
			
			$section[1] = "1 day";
			$section[2] = "2 days";
			$section[5] = "5 days";
			$section[7] = "1 week";
			$section[14] = "2 weeks";
			$section[21] = "3 weeks";
			$section[30] = "1 month";
			$section[91] = "3 months";
			$section[182] = "6 months";
			$section[365] = "1 year";
			
			return $section;
		}
		
		public function SendChallenge($author, $recipient, $content, $game_id)
		{
			$db = $this->db;
			
			$result = $db->Query('INSERT INTO `messages` (`Author`, `Recipient`, `Content`, `GameID`, `Created`) VALUES ("'.$db->Escape($author).'", "'.$db->Escape($recipient).'", "'.$db->Escape($content).'", "'.$game_id.'", NOW())');
			if (!$result) return false;
			
			return true;
		}
		
		public function GetChallenge($player, $opponent)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT `Author`, `Recipient`, `Content`, `GameID`, `Created` FROM `messages` WHERE `GameID` > 0 AND `Author` = "'.$db->Escape($player).'" AND `Recipient` = "'.$db->Escape($opponent).'"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$challenge = $result->Next();
			
			return $challenge;
		}
		
		public function CancelChallenge($game_id)
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `messages` WHERE `GameID` = "'.$game_id.'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function ListChallengesFrom($player)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Recipient`, `Content`, `Created` FROM `messages` WHERE `GameID` > 0 AND `Author` = "'.$db->Escape($player).'" ORDER BY `Created` DESC');
			if (!$result) return false;
			
			$challenges = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$challenges[$i] = $result->Next();
			
			return $challenges;
		}
		
		public function ListChallengesTo($player)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Author`, `Content`, `Created` FROM `messages` WHERE `GameID` > 0 AND `Recipient` = "'.$db->Escape($player).'" ORDER BY `Created` DESC');
			if (!$result) return false;
			
			$challenges = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$challenges[$i] = $result->Next();
			
			return $challenges;
		}
	}
?>
