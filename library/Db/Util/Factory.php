<?php
/**
 * DB util factory
 * Provides resource caching and sugar functions
 */

namespace Db\Util;

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
        $className = '\Db\Util\\'.$resourceName;

        // fetch respective DB config
        $config = $this->getDic()->config();
        $dbConfig = $config['db'][$resourceKey];

        $db = new $className($dbConfig);

        // store object to resource cache for future use
        $this->resources[$resourceKey] = $db;
    }

    /**
     * Load DB util PDO
     * @return Pdo
     */
    public function pdo()
    {
        return $this->loadResource('Pdo');
    }

    /**
     * Load DB util Mongo
     * @return Mongo
     */
    public function mongo()
    {
        return $this->loadResource('Mongo');
    }
}
