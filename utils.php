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

	////////////////////////
	/// XSL Transformations

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

	function Subsections() // returns sections-subsections relation
	{
		$sections = array();

		$sections['Page'] = array('Page');
		$sections['Forum'] = array('Forum', 'Forum_search', 'Section_details', 'New_thread', 'Thread_details', 'New_post', 'Edit_post', 'Edit_thread');
		$sections['Messages'] = array('Messages', 'Message_details', 'Message_new');
		$sections['Players'] = array('Players', 'Profile');
		$sections['Games'] = array('Games', 'Game', 'Deck_view', 'Game_note');
		$sections['Decks'] = array('Decks', 'Deck_edit');
		$sections['Concepts'] = array('Concepts', 'Concepts_new', 'Concepts_edit', 'Concepts_details');
		$sections['Cards'] = array('Cards', 'Cards_details');
		$sections['Replays'] = array('Replays', 'Replay');
		$sections['Novels'] = array('Novels');
		$sections['Settings'] = array('Settings');

		return $sections;
	}

	function SectionsList() { return array_keys(Subsections()); } // returns list of all sections

	function NavBarSection($current) // calculates current section for navigation bar
	{
		$sections = Subsections();

		// try to match current state with subsection, return section name when match was found
		foreach ($sections as $section => $subsections) if (array_search($current, $subsections) !== FALSE) return $section;

		return false; // no match was found (should never happen)
	}

?>
