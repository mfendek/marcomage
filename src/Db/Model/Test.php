<?php
/**
 * Test - testing and example use
 */

namespace Db\Model;

class Test extends ModelAbstract
{
    /**
     * @return int
     */
    public function getId()
    {
        return $this->getFieldValue('_id');
    }

    /**
     * @return int
     */
    public function getA()
    {
        return $this->getFieldValue('a');
    }

    /**
     * @return string
     */
    public function getB()
    {
        return $this->getFieldValue('b');
    }

    /**
     * @return string
     */
    public function getC()
    {
        return $this->getFieldValue('c');
    }

    /**
     * @return string
     */
    public function getD()
    {
        return $this->getFieldValue('d');
    }

    /**
     * @return string
     */
    public function getE()
    {
        return $this->getFieldValue('e');
    }

    /**
     * @return array
     */
    public function getF()
    {
        return $this->getFieldValue('f');
    }

    /**
     * @return array
     */
    public function getG()
    {
        return $this->getFieldValue('g');
    }

    /**
     * @return string
     */
    public function getH()
    {
        return $this->getFieldValue('h');
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setA($value)
    {
        return $this->setFieldValue('a', $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setB($value)
    {
        return $this->setFieldValue('b', $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setC($value)
    {
        return $this->setFieldValue('c', $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setD($value)
    {
        return $this->setFieldValue('d', $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setE($value)
    {
        return $this->setFieldValue('e', $value);
    }

    /**
     * @param array $value
     * @return $this
     */
    public function setF(array $value)
    {
        return $this->setFieldValue('f', $value);
    }

    /**
     * @param array $value
     * @return $this
     */
    public function setG(array $value)
    {
        return $this->setFieldValue('g', $value);
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setH($value)
    {
        return $this->setFieldValue('h', $value);
    }
}
