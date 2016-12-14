<?php
/**
 * Error and debug logger
 * Provides basic logging functionality with multiple writers
 */

class Logger
{
    /**
     * DIC reference
     * @var \Dic
     */
    protected $dic;

    /**
     * writers resource cache
     * @var array
     */
    protected $writers = array();

    /**
     * @param \Dic $dic
     */
    public function __construct(\Dic $dic)
    {
        $this->dic = $dic;
    }

    /**
     * @return \Dic
     */
    protected function getDic()
    {
        return $this->dic;
    }

    /**
     * Create writer of specified name
     * @param string $className
     */
    protected function createWriter($className)
    {
        // determine writer type
        $writerType = strtolower($className);

        // add class name prefix
        $className = '\Writer\\'.$className;

        $db = new $className($this->getDic()->dbUtilFactory());

        // store writer to writer cache for future use
        $this->writers[$writerType] = $db;
    }

    /**
     * Load writer class of specified name
     * @param string $writerType
     * @return Writer\WriterAbstract
     */
    protected function loadWriter($writerType)
    {
        // determine writer class name
        $className = ucfirst($writerType);

        // check resource cache first, initialize when necessary
        if (!isset($this->writers[$writerType])) {
            $this->createWriter($className);
        }

        return $this->writers[$writerType];
    }

    /**
     * Log generic exception
     * @param \Exception $e
     */
    public function logException(\Exception $e)
    {
        $trace = $e->getTrace();
        $class = (!empty($trace[0]['class'])) ? $trace[0]['class'] : '';
        $function = (!empty($trace[0]['function'])) ? $trace[0]['function'] : '';
        $method = $class.'::'.$function;

        $factory = $this->getDic()->dbUtilFactory();
        $dbPdo = $factory->pdo();

        $pdoErrors = $dbPdo->errorsLog();

        // process DB messages
        $dbMessage = array();
        if ($pdoErrors != '') {
            $dbMessage[]= 'PDO: '.$dbPdo->errorsLog();
        }

        $dbMessage = implode("\n", $dbMessage);

        $data = array(
            'code' => $e->getCode(),
            'msg' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'method' => $method,
            'db_message' => $dbMessage,
        );

        $this->logError($data);
    }

    /**
     * Log new error
     * @param array $data error data
     */
    public function logError(array $data)
    {
        $dic = $this->getDic();
        $config = $dic->config();
        $server = $dic->server();
        $currentIp = Util\Ip::getIp();

        $defaults = array(
            'code' => 0,
            'msg' => '',
            'created' => Util\Date::timeToStr(),
            'file' => '',
            'line' => 0,
            'method' => '',
            'request_ip' => (!is_null($currentIp)) ? $currentIp : '',
            'backend_ip' => (!is_null($server['SERVER_ADDR'])) ? $server['SERVER_ADDR'] : '',
            'uri' => (!is_null($server['REQUEST_URI'])) ? $server['REQUEST_URI'] : '',
            'db_message' => '',
        );

        $data = array_merge($defaults, $data);

        // determine error logger config
        $writerType = $config['logger']['error_writer']['type'];
        $writerConfig = $config['logger']['error_writer'];

        $writer = $this->loadWriter($writerType);
        $writer->log($writerConfig, $data);
    }

    /**
     * Log new debug
     * @param string $message
     */
    public function logDebug($message)
    {
        $dic = $this->getDic();
        $config = $dic->config();
        $server = $dic->server();
        $currentIp = Util\Ip::getIp();

        $data = array(
            'msg' => $message,
            'created' => Util\Date::timeToStr(),
            'request_ip' => (!is_null($currentIp)) ? $currentIp : '',
            'backend_ip' => (!is_null($server['SERVER_ADDR'])) ? $server['SERVER_ADDR'] : '',
            'uri' => (!is_null($server['REQUEST_URI'])) ? $server['REQUEST_URI'] : '',
        );

        // determine error logger config
        $writerType = $config['logger']['debug_writer']['type'];
        $writerConfig = $config['logger']['debug_writer'];

        $writer = $this->loadWriter($writerType);
        $writer->log($writerConfig, $data);
    }
}
