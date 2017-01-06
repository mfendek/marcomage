<?php
/**
 * Abstract DB entity Mongo (mapper and DAO wrapper)
 * Provides common functionality specific for Mongo DB util
 */

namespace Db\Entity;

use Db\Util\Mongo;
use Db\Util\Result;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use Util\Date;

abstract class MongoAbstract extends EntityAbstract
{
    /**
     * Format data to MongoDB format
     * @param mixed $value data value
     * @param string $type data type
     * @return mixed formatted data
     */
    private function dataToMongo($value, $type)
    {
        // do not format data in this case
        if ($type == '') {
            return $value;
        }

        // case 1: mongo id
        if ($type == EntityAbstract::TYPE_MONGO_ID) {
            $result = new ObjectID($value);
        }
        // case 2: date
        elseif ($type == EntityAbstract::TYPE_DATE) {
            $result = new UTCDateTime(Date::strToTime($value) * 1000);
        }
        // case 3: datetime
        elseif ($type == EntityAbstract::TYPE_DATETIME) {
            $result = new UTCDateTime(Date::strToTime($value) * 1000);
        }
        // case 4: binary
        elseif ($type == EntityAbstract::TYPE_BINARY) {
            $result = new Binary($value, Binary::TYPE_GENERIC);
        }
        // case 5: integer
        elseif ($type == EntityAbstract::TYPE_INT) {
            $result = (int)$value;
        }
        // case 6: string
        else {
            $result = (string)$value;
        }

        return $result;
    }

    /**
     * Format data from MongoDB format
     * @param mixed $value data value
     * @param string $type data type
     * @return mixed formatted data
     */
    private function dataFromMongo($value, $type)
    {
        // case 1: mongo id
        if ($type == EntityAbstract::TYPE_MONGO_ID) {
            $result = (string)$value;
        }
        // case 2: date
        elseif ($type == EntityAbstract::TYPE_DATE) {
            $result = $value->toDateTime()->format('Y-m-d');
        }
        // case 3: datetime
        elseif ($type == EntityAbstract::TYPE_DATETIME) {
            $result = $value->toDateTime()->format('Y-m-d H:i:s');
        }
        // case 4: binary
        elseif ($type == EntityAbstract::TYPE_BINARY) {
            $result = $value->getData();
        }
        // case 5: integer
        elseif ($type == EntityAbstract::TYPE_INT) {
            $result = (int)$value;
        }
        // case 6: string
        else {
            $result = (string)$value;
        }

        return $result;
    }

    /**
     * Format insertion data
     * @param array $data insertion data
     * @param array [$options]
     * @return array formatted insertion
     */
    private function formatInsertion(array $data, array $options = [])
    {
        $omitPrimary = (count($this->primaryFields()) == 1);

        $insertion = array();
        foreach ($this->fieldNames() as $name) {
            // case 1: key is present - use provided value
            if (isset($data[$name])) {
                $value = $data[$name];
            }
            // case 2: in case _id field is used and doesn't need to be specified
            elseif ($name == '_id' && $omitPrimary) {
                continue;
            }
            // case 3: skip default
            elseif (isset($options['skip_default']) && $options['skip_default']) {
                continue;
            }
            // case 4: key is missing - use default value
            else {
                $value = $this->fieldDefault($name);
            }

            $insertion[$this->fieldAlias($name)] = $this->dataToMongo($value, $this->fieldType($name));
        }

        return $insertion;
    }

    /**
     * Format conditions data
     * @param array $conditions
     * @param string [$fieldType] field type from parent iteration
     * @return array formatted conditions
     */
    private function formatConditions(array $conditions, $fieldType = '')
    {
        $formatted = array();
        foreach ($conditions as $key => $value) {
            // case 1: numeric key
            if (is_numeric($key)) {
                // do nothing
            }
            // case 2: string key (standard field) - change field to it's alias
            elseif (in_array($key, $this->fieldNames())) {
                // set data field type according to definition
                $fieldType = $this->fieldType($key);

                // change key to its alias
                $key = $this->fieldAlias($key);
            }
            // case 3: string key (operator) - keep current field
            elseif (strpos($key, '$') === 0) {
                // special operators - do not format these
                if (in_array($key, [
                    '$exists', '$type', '$mod', '$regex', '$options', '$text',
                    '$search', '$language', '$where', '$geoWithin', '$geometry',
                    '$geoIntersects', '$near', '$nearSphere', '$all', '$elemMatch', '$size'
                ])) {
                    $fieldType = '';
                }
            }
            // case 4: unsupported data
            else {
                continue;
            }

            // case 1: complex condition
            if (is_array($value)) {
                $value = $this->formatConditions($value, $fieldType);
            }
            // case 2: simple condition
            else {
                $value = $this->dataToMongo($value, $fieldType);
            }

            $formatted[$key] = $value;
        }

        return $formatted;
    }

