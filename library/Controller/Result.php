<?php
/**
 * Controller result
 */

namespace Controller;

class Result
{
    /**
     * successful result
     */
    const SUCCESS = 1;

    /**
     * error
     */
    const ERROR = 0;

    /**
     * Result status code
     * @var int
     */
    protected $status;

    /**
     * @var string
     */
    protected $info = '';

    /**
     * @var string
     */
    protected $warning = '';

    /**
     * @var string
     */
    protected $error = '';

    /**
     * @var string
     */
    protected $currentSection = '';

    /**
     * @var array
     */
    protected $requestChanges = [];

    /**
     * @var int
     */
    protected $levelUp = 0;

    /**
     * @var string
     */
    protected $rawOutput = '';

    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Result constructor.
     */
    public function __construct()
    {
        $this->status = self::SUCCESS;
    }

    /**
     * @return int
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function info()
    {
        return $this->info;
    }

    /**
     * @return string
     */
    public function warning()
    {
        return $this->warning;
    }

    /**
     * @return string
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function currentSection()
    {
        return $this->currentSection;
    }

    /**
     * @return array
     */
    public function requestChanges()
    {
        return $this->requestChanges;
    }

    /**
     * @return int
     */
    public function levelUp()
    {
        return $this->levelUp;
    }

    /**
     * @return string
     */
    public function rawOutput()
    {
        return $this->rawOutput;
    }

    /**
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setInfo($message)
    {
        $this->info = $message;

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setWarning($message)
    {
        $this->warning = $message;

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setError($message)
    {
        $this->error = $message;
        $this->status = self::ERROR;

        return $this;
    }

    /**
     * @param string $sectionName
     * @return $this
     */
    public function setCurrent($sectionName)
    {
        $this->currentSection = $sectionName;

        return $this;
    }

    /**
     * @param int $level
     * @return $this
     */
    public function setLevelUp($level)
    {
        $this->levelUp = $level;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function changeRequest($key, $value)
    {
        $this->requestChanges[$key] = $value;

        return $this;
    }

    /**
     * @param string $output
     * @param array $headers
     * @return $this
     */
    public function setRawOutput($output, array $headers = [])
    {
        $this->rawOutput = $output;
        $this->headers = $headers;

        return $this;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Is result success
     * @return bool
     */
    public function isSuccess()
    {
        return ($this->status == self::SUCCESS);
    }

    /**
     * Is result error
     * @return bool
     */
    public function isError()
    {
        return ($this->status == self::ERROR);
    }

    /**
     * @return array
     */
    public function exportFlags()
    {
        $allFlags = [
            'info' => $this->info(),
            'warning' => $this->warning(),
            'error' => $this->error(),
            'current' => $this->currentSection(),
            'request_changes' => $this->requestChanges(),
            'level_up' => $this->levelUp(),
        ];

        // export used flags only
        $usedFlags = array();
        foreach ($allFlags as $key => $value) {
            if (!empty($value)) {
                $usedFlags[$key] = $value;
            }
        }

        return $usedFlags;
    }
}
