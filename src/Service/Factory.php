<?php
/**
 * Service factory
 * Provides resource caching and sugar functions
 */

namespace Service;

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
        $className = '\Service\\'.$resourceName;

        $service = new $className($this->getDic());

        // store service to resource cache for future use
        $this->resources[$resourceKey] = $service;
    }

    /**
     * @return Concept
     */
    public function concept()
    {
        return $this->loadResource('Concept');
    }

    /**
     * @return Deck
     */
    public function deck()
    {
        return $this->loadResource('Deck');
    }

    /**
     * @return Forum
     */
    public function forum()
    {
        return $this->loadResource('Forum');
    }

    /**
     * @return Player
     */
    public function player()
    {
        return $this->loadResource('Player');
    }

    /**
     * @return Statistic
     */
    public function statistic()
    {
        return $this->loadResource('Statistic');
    }

    /**
     * @return GameUtil
     */
    public function gameUtil()
    {
        return $this->loadResource('GameUtil');
    }

    /**
     * @return GameAward
     */
    public function gameAward()
    {
        return $this->loadResource('GameAward');
    }

    /**
     * @return GameCheat
     */
    public function gameCheat()
    {
        return $this->loadResource('GameCheat');
    }

    /**
     * @return GameManagement
     */
    public function gameManagement()
    {
        return $this->loadResource('GameManagement');
    }

    /**
     * @return GameTurn
     */
    public function gameTurn()
    {
        return $this->loadResource('GameTurn');
    }

    /**
     * @return GameUseCard
     */
    public function gameUseCard()
    {
        return $this->loadResource('GameUseCard');
    }

    /**
     * @return GameTest
     */
    public function gameTest()
    {
        return $this->loadResource('GameTest');
    }

    /**
     * @return GameAi
     */
    public function gameAi()
    {
        return $this->loadResource('GameAi');
    }
}
