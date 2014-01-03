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
		
		public function getDB()
		{
			return $this->db;
		}
		
		public function saveChatMessage($gameid, $message, $name)
		{
			$db = $this->db;
			
			$result = $db->query('INSERT INTO `chats` (`GameID`, `Name`, `Message`) VALUES (?, ?, ?)', array($gameid, $name, $message));
			if ($result === false) return false;
			
			return true;
		}
		
		public function deleteChat($gameid)
		{
			$db = $this->db;

			$result = $db->query('DELETE FROM `chats` WHERE `GameID` = ?', array($gameid));
			if ($result === false) return false;
			
			return true;
		}
		
		public function listChatMessages($gameid, $order)
		{
			$db = $this->db;

			$order = ($order == 'ASC') ? 'ASC' : 'DESC';

			$result = $db->query('SELECT `Name`, `Message`, `Timestamp` FROM `chats` WHERE `GameID` = ? ORDER BY `Timestamp` '.$order.'', array($gameid));
			if ($result === false) return false;

			return $result;
		}

		public function newMessages($gameid, $player, $time)
		{
			$db = $this->db;

			$result = $db->query('SELECT 1 FROM `chats` WHERE `GameID` = ? AND `Name` != ? AND `Timestamp` > ? LIMIT 1', array($gameid, $player, $time));
			if ($result === false or count($result) == 0) return false;

			return true;
		}
	}
?>