    /**
     * Format operations data
     * @param array $operations
     * @return array formatted operations
     */
    private function formatOperations(array $operations)
    {
        // format operations
        $formatted = array();
        foreach ($operations as $operation => $data) {
            foreach ($data as $key => $value) {
                if (in_array($key, $this->fieldNames())) {
                    $value = $this->dataToMongo($value, $this->fieldType($key));

                    $formatted[$operation][$this->fieldAlias($key)] = $value;
                }
            }
        }

        return $formatted;
    }

    /**
     * Format projection data
     * @param array $projection
     * @return array formatted projection
     */
    private function formatProjection(array $projection)
    {
        $formatted = array();
        foreach ($projection as $key) {
            if (in_array($key, $this->fieldNames())) {
                $formatted[] = $this->fieldAlias($key);
            }
        }

        // prepare projection
        $formatted = array_combine($formatted, array_fill(0, count($formatted), true));

        // '_id' is included by default, exclude it if not present
        if (!in_array('_id', $projection)) {
            $formatted['_id'] = false;
        }

        return $formatted;
    }

    /**
     * Format result data
     * @param array $projection projection
     * @param array $data result data
     * @return array formatted result data
     */
    private function formatResult(array $projection, array $data)
    {
        $formatted = array();
        foreach ($projection as $key) {
            if (in_array($key, $this->fieldNames())) {
                $alias = $this->fieldAlias($key);

                // case 1: key is present in the result - load data
                if (isset($data[$alias])) {
                    $formatted[$key] = $this->dataFromMongo($data[$alias], $this->fieldType($key));
                }
                // case 2: key is missing - load default
                else {
                    $formatted[$key] = $this->fieldDefault($key);
                }
            }
        }

        return $formatted;
    }

    /**
     * @return Mongo
     */
    protected function db()
    {
        return $this->db;
    }

    /**
     * Executes insert operation
     * @param array $data insertion data
     * @param array [$options]
     * @return Result
     */
    protected function insert(array $data, array $options = [])
    {
        $db = $this->db();

        $insertion = $this->formatInsertion($data, $options);

        $result = $db->write($this->entityName(), ['insert' => [$insertion]]);

        return $result;
    }

    /**
     * Executes batch insert operation
     * @param array $batch batched insertion data
     * @return Result
     */
    protected function batchInsert(array $batch)
    {
        $db = $this->db();

        $formatted = array();
        foreach ($batch as $data) {
            $formatted[] = $this->formatInsertion($data);
        }

        $result = $db->write($this->entityName(), ['insert' => $formatted]);

        return $result;
    }

    /**
     * Executes update operation
     * @param array $conditions
     * @param array $operations
     * @param array [$options]
     * @return Result
     */
    protected function modify(array $conditions, array $operations, array $options = [])
    {
        $db = $this->db();

        // format conditions
        $conditions = $this->formatConditions($conditions);

        // format operations
        $operations = $this->formatOperations($operations);

        $result = $db->write($this->entityName(), ['update' => [[$conditions, $operations, $options]]]);

        return $result;
    }

