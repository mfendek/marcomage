<?php
/**
 * Def entity factory
 * Provides resource caching and sugar functions
 */

namespace Def\Entity;

class Factory extends \FactoryAbstract
{
    /**
     * Create DB util class of specified name
     * @param string $resourceName (<db>_<class>)
     */
    protected function createResource($resourceName)
    {
        // determine config key name
        $resourceKey = strtolower($resourceName);

        // decode class name
        $resourceName = explode('_', $resourceName);
        $dbType = $resourceName[0];
        $className = $resourceName[1];

        // add class name prefix
        $className = '\Def\Entity\\'.$dbType.$className;

        $db = new $className();

        // store object to resource cache for future use
        $this->resources[$resourceKey] = $db;
    }

    /**
     * @return XmlAward
     */
    public function award()
    {
        return $this->loadResource('Xml_Award');
    }

    /**
     * @return XmlCard
     */
    public function card()
    {
        return $this->loadResource('Xml_Card');
    }

    /**
     * @return XmlCardTest
     */
    public function cardTest()
    {
        return $this->loadResource('Xml_CardTest');
    }

    /**
     * @return XmlChallenge
     */
    public function challenge()
    {
        return $this->loadResource('Xml_Challenge');
    }

    /**
     * @return XmlKeyword
     */
    public function keyword()
    {
        return $this->loadResource('Xml_Keyword');
    }

    /**
     * @return XmlKeywordTest
     */
    public function keywordTest()
    {
        return $this->loadResource('Xml_KeywordTest');
    }
}
