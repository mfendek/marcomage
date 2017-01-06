<?php
/**
 * DB entity factory
 * Provides resource caching, DB type configuration and sugar functions
 */

namespace Db\Entity;

use ArcomageException as Exception;

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
        $className = '\Db\Entity\\'.$dbType.$className;

        // fetch respective DB config
        $config = $this->getDic()->config();
        $dbTypeKey = strtolower($dbType);
        $dbConfig = (isset($config['db'][$dbTypeKey]['entity'])) ? $config['db'][$dbTypeKey]['entity'] : array();

        $db = new $className($this->getDic()->dbUtilFactory()->loadResource($dbType), $dbConfig);

        // store object to resource cache for future use
        $this->resources[$resourceKey] = $db;
    }

    /**
     * Save checkpoint on all models
     */
    public function saveCheckpoint()
    {
        /* @var \Db\Entity\EntityAbstract $entity */
        foreach ($this->resources as $entity) {
            /* @var \Db\Model\ModelAbstract $model */
            foreach ($entity->models() as $model) {
                $model->checkpoint();
            }
        }
    }

    /**
     * Restore all models to checkpoint
     * does not revert newly created models
     */
    public function restoreCheckpoint()
    {
        /* @var \Db\Entity\EntityAbstract $entity */
        foreach ($this->resources as $entity) {
            /* @var \Db\Model\ModelAbstract $model */
            foreach ($entity->models() as $model) {
                $model->rollback();
            }
        }
    }

    /**
     * Sync all models to DB
     * @throws \Exception
     */
    public function dbSync()
    {
        // group entities by DB type
        $entities = array();
        foreach ($this->resources as $key => $entity) {
            // <db>_<class>
            $key = explode('_', $key);
            $entities[$key[0]][$key[1]] = $entity;
        }

        // PDO entities - save all models wrapped in transaction (rollback if necessary)
        if (!empty($entities['pdo'])) {
            $dbPdo = $this->getDic()->dbUtilFactory()->pdo();
            $dbPdo->beginTransaction();

            /* @var \Db\Entity\EntityAbstract $entity */
            foreach ($entities['pdo'] as $entity) {
                /* @var \Db\Model\ModelAbstract $model */
                foreach ($entity->models() as $model) {
                    if (!$model->save()) {
                        $dbPdo->rollBack();
                        throw new Exception('failed to save model ('.$entity->entityName().') to DB (Pdo) '.print_r($model->toArray(), true));
                    }
                }
            }

            $dbPdo->commit();
        }
    }

    /**
     * @return PdoChat
     */
    public function chat()
    {
        return $this->loadResource('Pdo_Chat');
    }

    /**
     * @return PdoConcept
     */
    public function concept()
    {
        return $this->loadResource('Pdo_Concept');
    }

    /**
     * @return PdoDeck
     */
    public function deck()
    {
        return $this->loadResource('Pdo_Deck');
    }

    /**
     * @return PdoForumSection
     */
    public function forumSection()
    {
        return $this->loadResource('Pdo_ForumSection');
    }

    /**
     * @return PdoForumThread
     */
    public function forumThread()
    {
        return $this->loadResource('Pdo_ForumThread');
    }

    /**
     * @return PdoForumPost
     */
    public function forumPost()
    {
        return $this->loadResource('Pdo_ForumPost');
    }

    /**
     * @return PdoMessage
     */
    public function message()
    {
        return $this->loadResource('Pdo_Message');
    }

    /**
     * @return PdoPlayer
     */
    public function player()
    {
        return $this->loadResource('Pdo_Player');
    }

    /**
     * @return PdoReplay
     */
    public function replay()
    {
        return $this->loadResource('Pdo_Replay');
    }

    /**
     * @return PdoScore
     */
    public function score()
    {
        return $this->loadResource('Pdo_Score');
    }

    /**
     * @return PdoSetting
     */
    public function setting()
    {
        return $this->loadResource('Pdo_Setting');
    }

    /**
     * @return PdoStatistic
     */
    public function statistic()
    {
        return $this->loadResource('Pdo_Statistic');
    }

    /**
     * @return PdoGame
     */
    public function game()
    {
        return $this->loadResource('Pdo_Game');
    }

    /**
     * @return MongoAutoIncrement
     */
    public function autoIncrement()
    {
        return $this->loadResource('Mongo_AutoIncrement');
    }

    /**
     * @return MongoTest
     */
    public function test()
    {
        return $this->loadResource('Mongo_Test');
    }
}
