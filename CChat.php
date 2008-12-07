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
			
			$result = $db->Query('INSERT INTO `chats` (`GameID`, `Number`, `Name`, `Message`) VALUES ("'.$gameid.'", "'.$number.'", "'.$db->Escape($name).'", "'.$db->Escape($message).'")');
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
		
		public function ListChatMessages($gameid, $order = "DESC")
		{
			$db = $this->db;
			
			$result = $db->Query('SELECT `Name`, `Message`, `Timestamp` FROM `chats` WHERE `GameID` = "'.$gameid.'" ORDER BY `Number` '.$order.' LIMIT 0 , 21');
			if (!$result) return false;
			
			$messages = array();
			while( $data = $result->Next() )
				$messages[] = $data;
			
			return $messages;
		}
	}
?>
