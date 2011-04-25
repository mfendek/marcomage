<?php

	// UPDATE SCRIPT FOR GAMES
	// tranforms old game data format to new one

	require_once('../config.php');
	require_once('../CDeck.php');
	require_once('../CGame.php');
	require_once('../CGameAI.php');
	require_once('../CChat.php');

	class CDatabase
	{
		private $db = false;
		public $status = 'ERROR_DB_OFFLINE';
		public $queries = 0; // counter
		public $qtime = 0; // time spent
		public $log = array(); // query log
		
		public function __construct($server, $username, $password, $database)
		{
			$db = mysql_connect($server, $username, $password);
			if (!$db) { $this->status = 'ERROR_MYSQL_CONNECT'; return; };
			
			$status = mysql_select_db($database, $db);
			if (!$status) { $this->status = 'ERROR_MYSQL_SELECT_DB'; return; };
			
			$status = mysql_query("SET NAMES utf8 COLLATE utf8_unicode_ci", $db);
			if (!$status) { $this->status = 'ERROR_MYSQL_SET_NAMES'; return; };
			
			$this->db = $db;
			$this->status = 'SUCCESS';
			return;
		}
		
		public function isOnline()
		{
			return ($this->db) ? true : false;
		}
		
		public function Escape($string)
		{
			return mysql_real_escape_string($string, $this->db);
		}
		
		public function LastID()
		{
			return mysql_insert_id();
		}
		
		public function Query($query)
		{
			if (!$this->db) { $this->status = 'ERROR_DB_OFFLINE'; return false; };
			
			$t_start = microtime(TRUE);
			$result = mysql_query($query, $this->db);
			$t_end = microtime(TRUE);
			if( $result === false ) { $this->status = 'ERROR_MYSQL_QUERY: '.mysql_error($this->db); return false; };

			$data = array();
			if( is_resource($result) )
				while( ($row = mysql_fetch_array($result, MYSQL_ASSOC)) !== false )
					$data[] = $row;
			
			$this->queries++;
			$this->qtime += $t_end - $t_start;
			$this->log[] = sprintf("[%.2f ms] %s", round(1000*($t_end - $t_start),2), $query);

			$this->status = 'SUCCESS';
			return $data;
		}
	}

	$db = new CDatabase($server, $username, $password, $database);
	if( $db->status != 'SUCCESS' )
	{
		header("Content-type: text/html");
		die("Unable to connect to database, aborting.");
	}

	if( false === date_default_timezone_set("Etc/UTC")
	||  false === $db->Query("SET time_zone='Etc/UTC'")
	&&  false === $db->Query("SET time_zone='+0:00'") )
	{
		header("Content-type: text/html");
		die("Unable to configure time zone, aborting.");
	}

	$gamedb = new CGames($db);

	echo "Updating game data...<br /><br />";

	// get list of untransformed games
	$list = $db->Query("SELECT `Player1`, `Player2`, `Data`, `State`, `GameID` FROM `games` ORDER BY `GameID` ASC");
	if ($list === false) exit('Failed to retrieve games from DB.');

	foreach( $list as $data )
	{
		// get game data
		$game_id = $data['GameID'];
		$player1 = $data['Player1'];
		$player2 = $data['Player2'];
		$state = $data['State'];
		$old_data = $data['Data'];
		$old_data = unserialize($old_data);

		$new_data[1] = $old_data[$player1];
		$new_data[2] = ($state != 'waiting') ? $old_data[$player2] : '';// waiting games don't have player2 data

		// save updated data
		$result = $db->Query('UPDATE `games` SET `Data` = "'.$db->Escape(serialize($new_data)).'" WHERE `GameID` = '.$db->Escape($game_id).'');
		if ($result === false) echo 'Failed to save transformed game data (GameID = '.$game_id.').<br />';
	}

	echo "<br /><br />Done.";
?>
