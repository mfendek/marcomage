<?php
/**
 * Abstract DB entity PDO (mapper and DAO wrapper)
 * Provides common functionality specific for PDO DB util
 */

namespace Db\Entity;

use Db\Util\Pdo;
use Db\Util\Result;

abstract class PdoAbstract extends EntityAbstract
{
    /**
     * @param Pdo $db
     * @param array [$config]
     */
    public function __construct(Pdo $db, array $config = array())
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * @return Pdo
     */
    protected function db()
    {
        return $this->db;
    }

    /**
     * Insert provided data
     * @param array $data
     * @return Result
     */
    protected function create(array $data)
    {
        // prepare query data
        $fields = $placeholders = $params = array();
        foreach ($data as $name => $value) {
            $fields[] = '`'.$name.'`';
            $placeholders[] = '?';
            $params[] = $value;
        }

        // execute query
        $result = $this->db()->query('INSERT INTO `'.$this->entityName().'` ('.implode(", ", $fields).') VALUES ('.implode(", ", $placeholders).')', $params);

        // add auto-id field if necessary
        $autoIdField = $this->findAutoIdField();
        if (!empty($autoIdField) && $result->isSuccess()) {
            return new Result(Result::SUCCESS, ['new_id' => $this->db()->lastId()]);
        }

        return $result;
    }

    /**
     * Read data based on specified conditions
     * @param array $conditions
     * @return Result
     */
    protected function read(array $conditions)
    {
        // data fields part of the query
        $fields = array();
        foreach ($this->fieldNames() as $name) {
            $fields[] = '`'.$name.'`';
        }

        $fields = implode(", ", $fields);

        // primary keys part of the query
        $primaryFields = $params = array();
        foreach ($conditions as $name => $value) {
            $primaryFields[] = '`'.$name.'` = ?';
            $params[] = $value;
        }

        // execute query
        $result = $this->db()->query('SELECT '.$fields.' FROM `'.$this->entityName().'` WHERE '.implode(" AND ", $primaryFields).' LIMIT 1', $params);

        // error occurred
        if ($result->isError()) {
            return new Result(Result::ERROR);
        }

        // data not found
        if (count($result->data()) == 0) {
            return new Result(Result::NO_EFFECT);
        }

        $data = $result[0];

        return new Result(Result::SUCCESS, $data);
    }

    /**
     * Update data based on specified conditions and changes
     * @param array $conditions
     * @param array $data
     * @return Result
     */
    protected function update(array $conditions, array $data)
    {
        // data part of the query
        $fields = $params = array();
        foreach ($data as $name => $value) {
            $fields[] = '`'.$name.'` = ?';
            $params[] = $value;
        }

        // primary keys part of the query
        $primaryFields = array();
        foreach ($conditions as $name => $value) {
            $primaryFields[] = '`'.$name.'` = ?';
            $params[] = $value;
        }

        // execute query
        $result = $this->db()->query('UPDATE `'.$this->entityName().'` SET '.implode(", ", $fields).' WHERE '.implode(" AND ", $primaryFields).' LIMIT 1', $params);

        // error occurred
        if ($result->isError()) {
            return new Result(Result::ERROR);
        }

        // query had no effect
        if ($this->db()->effectedRows() == 0) {
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
        // primary keys part of the query
        $primaryFields = $params = array();
        foreach ($conditions as $name => $value) {
            $primaryFields[] = '`'.$name.'` = ?';
            $params[] = $value;
        }

        // execute query
        $result = $this->db()->query('DELETE FROM `'.$this->entityName().'` WHERE '.implode(" AND ", $primaryFields).' LIMIT 1', $params);

        // process result
        if ($result->isError()) {
            return new Result(Result::ERROR);
        }

        // query had no effect
        if ($this->db()->effectedRows() == 0) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS);
    }
}
