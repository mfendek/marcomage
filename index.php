<?php
	// redirect errors to our own logfile
	error_reporting(-1); // everything
	ini_set("display_errors", "Off");
	ini_set("error_log", "logs/arcomage-error-".strftime('%Y%m%d').".log");

	// activate output buffering and optional compression
	if ( stristr(@$_SERVER["HTTP_ACCEPT_ENCODING"],"gzip") && extension_loaded('zlib') && ini_get('zlib.output_compression') == 0 )
		ob_start('ob_gzhandler', 16384);
	else
		ob_start();

	// enable xhtml+xml mode if the client supports it
	if ( stristr(@$_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") )
		header("Content-type: application/xhtml+xml");
	else
		header("Content-type: text/html");

	require_once("main.php");

	ob_end_flush();
?>
