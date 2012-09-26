<?php
class CDatabase // encapsulated MySQL database access (PDO)
{
	private $db = false;
	public $status = 'ERROR_DB_OFFLINE';
	public $queries = 0; // counter
	public $qtime = 0; // time spent
	public $log = array(); // query log

	public function __construct($server, $username, $password, $database)
	{
		$dsn = "mysql:host=$server;dbname=$database";

		$options = array(
			PDO::ATTR_PERSISTENT => true,
			PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
		);

		try
		{ $db = new PDO($dsn, $username, $password, $options); }
		catch (PDOException $e)
		{ $this->status = $e->getMessage(); return; }

		$this->db = $db;
		$this->status = 'SUCCESS';
		return;
	}

	public function LastID()
	{
		return $this->db->lastInsertId();
	}

	public function txnBegin()
	{
		return $this->db->beginTransaction();
	}

	public function txnRollBack()
	{
		return $this->db->rollBack();
	}

	public function txnCommit()
	{
		return $this->db->commit();
	}

	/// Executes database query with provided parameter values.
	/// @param string $query database query
	/// @param array $params parameter values in the order they appear in the query
	/// @return array result data if the operation succeeds (empty array in case of non-SELECT statements), false if it fails
	public function Query($query, array $params = array())
	{
		if( !$this->db ) { $this->status = 'ERROR_DB_OFFLINE'; return false; };

		$t_start = microtime(TRUE);

		try
		{ $statement = $this->db->prepare($query); }
		catch (PDOException $e)
		{ $this->status = 'ERROR_QUERY: '.$e->getMessage(); return false; }

		$result = $statement->execute($params);
		if( !$result ) { $this->status = 'ERROR_QUERY: '.implode(" ", $statement->errorInfo()); return false; }

		$t_end = microtime(TRUE);

		$this->queries++;
		$this->qtime += $t_end - $t_start;
		$this->log[] = sprintf("[%.2f ms] %s", round(1000*($t_end - $t_start),2), $query);
		$this->status = 'SUCCESS';

		// get result data
		$data = $statement->fetchAll(PDO::FETCH_ASSOC);

		// free statement object
		$statement = null;

		return $data;
	}

	/// Executes prepared database query multiple times with provided parameter values.
	/// Each run of query has its own set of parameters.
	/// All queries are executed as a single transaction.
	/// @param string $query database query
	/// @param array $params sequence of sets of parameter values in the order they appear in the query
	/// @return array result data for each query if the operation succeeds, false if it fails
	public function MultiQuery($query, array $params)
	{
		if( !$this->db ) { $this->status = 'ERROR_DB_OFFLINE'; return false; }
		if( count($params) == 0 ) { $this->status = 'ERROR_PARAMS_EMPTY'; return false; }

		$t_start = microtime(TRUE);

		try
		{ $statement = $this->db->prepare($query); }
		catch (PDOException $e)
		{ $this->status = 'ERROR_QUERY: '.$e->getMessage(); return false; }

		$this->txnBegin();

		$data = array();
		foreach ($params as $key => $param_set)
		{
			$result = $statement->execute($param_set);
			if( !$result )
			{
				$this->status = 'ERROR_QUERY: '.implode(" ", $statement->errorInfo());
				$this->txnRollBack();

				return false;
			}

			// store result data
			$data[$key] = $statement->fetchAll(PDO::FETCH_ASSOC);
		}

		$this->txnCommit();

		$t_end = microtime(TRUE);

		$this->queries++;
		$this->qtime += $t_end - $t_start;
		$this->log[] = sprintf("[%.2f ms] %s", round(1000*($t_end - $t_start),2), $query);
		$this->status = 'SUCCESS';

		// free statement object
		$statement = null;

		return $data;
	}
};
?>
