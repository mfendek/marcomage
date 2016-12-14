<?php
/**
 * Abstract Definition model
 */

namespace Def\Model;

abstract class ModelAbstract
{
    /**
     * Values of fields
     * @var array
     */
    protected $values = array();

    /**
     * Get specified field value
     * @param string $field
     * @return mixed
     */
    protected function getFieldValue($field)
    {
        return $this->values[$field];
    }
}
