<?php
/**
 * Mongo utility wrapper
 * Provides low level Mongo native services
 */

namespace Db\Util;

use ArcomageException as Exception;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use Util\Ip;

class Mongo extends UtilAbstract
{
    /**
     * @param array $objectList
     * @return array
     */
    private function listToArray(array $objectList)
    {
        $result = array();
        foreach ($objectList as $object) {
            $result[] = $this->objectToArray($object);
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    protected function init()
    {
        $db = null;
        $errorMessage = '';

        $config = \Dic::config();
        $dbConfig = $config['db']['mongo'];

        // add more hosts to seed list (used for sharded cluster)
        $additionalHosts = (!empty($dbConfig['additional_hosts'])) ? ',' . $dbConfig['additional_hosts'] : '';

        // prepare auth string
        $mongoAuth = ($this->username != '' && $this->password != '') ? $this->username . ':' . $this->password . '@' : '';

        // main host
        $host = $this->server . (($this->port != '') ? ':' . $this->port : '');

        // format mongodb://[username:password@]host1[:port1][,host2[:port2:],...]/db
        $mongoConfig = 'mongodb://' . $mongoAuth . $host . $additionalHosts . '/' . $this->database;

        // attempt to log in
        try {
            $options = [
                'journal' => true
            ];

            // add replica set name (used only with single replica set (unused when using sharded cluster))
            $replicaSet = $dbConfig['replica_set'];

            // add replica set option if specified
            if ($replicaSet != '') {
                $options['replicaSet'] = $replicaSet;
            }

            $db = new Manager($mongoConfig, $options);
        }
        catch (\MongoDB\Driver\Exception\Exception $e) {
            $errorMessage = 'failed to create MongoDB ' . $e->getMessage();
            $this->logError(self::STATUS_OFFLINE_INIT, $errorMessage);
        }

        if ($this->status != self::STATUS_OK) {
            // add extra debug for internal IPs
            $extraDebug = (Ip::isInternalIp()) ? ' ' . $this->status.' ' . $errorMessage : '';

            throw new Exception('Unable to connect to DB (via Mongo), aborting.' . $extraDebug);
        }

        $this->resource = $db;
    }

    /**
     * Return raw initialized DB resource
     * @return Manager
     */
    public function db()
    {
        $this->loadDb();

        return $this->resource;
    }

    /**
     * @param mixed $object
     * @return array
     */
    public function objectToArray($object)
    {
        $data = array();
        foreach ($object as $key => $value) {
            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * @param array $input
     * @return Result
     */
    public function command(array $input)
    {
        $db = $this->db();
        $this->markQuestionStart();

        $question = 'command: ' . print_r($input, true);

        $command = new Command($input);

        try {
            $result = $db->executeCommand($this->database, $command);
        }
        catch (\MongoDB\Driver\Exception\Exception $e) {
            $this->logError(self::STATUS_QUESTION_E, $e->getMessage(), $question);
            return new Result(Result::ERROR);
        }

        $this->markQuestionEnd($question);

        $result = $this->listToArray($result->toArray());

        return new Result(Result::SUCCESS, $result[0]);
    }

    /**
     * @param string $collection
     * @param array $filter
     * @param array [$options]
     * @return Result
     */
    public function read($collection, array $filter, array $options = [])
    {
        $db = $this->db();
        $this->markQuestionStart();

        $question = 'read: ' . $collection . ' ' . print_r($filter, true) . ' ' . print_r($options, true);

        $query = new Query($filter, $options);

        try {
            $result = $db->executeQuery($this->database . '.' . $collection, $query);
        }
        catch (\MongoDB\Driver\Exception\Exception $e) {
            $this->logError(self::STATUS_QUESTION_E, $e->getMessage(), $question);
            return new Result(Result::ERROR);
        }

        $this->markQuestionEnd($question);

        return new Result(Result::SUCCESS, $this->listToArray($result->toArray()));
    }

    /**
     * @param string $collection
     * @param array $operations
     * @return Result
     */
    public function write($collection, array $operations)
    {
        $db = $this->db();
        $this->markQuestionStart();

        $question = 'write: ' . $collection . ' ' . print_r($operations, true);

        $bulk = new BulkWrite();

        try {
            foreach ($operations as $type => $operationList) {
                foreach ($operationList as $data) {
                    // case 1: insert
                    if ($type == 'insert') {
                        $bulk->insert($data);
                    }
                    // case 2: update
                    elseif ($type == 'update') {
                        $bulk->update($data[0], (isset($data[1])) ? $data[1] : [], (isset($data[2])) ? $data[2] : []);
                    }
                    // case 2: delete
                    elseif ($type == 'delete') {
                        $bulk->delete($data[0], (isset($data[1])) ? $data[1] : []);
                    }
                }
            }

            $result = $db->executeBulkWrite($this->database . '.' . $collection, $bulk);

            $this->effectedRows = 0;
            $this->effectedRows+= $result->getInsertedCount();
            $this->effectedRows+= $result->getUpsertedCount();
            $this->effectedRows+= $result->getModifiedCount();
            $this->effectedRows+= $result->getDeletedCount();
        }
        catch (\MongoDB\Driver\Exception\Exception $e) {
            $this->logError(self::STATUS_QUESTION_E, $e->getMessage(), $question);
            return new Result(Result::ERROR);
        }

        $this->markQuestionEnd($question);

        // operation had no effect
        if ($this->effectedRows() == 0) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS);
    }
}
