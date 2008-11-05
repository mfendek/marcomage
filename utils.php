<?php
/*
	utils -  various supporting functions
*/
?>
<?php
	/////////////////////
	/// content encoding

	function htmlencode($string) { return htmlspecialchars($string, ENT_COMPAT, 'UTF-8'); }
	function htmldecode($string) { return htmlspecialchars_decode($string, ENT_COMPAT); }
	function postencode($string) { return rawurlencode($string); }
	function postdecode($string) { return rawurldecode($string); }
	function textencode($string) { return str_replace("\n", "\n<br />\n", htmlencode($string)); }

	///////////////////////////////
	/// date and time manipulation

	/// Returns zone-adjusted and date-formatted time.
	/// $time is the unix timestamp, normalized to UTC
	/// $zone is the time zone string (preferably "Etc/GMT+?")
	/// $format is the format string for date()
	/// NOTE: If using Etc/GMT, see http://bugs.php.net/bug.php?id=34710 !
	function ZoneTime($time, $zone, $format)
	{
		$date = new DateTime("@".$time, new DateTimeZone('UTC'));
		$date->setTimeZone(new DateTimeZone($zone));
		return $date->format($format);
	}
	
	function CheckDateInput($year, $month, $day) // check date input
	{
		if (($year == "") OR ($month == "") OR ($day == "")) return "Invalid input";
		elseif (!(is_numeric($year) AND is_numeric($month) AND is_numeric($day))) return "Invalid numeric input";
		elseif (!checkdate((int)$month, (int)$day, (int)$year)) return "Invalid date";
		else return "";
	}

?>
