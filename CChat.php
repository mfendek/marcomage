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
			
			$result = $db->Query('INSERT INTO `chats` (`GameID`, `Name`, `Message`) VALUES (?, ?, ?)', array($gameid, $name, $message));
			if ($result === false) return false;
			
			return true;
		}
		
		public function DeleteChat($gameid)
		{
			$db = $this->db;

			$result = $db->Query('DELETE FROM `chats` WHERE `GameID` = ?', array($gameid));
			if ($result === false) return false;
			
			return true;
		}
		
		public function ListChatMessages($gameid, $order)
		{
			$db = $this->db;

			$order = ($order == 'ASC') ? 'ASC' : 'DESC';

			$result = $db->Query('SELECT `Name`, `Message`, `Timestamp` FROM `chats` WHERE `GameID` = ? ORDER BY `Timestamp` '.$order.'', array($gameid));
			if ($result === false) return false;

			return $result;
		}
	}
?>
