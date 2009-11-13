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
		
		public function SendBattleReport($player1, $player2, $player1_reports, $player2_reports, $outcome, $hidden, $winner = '')
		{
			$winner = ($winner != '') ? 'Winner: '.$winner : '';
			
			if ($player1_reports == "yes")
				$this->SendMessage("MArcomage", $player1, "Battle report", "Opponent: $player2\nOutcome: {$outcome}\n".$winner."\nHide opponent's cards: ".$hidden);
			
			if ($player2_reports == "yes")
				$this->SendMessage("MArcomage", $player2, "Battle report", "Opponent: $player1\nOutcome: {$outcome}\n".$winner."\nHide opponent's cards: ".$hidden);
			
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
		
		public function MassDeleteMessage(array $deleted_messages, $player_name)
		{	
			$db = $this->db;
			
			$first = true;
			$query = "";
			
			foreach($deleted_messages as $message_id)
			{
				if ($first)
				{
					$query.= '`MessageID` = "'.$message_id.'"';
					$first = false;
				}
				else $query.= ' OR `MessageID` = "'.$message_id.'"';
			}
			
			$result = $db->Query('UPDATE `messages` SET `AuthorDelete` = (CASE WHEN `Author` = "'.$db->Escape($player_name).'" THEN "yes" ELSE `AuthorDelete` END), `RecipientDelete` = (CASE WHEN `Recipient` = "'.$db->Escape($player_name).'" THEN "yes" ELSE `RecipientDelete` END) WHERE '.$query.'');
			
			if (!$result) return false;
			
			return true;
		}
		
		public function ListMessagesTo($player, $date, $name, $condition, $order, $page)
		{	
			$db = $this->db;
			
			$name_query = (($name != "none") ? ' AND `Author` =  "'.$name.'"' : '');
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`Created`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			
			$result = $db->Query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = "'.$db->Escape($player).'" AND `RecipientDelete` = "no"'.$name_query.$date_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(MESSAGES_PER_PAGE * $page).' , '.MESSAGES_PER_PAGE.'');
			if (!$result) return false;
			
			$messages = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$messages[$i] = $result->Next();
			
			return $messages;
		}
		
		public function CountPagesTo($player, $date, $name)
		{	
			$db = $this->db;
			
			$name_query = (($name != "none") ? ' AND `Author` =  "'.$name.'"' : '');
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`Created`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			
			$result = $db->Query('SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = "'.$db->Escape($player).'" AND `RecipientDelete` = "no"'.$name_query.$date_query.'');
			if (!$result) return false;
			
			$data = $result->Next();
			
			$pages = ceil($data['Count'] / MESSAGES_PER_PAGE);
			
			return $pages;
		}
		
		public function ListMessagesFrom($player, $date, $name, $condition, $order, $page)
		{	
			$db = $this->db;
			
			$name_query = (($name != "none") ? ' AND `Recipient` =  "'.$name.'"' : '');
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`Created`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			
			$result = $db->Query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` = "'.$db->Escape($player).'" AND `AuthorDelete` = "no"'.$name_query.$date_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(MESSAGES_PER_PAGE * $page).' , '.MESSAGES_PER_PAGE.'');
			if (!$result) return false;
			
			$messages = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$messages[$i] = $result->Next();
			
			return $messages;
		}
		
		public function CountPagesFrom($player, $date, $name)
		{	
			$db = $this->db;
			
			$name_query = (($name != "none") ? ' AND `Recipient` =  "'.$name.'"' : '');
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`Created`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			
			$result = $db->Query('SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Author` = "'.$db->Escape($player).'" AND `AuthorDelete` = "no"'.$name_query.$date_query.'');
			if (!$result) return false;
			
			$data = $result->Next();
			
			$pages = ceil($data['Count'] / MESSAGES_PER_PAGE);
			
			return $pages;
		}
		
		public function ListAllMessages($player, $date, $name, $condition, $order, $page)
		{	// used when admin want to see list of all messages (including deleted ones)
			$db = $this->db;
			
			$name_query = (($name != "none") ? ' AND `Author` =  "'.$name.'"' : '');
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`Created`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			
			$result = $db->Query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` != "'.SYSTEM_NAME.'"'.$name_query.$date_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(MESSAGES_PER_PAGE * $page).' , '.MESSAGES_PER_PAGE.'');
			if (!$result) return false;
			
			$messages = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$messages[$i] = $result->Next();
			
			return $messages;
		}
		
		public function CountPagesAll($player, $date, $name)
		{	
			$db = $this->db;
			
			$name_query = (($name != "none") ? ' AND `Author` =  "'.$name.'"' : '');
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`Created`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			
			$result = $db->Query('SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Author` != "'.SYSTEM_NAME.'"'.$name_query.$date_query.'');
			if (!$result) return false;
			
			$data = $result->Next();
			
			$pages = ceil($data['Count'] / MESSAGES_PER_PAGE);
			
			return $pages;
		}
		
		public function CountUnreadMessages($player)
		{	
			$db = $this->db;
												
			$result = $db->Query('SELECT COUNT(`MessageID`) as `CountUnread` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = "'.$db->Escape($player).'" AND `RecipientDelete` = "no" AND `Unread` = "yes"');
			if (!$result) return false;
			if (!$result->Rows()) return false;
			
			$data = $result->Next();
			
			return $data['CountUnread'];
		}
			
		public function ListNamesTo($player, $date)
		{	
			$db = $this->db;
			
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`Created`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			
			$result = $db->Query('SELECT DISTINCT `Author` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = "'.$db->Escape($player).'" AND `RecipientDelete` = "no"'.$date_query.' ORDER BY `Author` ASC');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Author'];
			return $names;
		}
		
		public function ListNamesFrom($player, $date)
		{	
			$db = $this->db;
			
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`Created`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			
			$result = $db->Query('SELECT DISTINCT `Recipient` FROM `messages` WHERE `GameID` = 0 AND `Author` = "'.$db->Escape($player).'" AND `AuthorDelete` = "no"'.$date_query.' ORDER BY `Recipient` ASC');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Recipient'];
			return $names;
		}
		
		public function ListAllNames($player, $date)
		{	// get list of authors of all messages
			$db = $this->db;
			
			$date_query = (($date != "none") ? ' AND UNIX_TIMESTAMP(`Created`) >=  (UNIX_TIMESTAMP() - 60 * 60 * 24 * '.$date.')' : '');
			
			$result = $db->Query('SELECT DISTINCT `Author` FROM `messages` WHERE `GameID` = 0 AND `Author` != "'.SYSTEM_NAME.'"'.$date_query.' ORDER BY `Recipient` ASC');
			if (!$result) return false;
			
			$names = array();
			while( $data = $result->Next() )
				$names[] = $data['Author'];
			return $names;
		}
		
		function TimeSections() // date filter options
		{
			$section = array();
			$timesections = array();
			
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
			
			foreach ($section as $date_val => $date_text)
				array_push($timesections, array("time" => $date_val, "text" => $date_text));
			
			return $timesections;
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
			$result = $db->Query('SELECT `Recipient`, `Content`, `Created`, (CASE WHEN UNIX_TIMESTAMP(`Last Query`) >= UNIX_TIMESTAMP() - 60*10 THEN "yes" ELSE "no" END) as `Online` FROM (SELECT `Recipient`, `Content`, `Created` FROM `messages` WHERE `GameID` > 0 AND `Author` = "'.$db->Escape($player).'") as `messages` INNER JOIN `logins` ON `messages`.`Recipient` = `logins`.`Username` ORDER BY `Created` DESC');
			if (!$result) return false;
			
			$challenges = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$challenges[$i] = $result->Next();
			
			return $challenges;
		}
		
		public function ListChallengesTo($player)
		{
			$db = $this->db;
			$result = $db->Query('SELECT `Author`, `Content`, `Created`, (CASE WHEN UNIX_TIMESTAMP(`Last Query`) >= UNIX_TIMESTAMP() - 60*10 THEN "yes" ELSE "no" END) as `Online` FROM (SELECT `Author`, `Content`, `Created` FROM `messages` WHERE `GameID` > 0 AND `Recipient` = "'.$db->Escape($player).'") as `messages` INNER JOIN `logins` ON `messages`.`Author` = `logins`.`Username` ORDER BY `Created` DESC');
			if (!$result) return false;
			
			$challenges = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
				$challenges[$i] = $result->Next();
			
			return $challenges;
		}
	}
?>
