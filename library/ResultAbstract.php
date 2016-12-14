<?php
/**
 * AbstractResult
 */

abstract class ResultAbstract implements \ArrayAccess
{
    /**
     * successful result
     */
    const SUCCESS = 1;

    /**
     * either empty result set or no effect
     */
    const NO_EFFECT = 0;

    /**
     * error
     */
    const ERROR = -1;

    /**
     * Result status code
     * @var int
     */
    protected $status;

    /**
     * Result data
     * @var array
     */
    protected $data = array();

    /**
     * @param int $status
     * @param array $data
     */
    public function __construct($status, array $data = array())
    {
        $this->status = $status;
        $this->data = $data;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        }
        else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * Result status
     * @return int
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * Result data
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Is result success
     * @return bool
     */
    public function isSuccess()
    {
        return ($this->status == self::SUCCESS);
    }

    /**
     * Is result no effect
     * @return bool
     */
    public function isNoEffect()
    {
        return ($this->status == self::NO_EFFECT);
    }

    /**
     * Is result error
     * @return bool
     */
    public function isError()
    {
        return ($this->status == self::ERROR);
    }

    /**
     * Is result error or no effect
     * @return bool
     */
    public function isErrorOrNoEffect()
    {
        return in_array($this->status, [self::ERROR, self::NO_EFFECT]);
    }
}
