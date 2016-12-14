<?php
/**
 * Keyword - the representation of a keyword
 */

namespace Def\Model;

class Keyword extends ModelAbstract
{
    /**
     * Keyword constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        foreach (['name', 'basic_gain', 'bonus_gain', 'description', 'code'] as $field) {
            $this->values[$field] = $data[$field];
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getFieldValue('name');
    }

    /**
     * @return int
     */
    public function getBasicGain()
    {
        return $this->getFieldValue('basic_gain');
    }

    /**
     * @return int
     */
    public function getBonusGain()
    {
        return $this->getFieldValue('bonus_gain');
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->getFieldValue('description');
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->getFieldValue('code');
    }

    /**
     * Check if keyword is token based
     * @return bool
     */
    public function isTokenKeyword()
    {
        return ($this->getBasicGain() > 0 || $this->getBonusGain() > 0);
    }
}
