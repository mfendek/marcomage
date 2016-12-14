<?php
/**
 * IP handling utilities
 */

namespace Util;

class Ip
{
    /**
     * Internal IPs list
     * @var array
     */
    protected static $internalIps = [
    ];

    /**
     * Remove IPv6 prefix from IP
     * @param string $ip
     * @return string
     */
    private static function delIPv6Prefix($ip)
    {
        return str_replace('::ffff:', '', $ip);
    }

    /**
     * Determine current IP (takes into account request forwarding)
     * @return string
     */
    public static function getIp()
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR'
        ];

        foreach ($keys as $key) {
            if (!empty(\Dic::server()[$key])) {
                $ip = \Dic::server()[$key];

                // request has been forwarded - extract first IP from the list
                if ($key == 'HTTP_X_FORWARDED_FOR') {
                    if (strpos($ip, ',') !== false) {
                        $ip = explode(',', $ip);
                        $ip = array_shift($ip);
                    }
                }

                return self::delIPv6Prefix($ip);
            }
        }

        return '';
    }

    /**
     * Determine if provided IP is internal
     * @param string [$ip] defaults to current IP
     * @return bool
     */
    public static function isInternalIp($ip = null)
    {
        // if no IP is provided use current IP
        if (!$ip) {
            $ip = self::getIp();
        }

        // localhost is treated as internal IP for the sake of improved debug data
        if (strpos($ip, '127.0.0.1') !== false) {
            return true;
        }

        return in_array($ip, self::$internalIps);
    }
}