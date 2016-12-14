<?php
/**
 * Rename transformations (class names, entity names...)
 */

namespace Util;

class Rename
{
    /**
     * Convert underscored string to class name format
     * @param string $name
     * @return string
     */
    public static function underscoreToClassName($name)
    {
        $name = explode('_', $name);
        $className = array();
        foreach ($name as $segment) {
            $className[] = ucfirst($segment);
        }

        return implode('', $className);
    }

    /**
     * Convert input name string to action name format
     * @param string $name
     * @return string
     */
    public static function inputToActionName($name)
    {
        $name = explode('_', $name);
        $className = array();
        foreach ($name as $key => $segment) {
            $className[] = ($key == 0) ? lcfirst($segment) : ucfirst($segment);
        }

        return implode('', $className);
    }
}