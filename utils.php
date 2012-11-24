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

	///
	/// Creates a query part of URL from parameter names, values and fragment, which is used to generate internal hyperlinks.
	/// Parameter names and values are sanitized, fragment is not.
	/// First parameter (location) is mandatory and only value is provided.
	/// Optional parameters are always provided as pairs of parameter name and value (there can be arbitrary number of such pairs).
	/// Fragment is optional and expected to be in form '#fragment_name', which should be urlencoded (shoudn't contain any url-special characters).
	/// @param string $location current location value
	/// @return string query part of URL
	function makeurl($location)
	{
		global $session;

		$params = '?location='.urlencode($location); // get location (only mandatory parameter)
		$args = array_slice(func_get_args(), 1); // get other optional parameters

		$fragment = (count($args) % 2 == 1) ? array_pop($args) : ''; // extract fragment, if present

		if ($session AND !$session->hasCookies()) // add session data, if necessary
		{
			$args[] = 'Username';
			$args[] = $session->Username();
			$args[] = 'SessionID';
			$args[] = $session->SessionID();
		}

		// create url from optional parameters (sanitize parameters)
		foreach ($args as $pos => $param) $params.= (($pos % 2 == 0) ? '&' : '=').urlencode($param);

		return $params.$fragment;
	}

	///
	/// Pick one or more random entries out of an array using mt_rand()
	/// @param array $input input array
	/// @param int $num_req (optional) number of picked entries
	/// @return mixed one or multiple picked entries (returns corresponding keys)
	function array_mt_rand(array $input, $num_req = 1)
	{
		// validate inputs
		if (count($input) == 0 or count($input) < $num_req) return false;

		// case 1: single entry
		if ($num_req == 1)
		{
			$keys = array_keys($input);
			return $keys[mt_rand(0, count($keys) - 1)];
		}
		// case 2: multiple entries
		else
		{
			$picked_keys = array();
			$available_keys = array_keys($input);
			for ($i = 0; $i < $num_req; $i++)
			{
				$picked_key = mt_rand(0, count($available_keys) - 1);
				$picked_keys[] = $available_keys[$picked_key];
				unset($available_keys[$picked_key]);

				// contract array
				$available_keys = array_values($available_keys);
			}

			return $picked_keys;
		}
	}

	///////////////////////////////
	/// date and time manipulation

	/**
	 * Returns zone-adjusted and date-formatted time.
	 * NOTE: If using Etc/GMT, see http://bugs.php.net/bug.php?id=34710 !
	 * @param string $time the datetime string (in a strtotime() compatible format)
	 * @param string $zone the time zone string (Etc/UTC and such)
	 * @param string $format the format string for date()
	*/
	function ZoneTime($time, $zone, $format)
	{
		$date = new DateTime($time, new DateTimeZone('UTC'));
		$date->setTimeZone(new DateTimeZone($zone));
		return $date->format($format);
	}
	
	function CheckDateInput($year, $month, $day) // check date input
	{
		if( $year == '0000' and $month == '00' and $day == '00' ) return "";
		elseif (!(is_numeric($year) AND is_numeric($month) AND is_numeric($day))) return "Invalid numeric input";
		elseif (!checkdate((int)$month, (int)$day, (int)$year)) return "Invalid date";
		else return "";
	}

	///
	/// Format time difference into hours, minutes and seconds
	/// @param int $t_diff time difference in seconds
	/// @return string formatted difference
	function format_time_diff($t_diff)
	{
		$t_info = array();

		// calculate time components
		$hours = floor($t_diff / 3600);
		$t_diff-= $hours * 3600;
		$minutes = floor($t_diff / 60);
		$t_diff-= $minutes * 60;
		$seconds = $t_diff;

		if ($hours > 0) $t_info[] = $hours.'h';
		if ($minutes > 0) $t_info[] = $minutes.'m';
		if ($seconds > 0) $t_info[] = $seconds.'s';

		return implode(" ", $t_info);
	}

	////////////////////////
	/// XSL Transformations

	function Numbers($from, $to) // creates comma-separated list of all integer values from interval <$from, $to>
	{
		if ($from <= $to) return implode(",", array_keys(array_fill($from, $to - $from + 1, 0)));
		else return "";
	}

	function array2xml(array $array)
	{
		$text = "";
		foreach($array as $key => $value)
		{
			if( is_numeric($key) )
				$key = "k".$key;

			if( !is_array($value) ) 
				$text .= "<$key>".htmlencode($value)."</$key>"; 
			else
				$text .= "<$key>".array2xml($value)."</$key>";
		}
		return $text;
	}

	function XSLT($xslpath, array $params)
	{
		// set up xslt
		$xsldoc = new DOMDocument();
		$xsldoc->load($xslpath);
		$xsl = new XSLTProcessor();
		$xsl->importStyleSheet($xsldoc);
		$xsl->registerPHPFunctions();

		// set up the params tree
		$tree = '<?xml version="1.0" encoding="UTF-8"?>'.'<params>'.array2xml($params).'</params>';

		// convert tree into xml document
		$xmldoc = new DOMDocument();
		$xmldoc->loadXML($tree, LIBXML_NOERROR);
		if( $xmldoc->hasChildNodes() == FALSE )
		{
			echo 'utils::XSLT : failed to load $params, check if there aren\'t any invalid characters in key names.'."\n\n";
			print_r($params);
			die();
		}
	
		// generate output
		return $xsl->transformToXML($xmldoc);
	}

?>
