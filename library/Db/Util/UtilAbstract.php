<?php
/**
 * Abstract Database utility wrapper
 * Provides common functionality for specific Database utility classes
 */

namespace Db\Util;

abstract class UtilAbstract
{
    const STATUS_OK = 'ok';
    const STATUS_OFFLINE_INIT = 'offline_init';
    const STATUS_OFFLINE_GET = 'offline_get';
    const STATUS_QUESTION_P = 'q_prepare';
    const STATUS_QUESTION_E = 'q_execute';
    const STATUS_QUESTION_F = 'q_fetch';

    /**
     * raw DB resource (depends on DB type)
     */
    protected $resource;

    /**
     * server IP
     * @var string
     */
    protected $server;

    /**
     * DB user name
     * @var string
     */
    protected $username;

    /**
     * DB user password
     * @var string
     */
    protected $password;

    /**
     * database name
     * @var string
     */
    protected $database;

    /**
     * port number
     * @var string
     */
    protected $port;

    /**
     * questions log
     * @var array
     */
    protected $log = array();

    /**
     * errors log
     * @var array
     */
    protected $errors = array();

    /**
     * effected rows by most recent question
     * @var int
     */
    protected $effectedRows = false;

    /**
     * DB status
     * @var string
     */
    protected $status = self::STATUS_OK;

    /**
     * questions count
     * @var int
     */
    protected $questions = 0;

    /**
     * total time spent on questions processing
     * @var int
     */
    protected $qTimeTotal = 0;

    /**
     * current question start time mark
     * @var int
     */
    protected $qTimeStart = 0;

    /**
     * Question log enabled setting
     * @var bool
     */
    protected $qLogEnabled;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->server = $config['server'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->database = $config['database'];
        $this->port = (isset($config['port'])) ? $config['port'] : '';
        $this->qLogEnabled = (isset($config['qlog'])) ? $config['qlog'] : false;
    }

    /**
     * Initialize DB resource
     */
    abstract protected function init();

    /**
     * Load DB resource
     */
    protected function loadDb()
    {
        // init DB resource if necessary
        if (empty($this->resource)) {
            $this->init();
        }

        return $this->resource;
    }

    /**
     * Mark start of question processing
     */
    protected function markQuestionStart()
    {
        $this->qTimeStart = microtime(true);
        $this->effectedRows = false;
    }

    /**
     * Mark end of question processing
     * @param string $question
     */
    protected function markQuestionEnd($question)
    {
        // determine question time
        $questionTime = microtime(true) - $this->qTimeStart;

        // update log data
        $this->questions++;
        $this->qTimeTotal+= $questionTime;

        if ($this->qLogEnabled) {
            $this->log[]= sprintf("[%.2f ms] %s", round(1000 * $questionTime, 2), $question);
        }

        $this->status = self::STATUS_OK;
    }

    /**
     * Log error message
     * @param $status
     * @param $message
     * @param $question
     */
    protected function logError($status, $message = '', $question = '')
    {
        $this->status = $status;
        $errorMessage = $status;

        // add message if available
        if ($message != '') {
            $errorMessage.= ' '.$message;
        }

        // add message if question debug data if available
        if ($question != '') {
            $errorMessage.= ' '.$question;
        }

        $this->errors[]= $errorMessage;
    }

    /**
     * Return raw initialized DB resource
     */
    abstract public function db();

    /**
     * Number of rows effected by most recent question
     * @return mixed
     */
    public function effectedRows()
    {
        return $this->effectedRows;
    }

    /**
     * Number of questions thus far
     * @return int
     */
    public function questions()
    {
        return $this->questions;
    }

    /**
     * Total time spent during questions
     * @return int
     */
    public function questionsTime()
    {
        return $this->qTimeTotal;
    }

    /**
     * Disable questions log (useful for maintenance scripts)
     */
    public function disableQuestionsLog()
    {
        $this->qLogEnabled = false;
    }

    /**
     * Fetch questions log
     * @return string
     */
    public function questionsLog()
    {
        return implode("\n", $this->log);
    }

    /**
     * Fetch errors log
     * @return string
     */
    public function errorsLog()
    {
        return implode("\n", $this->errors);
    }
}
