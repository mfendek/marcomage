<?php
/*
	CDatabase - encapsulated MySQL database access
*/
?>
<?php
	class CDatabase
	{
		private $db = false; // mysqli object
		public $status = 'ERROR_DB_OFFLINE';
		public $queries = 0; // counter
		public $qtime = 0; // time spent
		public $log = array(); // query log
		
		public function __construct($server, $username, $password, $database)
		{
			$db = mysqli_connect($server, $username, $password);
			if (!$db) { $this->status = 'ERROR_MYSQL_CONNECT'; return; };
			
			$status = $db->select_db($database);
			if (!$status) { $this->status = 'ERROR_MYSQL_SELECT_DB'; return; };
			
			$status = $db->query("SET NAMES utf8 COLLATE utf8_unicode_ci");
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
			return $this->db->escape_string($string);
		}
		
		public function LastID()
		{
			return $this->db->insert_id;
		}
		
		public function Query($query, array $params = array())
		{
			$db = $this->db;
			if( $db === false ) { $this->status = 'ERROR_DB_OFFLINE'; return false; };
			
			$t_start = microtime(TRUE);
			$result = $db->query($query);
			$t_end = microtime(TRUE);
			if( $result === false ) { $this->status = 'ERROR_MYSQL_QUERY: '.$db->error; return false; };

			$data = array();
			if( is_object($result) )
			{
				while( ($row = $result->fetch_assoc()) !== NULL )
					$data[] = $row;

				$result->free();
			}
			
			$this->queries++;
			$this->qtime += $t_end - $t_start;
			$this->log[] = sprintf("[%.2f ms] %s", round(1000*($t_end - $t_start),2), $query);

			$this->status = 'SUCCESS';
			return $data;
		}
	}
?>
