<?php
/**
 * Random - random number related functionality
 */

namespace Util;

class Random
{
    /**
     * Pick one or more random entries out of an array using mt_rand()
     * return one or multiple picked entries (returns corresponding keys)
     * @param array $input input array
     * @param int [$num_req] number of picked entries
     * @return mixed
     */
    public static function arrayMtRand(array $input, $amount = 1)
    {
        // validate inputs
        if (count($input) == 0 || count($input) < $amount) {
            return false;
        }

        // case 1: single entry
        if ($amount == 1) {
            $keys = array_keys($input);
            return $keys[mt_rand(0, count($keys) - 1)];
        }
        // case 2: multiple entries
        else {
            $pickedKeys = array();
            $availableKeys = array_keys($input);

            for ($i = 0; $i < $amount; $i++) {
                $pickedKey = mt_rand(0, count($availableKeys) - 1);
                $pickedKeys[] = $availableKeys[$pickedKey];
                unset($availableKeys[$pickedKey]);

                // contract array
                $availableKeys = array_values($availableKeys);
            }

            return $pickedKeys;
        }
    }
}