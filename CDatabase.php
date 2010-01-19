<?php
/*
	CDatabase - encapsulated MySQL database access (with CResult)
*/
?>
<?php
	class CDatabase
	{
		private $db = false;
		public $status = 'ERROR_DB_OFFLINE';
		public $queries = 0; // counter
		public $qtime = 0; // time spent
		
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
		
		public function Query($query)
		{
			if (!$this->db) { $this->status = 'ERROR_DB_OFFLINE'; return false; };
			
			$t_start = microtime(TRUE);
			$result = mysql_query($query, $this->db);
			$t_end = microtime(TRUE);
			if (!$result) { $this->status = 'ERROR_MYSQL_QUERY: '.mysql_error($this->db); return false; };
			
			$this->queries++;
			$this->qtime += $t_end - $t_start;
			$this->status = 'SUCCESS';
			return new CResult($result);
		}
		
		public function LastID()
		{
			return mysql_insert_id();
		}
		
	}


	class CResult
	{
		private $result = false;
		private $cursor = 0;
		private $rows = 0;
		public $status = 'ERROR_NO_DATA';
		
		public function __construct($result)
		{
			if (!$result) { $this->status = 'ERROR_NO_DATA'; return; };
			
			if (!is_resource($result)) { $this->status = 'ERROR_NO_DATA'; return; };
			
			$this->rows = mysql_num_rows($result);
			$this->result = $result;
			$this->status = 'SUCCESS';
			return;
		}
		
		public function __destruct()
		{
			$this->Free();
		}
		
		public function Next()
		{
			if (!$this->result) { $this->status = 'ERROR_NO_DATA'; return false; };
			
			if ($this->cursor >= $this->rows) { $this->status = 'ERROR_NO_MORE_DATA'; return false; };
			
			$data = mysql_fetch_assoc($this->result);
			if (!$data) { $this->status = 'ERROR_NO_MORE_DATA'; return false; };
			
			$this->cursor++;
			$this->status = 'SUCCESS';
			return $data;
		}
		
		public function Free()
		{
			if (!$this->result) { $this->status = 'ERROR_NO_DATA'; return false; };
			
			$status = mysql_free_result($this->result);
			if (!$status) { $this->status = 'ERROR_MYSQL_FREE'; return false; };
			
			$this->result = false;
			$this->cursor = 0;
			$this->status = 'ERROR_NO_DATA';
			return true;
		}
		
		public function Rows()
		{
			return $this->rows;
		}
		
	}
?>
