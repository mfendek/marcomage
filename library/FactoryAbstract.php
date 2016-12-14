<?php
/**
 * Abstract factory
 * Provides generic resource caching
 */

abstract class FactoryAbstract
{
    /**
     * DIC reference
     * @var \Dic
     */
    protected $dic;

    /**
     * Resource cache
     * @var array
     */
    protected $resources = array();

    /**
     * @param \Dic $dic
     */
    public function __construct(\Dic $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Create resource of specified name
     * @param string $resourceName
     */
    abstract protected function createResource($resourceName);

    /**
     * @return \Dic
     */
    protected function getDic()
    {
        return $this->dic;
    }

    /**
     * Load class of specified name
     * @param string $className
     * @return mixed
     */
    protected function loadResource($className)
    {
        // determine resource key name
        $resourceKey = strtolower($className);

        // check resource cache first, initialize when necessary
        if (!isset($this->resources[$resourceKey])) {
            $this->createResource($className);
        }

        return $this->resources[$resourceKey];
    }
}
