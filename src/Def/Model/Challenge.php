<?php
/**
 * Challenge - AI challenge configuration
 */

namespace Def\Model;

class Challenge extends ModelAbstract
{
    /**
     * Challenge constructor.
     * @param $name
     * @param array $init
     * @param array $config
     */
    public function __construct($name, array $init, array $config)
    {
        $this->values['name'] = $name;
        $this->values['init'] = $init;
        $this->values['config'] = $config;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getFieldValue('name');
    }

    /**
     * @return array
     */
    public function getInit()
    {
        return $this->getFieldValue('init');
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->getFieldValue('config');
    }
}
