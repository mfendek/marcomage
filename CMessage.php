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
		
		public function getDB()
		{
			return $this->db;
		}		
		
		public function sendMessage($author, $recipient, $subject, $content)
		{
			$db = $this->db;
			
			$result = $db->query('INSERT INTO `messages` (`Author`, `Recipient`, `Subject`, `Content`) VALUES (?, ?, ?, ?)', array($author, $recipient, $subject, $content));
			if ($result === false) return false;
			
			return true;
		}
		
		public function sendBattleReport($player1, $player2, $player1_reports, $player2_reports, $outcome, $hidden, $message1 = '', $message2 = '', $winner = '')
		{
			$winner = ($winner != '') ? 'Winner: '.$winner : '';
			
			if ($player1_reports == "yes")
				$this->sendMessage(SYSTEM_NAME, $player1, "Battle report", "Opponent: [link=?location=Players_details&Profile=".urlencode($player2)."]".$player2."[/link]\nOutcome: {$outcome}\n".$winner."\nHide opponent's cards: ".$hidden."\n".$message1);
			
			if ($player2_reports == "yes")
				$this->sendMessage(SYSTEM_NAME, $player2, "Battle report", "Opponent: [link=?location=Players_details&Profile=".urlencode($player1)."]".$player1."[/link]\nOutcome: {$outcome}\n".$winner."\nHide opponent's cards: ".$hidden."\n".$message2);
			
			return true;
		}
		
		public function levelUp($player, $new_level)
		{
			$this->sendMessage(SYSTEM_NAME, $player, 'Level up ('.$new_level.')', 'Congratulations, you have reached level '.$new_level.'.');
		}
		
		public function achievementNotification($player, $achievement, $gold)
		{
			$this->sendMessage(SYSTEM_NAME, $player, 'Achievement gained', 'Congratulations, you have gained the '.$achievement.' achievement and '.$gold.' gold reward.');
		}
		
		public function welcomeMessage($player)
		{
			$message = 'Welcome '.$player.','."\n"."\n";
			$message.= 'MArcomage team has created three starter decks for you. To quickly start a game against a computer player, go to "Games" section and click on the "Quick game vs AI" button. To quickly start a game against a human player, go to "Games" section, "Hosted games" subsection where you can either host or join a game. If you want to play a game with a specific player, you can find his profile in the "Players" section where you can challenge him directly.'."\n"."\n";
			$message.= 'To improve your play strategy you need to improve your decks. You can do this in the "Decks" section. In addition to three starter decks which can be modified as you see fit, there are multiple empty decks that are awaiting your customization.'."\n"."\n";
			$message.= 'MArcomage can be configured to your best liking in the "Settings" section. Be sure to check it out. There are many interesting features that are just waiting to be discovered. To learn more about them, seek them out in the "Help" section.'."\n"."\n";
			$message.= 'Good luck and have fun,'."\n"."\n";
			$message.= 'MArcomage development team'."\n";
			
			return $this->sendMessage(SYSTEM_NAME, $player, 'Welcome to MArcomage', $message);
		}
		
		public function getMessage($messageid, $player_name)
		{	// get message for message details section
			$db = $this->db;
			
			$result = $db->query('SELECT `Author`, `Recipient`, `Subject`, `Content`, `Created` FROM `messages` WHERE `MessageID` = ? AND ((`Author` = ? AND `AuthorDelete` = FALSE) OR (`Recipient` = ? AND `RecipientDelete` = FALSE))', array($messageid, $player_name, $player_name));
			if ($result === false or count($result) == 0) return false;
			
			$message = $result[0];
			
			// unmark message from unread to read, when player is a recipient
			if ($message['Recipient'] == $player_name)
			{
				$result = $db->query('UPDATE `messages` SET `Unread`= FALSE WHERE `MessageID` = ?', array($messageid));
				if ($result === false) return false;
			}
			
			return $message;
		}
		
		public function retrieveMessage($messageid)
		{	// used when admin wants to see any message (including deleted one)
			$db = $this->db;

			$result = $db->query('SELECT `Author`, `Recipient`, `Subject`, `Content`, `Created` FROM `messages` WHERE `MessageID` = ?', array($messageid));
			if ($result === false or count($result) == 0) return false;

			$message = $result[0];

			return $message;
		}
		
		public function deleteMessage($messageid, $player_name)
		{	
			$db = $this->db;
			
			$result = $db->query('SELECT `Author`, `Recipient` FROM `messages` WHERE `MessageID` = ?', array($messageid));
			if ($result === false or count($result) == 0) return false;

			$message = $result[0];
			$author = $message['Author'];
			$recipient = $message['Recipient'];
			
			$author_query = ($author == $player_name) ? '`AuthorDelete` = TRUE' : '';
			$recipient_query = ($recipient == $player_name) ? '`RecipientDelete` = TRUE' : '';
			$sup_query = (($author_query != "") AND ($recipient_query != "")) ? ', ' : '';
			
			$result = $db->query('UPDATE `messages` SET '.$author_query.$sup_query.$recipient_query.' WHERE `MessageID` = ?', array($messageid));
			if ($result === false) return false;
			
			return true;
		}
		
		public function deleteSystemMessage($messageid)
		{	
			$db = $this->db;
			
			$result = $db->query('DELETE FROM `messages` WHERE `MessageID` = ?', array($messageid));
			if ($result === false) return false;
			
			return true;
		}
		
		public function massdeleteMessage(array $deleted_messages, $player_name)
		{	
			$db = $this->db;

			$params = array();
			foreach ($deleted_messages as $message_id)
				$params[$message_id] = array($player_name, $player_name, $message_id);

			$result = $db->multiquery('UPDATE `messages` SET `AuthorDelete` = (CASE WHEN `Author` = ? THEN TRUE ELSE `AuthorDelete` END), `RecipientDelete` = (CASE WHEN `Recipient` = ? THEN TRUE ELSE `RecipientDelete` END) WHERE `MessageID` = ?', $params);
			if ($result === false) return false;

			return true;
		}
		
		public function deleteMessages($player)
		{
			$db = $this->db;

			$result = $db->query('DELETE FROM `messages` WHERE ((`GameID` = 0) AND ((`Author` = ? AND `Recipient` = ?) OR (`Author` = ? AND `RecipientDelete` = TRUE) OR (`Recipient` = ? AND `AuthorDelete` = TRUE))) OR ((`GameID` > 0) AND (`Author` = ? OR `Recipient` = ?))', array(SYSTEM_NAME, $player, $player, $player, $player, $player));
			if ($result === false) return false;
			
			return true;
		}
		
		public function listMessagesTo($player, $date, $name, $condition, $order, $page)
		{	
			$db = $this->db;

			$name_query = (($name != "") ? ' AND `Author` LIKE ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($name != "") $params[] = '%'.$name.'%';
			if ($date != "none") $params[] = $date;

			$condition = (in_array($condition, array('Author', 'Created'))) ? $condition : 'Created';
			$order = ($order == 'ASC') ? 'ASC' : 'DESC';
			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, (CASE WHEN `Unread` = TRUE THEN "yes" ELSE "no" END) as `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = ? AND `RecipientDelete` = FALSE'.$name_query.$date_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(MESSAGES_PER_PAGE * $page).' , '.MESSAGES_PER_PAGE.'', $params);
			if ($result === false) return false;

			return $result;
		}
		
		public function countPagesTo($player, $date, $name)
		{	
			$db = $this->db;

			$name_query = (($name != "") ? ' AND `Author` LIKE ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($name != "") $params[] = '%'.$name.'%';
			if ($date != "none") $params[] = $date;

			$result = $db->query('SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = ? AND `RecipientDelete` = FALSE'.$name_query.$date_query.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			$pages = ceil($data['Count'] / MESSAGES_PER_PAGE);
			
			return $pages;
		}
		
		public function listMessagesFrom($player, $date, $name, $condition, $order, $page)
		{	
			$db = $this->db;

			$name_query = (($name != "") ? ' AND `Recipient` LIKE ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($name != "") $params[] = '%'.$name.'%';
			if ($date != "none") $params[] = $date;

			$condition = (in_array($condition, array('Recipient', 'Created'))) ? $condition : 'Created';
			$order = ($order == 'ASC') ? 'ASC' : 'DESC';
			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, (CASE WHEN `Unread` = TRUE THEN "yes" ELSE "no" END) as `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` = ? AND `AuthorDelete` = FALSE'.$name_query.$date_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(MESSAGES_PER_PAGE * $page).' , '.MESSAGES_PER_PAGE.'', $params);
			if ($result === false) return false;

			return $result;
		}
		
		public function countPagesFrom($player, $date, $name)
		{	
			$db = $this->db;

			$name_query = (($name != "") ? ' AND `Recipient` LIKE ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($name != "") $params[] = '%'.$name.'%';
			if ($date != "none") $params[] = $date;

			$result = $db->query('SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Author` = ? AND `AuthorDelete` = FALSE'.$name_query.$date_query.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			$pages = ceil($data['Count'] / MESSAGES_PER_PAGE);
			
			return $pages;
		}
		
		public function listAllMessages($date, $name, $condition, $order, $page)
		{	// used when admin want to see list of all messages (including deleted ones)
			$db = $this->db;

			$name_query = (($name != "") ? ' AND `Author` LIKE ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array(SYSTEM_NAME);
			if ($name != "") $params[] = '%'.$name.'%';
			if ($date != "none") $params[] = $date;

			$condition = (in_array($condition, array('Author', 'Created'))) ? $condition : 'Created';
			$order = ($order == 'ASC') ? 'ASC' : 'DESC';
			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, (CASE WHEN `Unread` = TRUE THEN "yes" ELSE "no" END) as `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` != ?'.$name_query.$date_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(MESSAGES_PER_PAGE * $page).' , '.MESSAGES_PER_PAGE.'', $params);
			if ($result === false) return false;

			return $result;
		}
		
		public function countPagesAll($date, $name)
		{	
			$db = $this->db;

			$name_query = (($name != "") ? ' AND `Author` LIKE ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array(SYSTEM_NAME);
			if ($name != "") $params[] = '%'.$name.'%';
			if ($date != "none") $params[] = $date;

			$result = $db->query('SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Author` != ?'.$name_query.$date_query.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			$pages = ceil($data['Count'] / MESSAGES_PER_PAGE);
			
			return $pages;
		}
		
		public function countUnreadMessages($player)
		{	
			$db = $this->db;

			$result = $db->query('SELECT COUNT(`MessageID`) as `CountUnread` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = ? AND `RecipientDelete` = FALSE AND `Unread` = TRUE', array($player));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			return $data['CountUnread'];
		}
		
		public function sendChallenge($author, $recipient, $content, $game_id)
		{
			$db = $this->db;
			
			$result = $db->query('INSERT INTO `messages` (`Author`, `Recipient`, `Subject`, `Content`, `GameID`, `Created`) VALUES (?, ?, "", ?, ?, NOW())', array($author, $recipient, $content, $game_id));
			if ($result === false) return false;
			
			return true;
		}
		
		public function getChallenge($player, $opponent)
		{
			$db = $this->db;
			
			$result = $db->query('SELECT `Author`, `Recipient`, `Content`, `GameID`, `Created` FROM `messages` WHERE `GameID` > 0 AND `Author` = ? AND `Recipient` = ?', array($player, $opponent));
			if ($result === false or count($result) == 0) return false;

			$challenge = $result[0];
			
			return $challenge;
		}
		
		public function cancelChallenge($game_id)
		{
			$db = $this->db;

			$result = $db->query('DELETE FROM `messages` WHERE `GameID` = ?', array($game_id));
			if ($result === false) return false;
			
			return true;
		}
		
		public function listChallengesFrom($player)
		{
			$db = $this->db;

			$result = $db->query('SELECT `GameID`, `Recipient`, `Content`, `Created`, (CASE WHEN `Last Query` >= NOW() - INTERVAL 10 MINUTE THEN "yes" ELSE "no" END) as `Online` FROM (SELECT `Recipient`, `Content`, `Created`, `GameID` FROM `messages` WHERE `GameID` > 0 AND `Author` = ?) as `messages` INNER JOIN `logins` ON `messages`.`Recipient` = `logins`.`Username` ORDER BY `Created` DESC', array($player));
			if ($result === false) return false;
			
			return $result;
		}
		
		public function listChallengesTo($player)
		{
			$db = $this->db;

			$result = $db->query('SELECT `GameID`, `Author`, `Content`, `Created`, (CASE WHEN `Last Query` >= NOW() - INTERVAL 10 MINUTE THEN "yes" ELSE "no" END) as `Online` FROM (SELECT `Author`, `Content`, `Created`, `GameID` FROM `messages` WHERE `GameID` > 0 AND `Recipient` = ?) as `messages` INNER JOIN `logins` ON `messages`.`Author` = `logins`.`Username` ORDER BY `Created` DESC', array($player));
			if ($result === false) return false;
			
			return $result;
		}
	}
?>
