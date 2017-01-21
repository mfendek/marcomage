<?php
/**
 * Date formatting functions
 */

namespace Util;

class Date
{
    const DATE_ZERO = '1000-01-01';
    const DATETIME_ZERO = '1970-01-01 00:00:01';
    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    const MINUTE = 60;
    const DAY = 24 * 60 * 60;
    const WEEK = 7 * 24 * 60 * 60;

    /**
     * @param int [$datetime]
     * @return string
     */
    public static function timeToStr($datetime = 0)
    {
        // no time was specified - use current time
        if ($datetime == 0) {
            $datetime = time();
        }

        return date(self::DATETIME_FORMAT, $datetime);
    }

    /**
     * @param string [$datetime]
     * @return int
     */
    public static function strToTime($datetime = '')
    {
        // no time was specified - use current time
        if ($datetime == '') {
            $datetime = date(self::DATETIME_FORMAT);
        }

        return ($datetime != self::DATETIME_ZERO) ? strtotime($datetime) : 0;
    }
}