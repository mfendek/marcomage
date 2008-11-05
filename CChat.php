<?php
/*
	CChat - player conversation during a game
*/
?>
<?php
	class CChats
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
		
		public function SaveChatMessage($gameid, $message, $name)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT MAX(`Number`)+1 as number FROM `chats` WHERE `GameID` = "'.$gameid.'"');
			$data = $result->Next();
			$number = (int)$data['number'];
			
			$result = $db->Query('INSERT INTO `chats` (`GameID`, `Number`, `Name`, `Message`, `Timestamp`) VALUES ("'.$gameid.'", "'.$number.'", "'.$db->Escape($name).'", "'.$db->Escape($message).'", UNIX_TIMESTAMP())');
			if (!$result) return false;
			
			return true;
		}
		
		public function DeleteChat($gameid)
		{
			$db = $this->db;
			$result = $db->Query('DELETE FROM `chats` WHERE `GameID` = "'.$gameid.'"');
			if (!$result) return false;
			
			return true;
		}
		
		public function ListChatMessages($gameid)
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT `Number`, `Name`, `Message`, `Timestamp` FROM `chats` WHERE `GameID` = "'.$gameid.'" ORDER BY `Number` DESC LIMIT 0 , 21');
			if (!$result) return false;
			
			$messages = array();
			for ($i = 1; $i <= $result->Rows(); $i++)
			{
				$temp = $result->Next();
				$messages[$i]['Name'] = $temp['Name'];
				$messages[$i]['Message'] = $temp['Message'];
				$messages[$i]['Timestamp'] = $temp['Timestamp'];
			}
			
			return $messages;
		}
	}
?>
