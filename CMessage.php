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
			
			$result = $db->Query('INSERT INTO `messages` (`Author`, `Recipient`, `Subject`, `Content`) VALUES (?, ?, ?, ?)', array($author, $recipient, $subject, $content));
			if ($result === false) return false;
			
			return true;
		}
		
		public function SendBattleReport($player1, $player2, $player1_reports, $player2_reports, $outcome, $hidden, $message1 = '', $message2 = '', $winner = '')
		{
			$winner = ($winner != '') ? 'Winner: '.$winner : '';
			
			if ($player1_reports == "yes")
				$this->SendMessage(SYSTEM_NAME, $player1, "Battle report", "Opponent: $player2\nOutcome: {$outcome}\n".$winner."\nHide opponent's cards: ".$hidden."\n".$message1);
			
			if ($player2_reports == "yes")
				$this->SendMessage(SYSTEM_NAME, $player2, "Battle report", "Opponent: $player1\nOutcome: {$outcome}\n".$winner."\nHide opponent's cards: ".$hidden."\n".$message2);
			
			return true;
		}
		
		public function LevelUp($player, $new_level)
		{
			$this->SendMessage(SYSTEM_NAME, $player, 'Level up ('.$new_level.')', 'Congratulations, you have reached level '.$new_level.'.');
		}
		
		public function AchievementNotification($player, $achievement, $gold)
		{
			$this->SendMessage(SYSTEM_NAME, $player, 'Achievement gained', 'Congratulations, you have gained the '.$achievement.' achievement and '.$gold.' gold reward.');
		}
		
		public function WelcomeMessage($player)
		{
			$message = 'Welcome '.$player.','."\n"."\n";
			$message.= 'MArcomage team has created three starter decks for you. To quickly start a game against a computer player, go to "Games" section and click on the "Quick game vs AI" button. To quickly start a game against a human player, go to "Games" section, "Hosted games" subsection where you can either host or join a game. If you want to play a game with a specific player, you can find his profile in the "Players" section where you can challenge him directly.'."\n"."\n";
			$message.= 'To improve your play strategy you need to improve your decks. You can do this in the "Decks" section. In addition to three starter decks which can be modified as you see fit, there are multiple empty decks that are awaiting your customization.'."\n"."\n";
			$message.= 'MArcomage can be configured to your best liking in the "Settings" section. Be sure to check it out. There are many interesting features that are just waiting to be discovered. To learn more about them, seek them out in the "Help" section.'."\n"."\n";
			$message.= 'Good luck and have fun,'."\n"."\n";
			$message.= 'MArcomage development team'."\n";
			
			$this->SendMessage(SYSTEM_NAME, $player, 'Welcome to MArcomage', $message);
		}
		
		public function GetMessage($messageid, $player_name)
		{	// get message for message details section
			$db = $this->db;
			
			$result = $db->Query('SELECT `Author`, `Recipient`, `Subject`, `Content`, `Created` FROM `messages` WHERE `MessageID` = ? AND ((`Author` = ? AND `AuthorDelete` = FALSE) OR (`Recipient` = ? AND `RecipientDelete` = FALSE))', array($messageid, $player_name, $player_name));
			if ($result === false or count($result) == 0) return false;
			
			$message = $result[0];
			
			// unmark message from unread to read, when player is a recipient
			if ($message['Recipient'] == $player_name)
			{
				$result = $db->Query('UPDATE `messages` SET `Unread`= FALSE WHERE `MessageID` = ?', array($messageid));
				if ($result === false) return false;
			}
			
			return $message;
		}
		
		public function RetrieveMessage($messageid)
		{	// used when admin wants to see any message (including deleted one)
			$db = $this->db;

			$result = $db->Query('SELECT `Author`, `Recipient`, `Subject`, `Content`, `Created` FROM `messages` WHERE `MessageID` = ?', array($messageid));
			if ($result === false or count($result) == 0) return false;

			$message = $result[0];

			return $message;
		}
		
		public function DeleteMessage($messageid, $player_name)
		{	
			$db = $this->db;
			
			$result = $db->Query('SELECT `Author`, `Recipient` FROM `messages` WHERE `MessageID` = ?', array($messageid));
			if ($result === false or count($result) == 0) return false;

			$message = $result[0];
			$author = $message['Author'];
			$recipient = $message['Recipient'];
			
			$author_query = ($author == $player_name) ? '`AuthorDelete` = TRUE' : '';
			$recipient_query = ($recipient == $player_name) ? '`RecipientDelete` = TRUE' : '';
			$sup_query = (($author_query != "") AND ($recipient_query != "")) ? ', ' : '';
			
			$result = $db->Query('UPDATE `messages` SET '.$author_query.$sup_query.$recipient_query.' WHERE `MessageID` = ?', array($messageid));
			if ($result === false) return false;
			
			return true;
		}
		
		public function MassDeleteMessage(array $deleted_messages, $player_name)
		{	
			$db = $this->db;

			$params = array();
			foreach ($deleted_messages as $message_id)
				$params[$message_id] = array($player_name, $player_name, $message_id);

			$result = $db->MultiQuery('UPDATE `messages` SET `AuthorDelete` = (CASE WHEN `Author` = ? THEN TRUE ELSE `AuthorDelete` END), `RecipientDelete` = (CASE WHEN `Recipient` = ? THEN TRUE ELSE `RecipientDelete` END) WHERE `MessageID` = ?', $params);
			if ($result === false) return false;

			return true;
		}
		
		public function DeleteMessages($player)
		{
			$db = $this->db;

			$result = $db->Query('DELETE FROM `messages` WHERE ((`GameID` = 0) AND ((`Author` = ? AND `Recipient` = ?) OR (`Author` = ? AND `RecipientDelete` = TRUE) OR (`Recipient` = ? AND `AuthorDelete` = TRUE))) OR ((`GameID` > 0) AND (`Author` = ? OR `Recipient` = ?))', array(SYSTEM_NAME, $player, $player, $player, $player, $player));
			if ($result === false) return false;
			
			return true;
		}
		
		public function ListMessagesTo($player, $date, $name, $condition, $order, $page)
		{	
			$db = $this->db;

			$name_query = (($name != "none") ? ' AND `Author` =  ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($name != "none") $params[] = $name;
			if ($date != "none") $params[] = $date;

			$condition = (in_array($condition, array('Author', 'Created'))) ? $condition : 'Created';
			$order = ($order == 'ASC') ? 'ASC' : 'DESC';
			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->Query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, (CASE WHEN `Unread` = TRUE THEN "yes" ELSE "no" END) as `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = ? AND `RecipientDelete` = FALSE'.$name_query.$date_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(MESSAGES_PER_PAGE * $page).' , '.MESSAGES_PER_PAGE.'', $params);
			if ($result === false) return false;

			return $result;
		}
		
		public function CountPagesTo($player, $date, $name)
		{	
			$db = $this->db;

			$name_query = (($name != "none") ? ' AND `Author` =  ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($name != "none") $params[] = $name;
			if ($date != "none") $params[] = $date;

			$result = $db->Query('SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = ? AND `RecipientDelete` = FALSE'.$name_query.$date_query.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			$pages = ceil($data['Count'] / MESSAGES_PER_PAGE);
			
			return $pages;
		}
		
		public function ListMessagesFrom($player, $date, $name, $condition, $order, $page)
		{	
			$db = $this->db;

			$name_query = (($name != "none") ? ' AND `Recipient` =  ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($name != "none") $params[] = $name;
			if ($date != "none") $params[] = $date;

			$condition = (in_array($condition, array('Recipient', 'Created'))) ? $condition : 'Created';
			$order = ($order == 'ASC') ? 'ASC' : 'DESC';
			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->Query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, (CASE WHEN `Unread` = TRUE THEN "yes" ELSE "no" END) as `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` = ? AND `AuthorDelete` = FALSE'.$name_query.$date_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(MESSAGES_PER_PAGE * $page).' , '.MESSAGES_PER_PAGE.'', $params);
			if ($result === false) return false;

			return $result;
		}
		
		public function CountPagesFrom($player, $date, $name)
		{	
			$db = $this->db;

			$name_query = (($name != "none") ? ' AND `Recipient` =  ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($name != "none") $params[] = $name;
			if ($date != "none") $params[] = $date;

			$result = $db->Query('SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Author` = ? AND `AuthorDelete` = FALSE'.$name_query.$date_query.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			$pages = ceil($data['Count'] / MESSAGES_PER_PAGE);
			
			return $pages;
		}
		
		public function ListAllMessages($date, $name, $condition, $order, $page)
		{	// used when admin want to see list of all messages (including deleted ones)
			$db = $this->db;

			$name_query = (($name != "none") ? ' AND `Author` =  ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array(SYSTEM_NAME);
			if ($name != "none") $params[] = $name;
			if ($date != "none") $params[] = $date;

			$condition = (in_array($condition, array('Author', 'Created'))) ? $condition : 'Created';
			$order = ($order == 'ASC') ? 'ASC' : 'DESC';
			$page = (is_numeric($page)) ? $page : 0;

			$result = $db->Query('SELECT `MessageID`, `Author`, `Recipient`, `Subject`, `Content`, (CASE WHEN `Unread` = TRUE THEN "yes" ELSE "no" END) as `Unread`, `Created` FROM `messages` WHERE `GameID` = 0 AND `Author` != ?'.$name_query.$date_query.' ORDER BY `'.$condition.'` '.$order.' LIMIT '.(MESSAGES_PER_PAGE * $page).' , '.MESSAGES_PER_PAGE.'', $params);
			if ($result === false) return false;

			return $result;
		}
		
		public function CountPagesAll($date, $name)
		{	
			$db = $this->db;

			$name_query = (($name != "none") ? ' AND `Author` =  ?' : '');
			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array(SYSTEM_NAME);
			if ($name != "none") $params[] = $name;
			if ($date != "none") $params[] = $date;

			$result = $db->Query('SELECT COUNT(`MessageID`) as `Count` FROM `messages` WHERE `GameID` = 0 AND `Author` != ?'.$name_query.$date_query.'', $params);
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			$pages = ceil($data['Count'] / MESSAGES_PER_PAGE);
			
			return $pages;
		}
		
		public function CountUnreadMessages($player)
		{	
			$db = $this->db;

			$result = $db->Query('SELECT COUNT(`MessageID`) as `CountUnread` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = ? AND `RecipientDelete` = FALSE AND `Unread` = TRUE', array($player));
			if ($result === false or count($result) == 0) return false;

			$data = $result[0];
			
			return $data['CountUnread'];
		}
			
		public function ListNamesTo($player, $date)
		{	
			$db = $this->db;

			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($date != "none") $params[] = $date;

			$result = $db->Query('SELECT DISTINCT `Author` FROM `messages` WHERE `GameID` = 0 AND `Recipient` = ? AND `RecipientDelete` = FALSE'.$date_query.' ORDER BY `Author` ASC', $params);
			if ($result === false) return false;
			
			$names = array();
			foreach( $result as $data )
				$names[] = $data['Author'];
			return $names;
		}
		
		public function ListNamesFrom($player, $date)
		{	
			$db = $this->db;

			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array($player);
			if ($date != "none") $params[] = $date;

			$result = $db->Query('SELECT DISTINCT `Recipient` FROM `messages` WHERE `GameID` = 0 AND `Author` = ? AND `AuthorDelete` = FALSE'.$date_query.' ORDER BY `Recipient` ASC', $params);
			if ($result === false) return false;
			
			$names = array();
			foreach( $result as $data )
				$names[] = $data['Recipient'];
			return $names;
		}
		
		public function ListAllNames($date)
		{	// get list of authors of all messages
			$db = $this->db;

			$date_query = (($date != "none") ? ' AND `Created` >= NOW() - INTERVAL ? DAY' : '');

			$params = array(SYSTEM_NAME);
			if ($date != "none") $params[] = $date;

			$result = $db->Query('SELECT DISTINCT `Author` FROM `messages` WHERE `GameID` = 0 AND `Author` != ?'.$date_query.' ORDER BY `Author` ASC', $params);
			if ($result === false) return false;
			
			$names = array();
			foreach( $result as $data )
				$names[] = $data['Author'];
			return $names;
		}
		
		public function SendChallenge($author, $recipient, $content, $game_id)
		{
			$db = $this->db;
			
			$result = $db->Query('INSERT INTO `messages` (`Author`, `Recipient`, `Content`, `GameID`, `Created`) VALUES (?, ?, ?, ?, NOW())', array($author, $recipient, $content, $game_id));
			if ($result === false) return false;
			
			return true;
		}
		
		public function GetChallenge($player, $opponent)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT `Author`, `Recipient`, `Content`, `GameID`, `Created` FROM `messages` WHERE `GameID` > 0 AND `Author` = ? AND `Recipient` = ?', array($player, $opponent));
			if ($result === false or count($result) == 0) return false;

			$challenge = $result[0];
			
			return $challenge;
		}
		
		public function CancelChallenge($game_id)
		{
			$db = $this->db;

			$result = $db->Query('DELETE FROM `messages` WHERE `GameID` = ?', array($game_id));
			if ($result === false) return false;
			
			return true;
		}
		
		public function ListChallengesFrom($player)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `GameID`, `Recipient`, `Content`, `Created`, (CASE WHEN `Last Query` >= NOW() - INTERVAL 10 MINUTE THEN "yes" ELSE "no" END) as `Online` FROM (SELECT `Recipient`, `Content`, `Created`, `GameID` FROM `messages` WHERE `GameID` > 0 AND `Author` = ?) as `messages` INNER JOIN `logins` ON `messages`.`Recipient` = `logins`.`Username` ORDER BY `Created` DESC', array($player));
			if ($result === false) return false;
			
			return $result;
		}
		
		public function ListChallengesTo($player)
		{
			$db = $this->db;

			$result = $db->Query('SELECT `GameID`, `Author`, `Content`, `Created`, (CASE WHEN `Last Query` >= NOW() - INTERVAL 10 MINUTE THEN "yes" ELSE "no" END) as `Online` FROM (SELECT `Author`, `Content`, `Created`, `GameID` FROM `messages` WHERE `GameID` > 0 AND `Recipient` = ?) as `messages` INNER JOIN `logins` ON `messages`.`Author` = `logins`.`Username` ORDER BY `Created` DESC', array($player));
			if ($result === false) return false;
			
			return $result;
		}
	}
?>