    /**
     * Executes find one operation
     * @param array $conditions
     * @param array $projection
     * @return Result
     */
    protected function findOne(array $conditions, array $projection)
    {
        $db = $this->db();

        // format conditions
        $conditions = $this->formatConditions($conditions);

        // format fields
        $formatted = $this->formatProjection($projection);

        $result = $db->read($this->entityName(), $conditions, [
            'projection' => $formatted,
            'limit' => 1,
        ]);
        if ($result->isError()) {
            return $result;
        }

        // extract result
        $result = $result->data();

        $resultData = array();
        foreach ($result as $data) {
            $resultData[] = $this->formatResult($projection, $data);
        }

        // result is empty
        if (count($resultData) == 0) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS, $resultData[0]);
    }

    /**
     * Executes find and modify operation
     * @param array $conditions
     * @param array $operations
     * @param array $projection
     * @param array [$options]
     * @return Result
     */
    protected function findAndModify(array $conditions, array $operations, array $projection, array $options = [])
    {
        $db = $this->db();

        // format conditions
        $conditions = $this->formatConditions($conditions);

        // format operations
        $operations = $this->formatOperations($operations);

        // format projection
        $formatted = $this->formatProjection($projection);

        $result = $db->command(array_merge([
            'findandmodify' => $this->entityName(),
            'query' => $conditions,
            'update' => $operations,
            'fields' => $formatted,
        ], $options));
        if ($result->isError()) {
            return $result;
        }

        $data = (!empty($result->data()['value'])) ? $db->objectToArray($result->data()['value']) : [];
        if (empty($data)) {
            return new Result(Result::NO_EFFECT);
        }

        // process result data
        $data = $this->formatResult($projection, $data);

        return new Result(Result::SUCCESS, $data);
    }

    /**
     * Executes find operation
     * @param array $conditions
     * @param array $projection
     * @param array [$options]
     * @return Result
     */
    protected function find(array $conditions, array $projection, array $options = [])
    {
        $db = $this->db();

        // format conditions
        $conditions = $this->formatConditions($conditions);

        // format fields
        $formatted = $this->formatProjection($projection);

        $queryOptions = ['projection' => $formatted];

        // apply limit
        if (isset($options['limit'])) {
            $queryOptions['limit'] = $options['limit'];
        }

        // apply order
        if (isset($options['sort'])) {
            $queryOptions['sort'] = $options['sort'];
        }

        // skip specified number of records
        if (isset($options['skip']) && $options['skip'] > 0) {
            $queryOptions['skip'] = $options['skip'];
        }

        $result = $db->read($this->entityName(), $conditions, $queryOptions);
        if ($result->isError()) {
            return $result;
        }

        // extract result
        $result = $result->data();

        $resultData = array();
        foreach ($result as $data) {
            $resultData[] = $this->formatResult($projection, $data);
        }

        // result is empty
        if (count($resultData) == 0) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS, $resultData);
    }

    /**
     * Executes count operation
     * @param array $conditions
     * @param array [$options]
     * @return Result (contains integer inside on success)
     */
    protected function count(array $conditions, array $options = [])
    {
        // format conditions
        $conditions = $this->formatConditions($conditions);

        $result = $this->db->command(array_merge([
            'count' => $this->entityName(),
            'query' => $conditions,
            'options' => $options,
        ], $options));
        if ($result->isError()) {
            return $result;
        }

        $data = (!empty($result->data()['n'])) ? $result->data()['n'] : [];
        if (empty($data)) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS, [$data]);
    }

    /**
     * Executes remove operation
     * @param array $conditions
     * @param array [$options]
     * @return Result
     */
    protected function remove(array $conditions, array $options = [])
    {
        $db = $this->db();

        // format conditions
        $conditions = $this->formatConditions($conditions);

        $result = $db->write($this->entityName(), ['delete' => [[$conditions, $options]]]);

        return $result;
    }

    /**
     * Insert provided data
     * @param array $data
     * @return Result
     */
    protected function create(array $data)
    {
        // add auto-id field if necessary
        $autoIdField = $this->findAutoIdField();
        if (!empty($autoIdField)) {
            $dic = \Dic::getInstance();
            $result = $dic->dbEntityFactory()->autoIncrement()->nextId($this->entityName());
            if ($result->isErrorOrNoEffect()) {
                return $result;
            }

            // add next id to the insertion data
            $nextId = $result->data()['counter'];
            $data[$autoIdField] = $nextId;
        }

        return $this->insert($data);
    }

    /**
     * Read data based on specified conditions
     * @param array $conditions
     * @return Result
     */
    protected function read(array $conditions)
    {
        return $this->findOne($conditions, $this->fieldNames());
    }

    /**
     * Update data based on specified conditions and changes
     * @param array $conditions
     * @param array $data
     * @return Result
     */
    protected function update(array $conditions, array $data)
    {
        $db = $this->db();

        // prepare data update - process data fields
        $update = array();
        foreach ($data as $key => $value) {
            // omit primary keys
            if (!in_array($key, $this->primaryFields())) {
                $update[$key] = $data[$key];
            }
        }

        $result = $this->modify($conditions, ['$set' => $update], ['multi' => false]);

        // error occurred
        if ($result->isError()) {
            return new Result(Result::ERROR);
        }

        // query had no effect
        if ($db->effectedRows() == 0) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS);
    }

    /**
     * Delete data based on specified conditions
     * @param array $conditions
     * @return Result
     */
    protected function delete(array $conditions)
    {
        $db = $this->db();

        $result = $this->remove($conditions, ['limit' => 1]);

        // process result
        if ($result->isError()) {
            return new Result(Result::ERROR);
        }

        // query had no effect
        if ($db->effectedRows() == 0) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS);
    }

    /**
     * @param Mongo $db
     * @param array [$config]
     */
    public function __construct(Mongo $db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function mongoId()
    {
        $id = new ObjectID();

        return (string)$id;
    }
}
