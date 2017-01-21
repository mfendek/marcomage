<?php
/**
 * Xslt - XSLT related functionality
 */

namespace Util;

use ArcomageException as Exception;

class Xslt
{
    /**
     * @param array $array
     * @return string
     */
    private static function array2xml(array $array)
    {
        $text = '';
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $key = 'k' . $key;
            }

            if (!is_array($value)) {
                $text .= '<' . $key . '>' . Encode::htmlEncode($value) . '</' . $key . '>';
            }
            else {
                $text .= '<' . $key . '>' . self::array2xml($value) . '</' . $key . '>';
            }
        }

        return $text;
    }

    /**
     * Construct the query part of an URL from the given parameters
     * Includes username/session-id information if cookies are disabled
     * has variable number of optional params provided in pairs
     * key[i] GET parameter name
     * val[i] GET parameter value
     * @param string $location current section name
     * @return string
     */
    public static function makeUrl($location)
    {
        $player = \Dic::getPlayer();

        // get optional parameters
        $args = array_slice(func_get_args(), 1);

        // add session data, if necessary
        if (!$player->isGuest() && $player->getSessionId() > 0 && !$player->hasCookies()) {
            $args[] = 'username';
            $args[] = $player->getUsername();
            $args[] = 'session_id';
            $args[] = $player->getSessionId();
        }

        // write location
        $params = '?location=' . urlencode($location);

        // write optional key/value pairs
        for ($i = 0; $i < count($args); $i += 2) {
            $key = $args[$i];
            $val = $args[$i + 1];
            if ($key === '' && $val === '') {
                // skip blank fields
                continue;
            }
            $params.= '&' . urlencode($key) . '=' . urlencode($val);
        }

        return $params;
    }

    /**
     * Returns zone-adjusted and date-formatted time.
     * NOTE: If using Etc/GMT, see http://bugs.php.net/bug.php?id=34710 !
     * @param string $time the datetime string (in a strtotime() compatible format)
     * @param string $zone the time zone string (Etc/UTC and such)
     * @param string [$format] the format string for date()
     * @return string
     */
    public static function zoneTime($time, $zone, $format = Date::DATETIME_FORMAT)
    {
        $date = new \DateTime($time, new \DateTimeZone('UTC'));
        $date->setTimezone(new \DateTimeZone($zone));
        return $date->format($format);
    }

    /**
     * Creates comma-separated list of all integer values from interval <$from, $to>
     * @param int $from
     * @param int $to
     * @return string
     */
    public static function numbers($from, $to)
    {
        if ($from <= $to) {
            return implode(',', array_keys(array_fill($from, $to - $from + 1, 0)));
        }
        else {
            return '';
        }
    }

    /**
     * @param string $xslPath
     * @param array $params
     * @throws Exception
     * @return string
     */
    public static function transform($xslPath, array $params)
    {
        // set up xslt
        $xslDoc = new \DOMDocument();
        $xslDoc->load($xslPath);
        $xsl = new \XSLTProcessor();
        $xsl->importStylesheet($xslDoc);
        $xsl->registerPHPFunctions();

        // set up the params tree
        $tree = '<?xml version="1.0" encoding="UTF-8"?>' . '<params>' . self::array2xml($params) . '</params>';

        // convert tree into xml document
        $xmlDoc = new \DOMDocument();
        $xmlDoc->loadXML($tree, LIBXML_NOERROR);
        if ($xmlDoc->hasChildNodes() == false) {
            throw new Exception(
                "XSLT transform: failed to load params, check if there aren't any invalid characters in key names." . "\n\n"
                . print_r($params, true)
            );
        }

        // generate output
        return $xsl->transformToXml($xmlDoc);
    }
}