<?php
	// redirect errors to our own logfile
	error_reporting(-1); // everything
//	ini_set("display_errors", "Off");
	ini_set("error_log", "logs/arcomage-error-".strftime('%Y%m%d').".log");

	require_once("main.php");
?>
