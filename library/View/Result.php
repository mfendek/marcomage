<?php
/**
 * View data result
 */

namespace View;

class Result
{
    /**
     * Result data
     * @var array
     */
    protected $data = array();

    /**
     * Subsection name
     * @var string
     */
    protected $subsection = '';

    /**
     * Result constructor.
     * @param array [$data]
     * @param string [$subsection]
     */
    public function __construct(array $data = [], $subsection = '')
    {
        $this->data = $data;
        $this->subsection = $subsection;
    }

    /**
     * @return string
     */
    public function subsection()
    {
        return $this->subsection;
    }

    /**
     * @return array
     */
    public function data()
    {
        return $this->data;
    }
}
