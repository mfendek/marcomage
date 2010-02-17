<?php
/*
	parse_utils -  functions for parsing a post or message
*/
?>
<?php

	require_once('stringparser_bbcode.class.php');
	define('BBCODE_URL_MAXLEN', 40); // Maximum length of a URL before it gets abbreviated (superficially)
	
/* Do some rudimentary cleaning of a user-supplied URL. Makes sure the resource
type it's prefixed with is valid (and if it's not, or none is supplied, just 
prepend the URL with http://). Escape bad characters.
*/
function clean_url($url) {
		$valid_prefixes = array('http', 'https', 'ftp');
		$prefixed = false;
		foreach ($valid_prefixes as $prefix) {
			$prefixed = ($prefixed || ( strpos( strtolower($url), $prefix) === 0));
		}
		$url = ($prefixed) ? $url : 'http://'.$url;

		return htmlspecialchars($url);
}

/* Callback for creating a hyperlink in BBCode */
function do_bbcode_url ($action, $attributes, $content, $params, &$node_object) {
    if ($action == 'validate') {
        return true;
    }
    // the code was specified as follows: [url]http://.../[/url]
    if (!isset ($attributes['default'])) {
    	$content = clean_url($content);
    	if (($l = strlen($content))>BBCODE_URL_MAXLEN) {
      	$t = (int)(BBCODE_URL_MAXLEN/2);
      	// Wedge an ellipsis in
        $inner = substr($content, 0, $t) . '&hellip;' . substr($content, $l-$t);
      } 
			else {
      	$inner = $content;
            }
      return '<a href="'.$content.'">'.$inner.'</a>';
    }
    // the code was specified as follows: [url=http://.../]Text[/url]
    return '<a href="'.clean_url($attributes['default']).'">'.$content.'</a>';
    
}

function do_bbcode_quote($action, $attributes, $content, $params, &$node_object) {
	if ($action == 'validate') {
		return true;
	}
	$start = "<blockquote><div>";
	$end = "</div></blockquote>";
	if (isset ($attributes['author'])) {
	  $start = $start."<cite>".$attributes['author']." wrote:</cite><br/>";
	}
	else if (isset ($attributes['default'])) {
		$start = $start."<cite>".$attributes['default']." wrote:</cite><br/>";
	}
	else {
  	$start = $start."<br/>";
  }
	return $start.$content.$end;
}

/* Parse some BBCode until HTML. The extended flag is a simple way of partitioning
the available BBCode flags. Maybe we want access to limited BBCode (bold, italics)
for card concepts and messages, and want to reserve the others (hyperlinks, or
quoting) for just the forums? Just an idea. 
*/
	function parse_post($content, $extended = false) {
		// This is the best place to take care of escaping HTML injection stuff. We
		// have to explicitly not escape in our xslt.
		$content = htmlspecialchars($content, ENT_COMPAT, 'UTF-8');
		$content = str_replace("\n", "<br/>", $content);
		$bbcode = new StringParser_BBCode ();
		$bbcode->addCode ('b', 'simple_replace', null, 
				array ('start_tag' => '<b>', 'end_tag' => '</b>'), 
				'inline', array ('block', 'inline'), array ());
    $bbcode->addCode ('i', 'simple_replace', null, 
				array ('start_tag' => '<i>', 'end_tag' => '</i>'),
        'inline', array ('block', 'inline'), array ());
    if ($extended) {
			$bbcode->addCode ('url', 'usecontent?', 'do_bbcode_url', array ('usecontent_param' => 'default'),
                  'link', array ('block', 'inline'), array ('link'));
      $bbcode->addCode ('quote', 'callback-replace', 'do_bbcode_quote', 
				array ('usecontent_param' => 'default'), 
				'inline', array ('block', 'inline'), array ());
			//$bbcode->setOccurrenceType ('quote', 'quote');
			/* We don't want quotes to nest too deeply, or it looks like a mess and is
			unreadable. 6 is a reasonable limit, in terms of appearance and in terms
			of what the maximum practical usage would be */
			//$bbcode->setMaxOccurrences ('quote', 6);
    }
    return $bbcode->parse($content);
	}