<?php
/**
 * PDO utility wrapper
 * Provides low level PDO native services
 */

namespace Db\Util;

use ArcomageException as Exception;
use Util\Ip;

class Pdo extends UtilAbstract
{
    const MAX_STR_LOG_LENGTH = 25;

    /**
     * @throws Exception
     */
    protected function init()
    {
        $db = null;
        $errorMessage = '';

        // compose PDO dns string
        $dsn = 'mysql:dbname=' . $this->database . ';host=' . $this->server . (($this->port != '') ? ';port=' . $this->port : '');

        // attempt to connect via PDO
        try {
            $db = new \PDO($dsn, $this->username, $this->password, [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8, time_zone='+0:00'",
                \PDO::MYSQL_ATTR_COMPRESS => true
            ]);
        }
        catch (\PDOException $e) {
            $errorMessage = 'failed to create PDO ' . $e->getMessage();
            $this->logError(self::STATUS_OFFLINE_INIT, $errorMessage);
        }

        if ($this->status != self::STATUS_OK) {
            // add extra debug for internal IPs
            $extraDebug = (Ip::isInternalIp()) ? ' ' . $this->status . ' ' . $errorMessage : '';

            throw new Exception('Unable to connect to DB (via PDO), aborting.' . $extraDebug);
        }

        $this->resource = $db;
    }

    /**
     * Return raw initialized DB resource
     * @return \PDO
     */
    public function db()
    {
        $this->loadDb();

        return $this->resource;
    }

    /**
     * Last inserted id (auto-increment only)
     * @return int
     */
    public function lastId()
    {
        return $this->db()->lastInsertId();
    }

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction()
    {
        return $this->db()->beginTransaction();
    }

    /**
     * Rollback transaction
     * @return bool
     */
    public function rollBack()
    {
        return $this->db()->rollBack();
    }

    /**
     * Commit transaction
     * @return bool
     */
    public function commit()
    {
        return $this->db()->commit();
    }

    /**
     * Execute database query with provided parameter values
     * @param string $query database query
     * @param array $params parameter values in the order they appear in the query
     * @return Result
     */
    public function query($query, array $params = array())
    {
        $db = $this->db();
        if (empty($db)) {
            return new Result(Result::ERROR);
        }

        // prepare question debug data
        $question = $query;
        if (count($params) > 0) {
            // replace placeholders with values
            $queryParts = explode('?', $query);
            $question = $queryParts[0];

            // reformat long string data
            $reformatted = $params;
            foreach ($params as $i => $param) {
                if (mb_strlen($param) > self::MAX_STR_LOG_LENGTH) {
                    $reformatted[$i] = 'LONG_STRING';
                }

                $question.= $reformatted[$i].$queryParts[$i + 1];
            }
        }

        $this->markQuestionStart();

        // prepare
        try {
            $statement = $db->prepare($query);
        }
        catch (\PDOException $e) {
            $this->logError(self::STATUS_QUESTION_P, $e->getMessage(), $question);
            return new Result(Result::ERROR);
        }

        // execute
        $result = $statement->execute($params);
        if (!$result) {
            $this->logError(self::STATUS_QUESTION_E, implode(' ', $statement->errorInfo()), $question);
            return new Result(Result::ERROR);
        }

        // process result data
        $data = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if ($data === false) {
            $this->logError(self::STATUS_QUESTION_F, implode(' ', $statement->errorInfo()), $question);
            return new Result(Result::ERROR);
        }

        // update effected rows counter
        $this->effectedRows = $statement->rowCount();

        $this->markQuestionEnd($question);

        // free statement object
        $statement = null;

        // detect no effect or empty result set
        if ($this->effectedRows() == 0 && empty($data)) {
            return new Result(Result::NO_EFFECT);
        }

        return new Result(Result::SUCCESS, $data);
    }
}
