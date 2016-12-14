<?php
/**
 * Response - final output generation
 */

namespace Controller;

class Response
{
    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @var string
     */
    protected $output = '';

    /**
     * @var array
     */
    protected $cookies = array();

    /**
     * Response constructor.
     * @param string $output
     * @param array [$headers]
     */
    public function __construct($output, array $headers = [])
    {
        $this->output = $output;
        $this->headers = $headers;
    }

    /**
     * @param array $cookies
     */
    public function setCookies(array $cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     *
     */
    public function output()
    {
        // set cookies
        foreach ($this->cookies as $cookieName => $cookieData) {
            $cookieValue = $cookieData[0];
            $cookieLifespan = $cookieData[1];
            setcookie($cookieName, $cookieValue, $cookieLifespan);
        }

        // generate headers
        foreach ($this->headers as $header) {
            header($header);
        }

        // generate output
        echo $this->output;
    }

    /**
     * @return string
     */
    public function raw()
    {
        return $this->output;
    }
}
