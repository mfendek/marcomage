<?php
/**
 * Middleware factory
 * Provides resource caching and sugar functions
 */

namespace Middleware;

use Util\Rename;

class Factory extends \FactoryAbstract
{
    /**
     * Create resource of specified name
     * @param string $resourceName
     */
    protected function createResource($resourceName)
    {
        // determine config key name
        $resourceKey = strtolower($resourceName);

        // add class name prefix
        $className = '\Middleware\\'. Rename::underscoreToClassName($resourceName);

        $service = new $className($this->getDic());

        // store service to resource cache for future use
        $this->resources[$resourceKey] = $service;
    }

    /**
     * @param string $name
     * @return MiddlewareAbstract
     */
    public function loadMiddleware($name)
    {
        return $this->loadResource($name);
    }
}
