<?php
/**
 * Input - user input related functionality
 */

namespace Util;

class Input
{
    /**
     * List of supported image upload types
     * @return array
     */
    public static function imageUploadTypes()
    {
        return [
            'image/jpg',
            'image/jpeg',
            'image/gif',
            'image/png'
        ];
    }

    /**
     * Process date input
     * @param string $year
     * @param string $month
     * @param string $day
     * @return string
     */
    public static function checkDateInput($year, $month, $day)
    {
        // spacial 'empty' value
        if ($year == '1000' && $month == '01' && $day == '01') {
            return '';
        }
        // invalid value
        elseif (!(is_numeric($year) && is_numeric($month) && is_numeric($day))) {
            return 'Invalid numeric input';
        }
        // invalid date
        elseif (!checkdate((int)$month, (int)$day, (int)$year)) {
            return 'Invalid date';
        }
        // valid date
        else {
            return '';
        }
    }

    /**
     * Format time difference into hours, minutes and seconds
     * @param int $timeDiff time difference in seconds
     * @return string
     */
    public static function formatTimeDiff($timeDiff)
    {
        $result = array();

        // calculate time components
        $hours = floor($timeDiff / 3600);
        $timeDiff -= $hours * 3600;
        $minutes = floor($timeDiff / 60);
        $timeDiff -= $minutes * 60;
        $seconds = $timeDiff;

        if ($hours > 0) {
            $result[] = $hours . 'h';
        }
        if ($minutes > 0) {
            $result[] = $minutes . 'm';
        }
        if ($seconds > 0) {
            $result[] = $seconds . 's';
        }

        return implode(' ', $result);
    }

    /**
     * @param array $data
     * @param mixed $key
     * @param mixed [$default]
     * @return mixed
     */
    public static function defaultValue(array $data, $key, $default = '')
    {
        return (isset($data[$key])) ? $data[$key] : $default;
    }
}