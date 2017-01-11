<?php
/**
 * Simple dependency injection container
 */

class Dic implements ArrayAccess
{
    /**
     * @var array
     */
    private $values = array();

    /**
     * Initialize params
     */
    private static function initParams()
    {
        $dic = self::getInstance();
//        $dic['request'] = $_REQUEST;
        $dic['server'] = $_SERVER;
        $dic['cookies'] = $_COOKIE;
        $dic['files'] = $_FILES;

        // apply backwards compatibility corrections
        $request = $_REQUEST;
        $patch = [
            'CurrentConcept' => 'current_concept',
            'CurrentDeck' => 'current_deck',
            'CurrentSection' => 'current_section',
            'CurrentThread' => 'current_thread',
            'CurrentPage' => 'thread_current_page',
        ];

        foreach ($patch as $find => $replace) {
            if (isset($request[$find])) {
                $request[$replace] = $request[$find];
                unset($request[$find]);
            }
        }

        $dic['request'] = $request;
    }

    /**
     * @return array
     */
    private static function mergeConfigs()
    {
        if (func_num_args() < 2) {
            trigger_error(__METHOD__ . ' needs two or more array arguments', E_USER_WARNING);
            return [];
        }

        $arrays = func_get_args();
        $merged = array();

        while ($arrays) {
            $array = array_shift($arrays);
            if (!is_array($array)) {
                trigger_error(__METHOD__ . ' encountered a non array argument', E_USER_WARNING);
                return [];
            }

            foreach ($array as $key => $value) {
                if (is_array($value) && array_key_exists($key, $merged) && is_array($merged[$key])) {
                    $merged[$key] = call_user_func(__METHOD__, $merged[$key], $value);
                }
                else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * Set a parameter or an object
     * objects must be defined as Closures
     * @param string $id unique identifier for the parameter or object
     * @param mixed $value value of the parameter or a closure to a defined an object
     */
    function offsetSet($id, $value)
    {
        $this->values[$id] = $value;
    }

    /**
     * Gets a parameter or an object
     * @param mixed $id
     * @return mixed
     * @throws \InvalidArgumentException
     */
    function offsetGet($id)
    {
        if (!array_key_exists($id, $this->values)) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return $this->values[$id] instanceof \Closure ? $this->values[$id]($this) : $this->values[$id];
    }

    /**
     * Checks if a parameter or an object is set
     * @param string $id unique identifier for the parameter or object
     * @return boolean
     */
    function offsetExists($id)
    {
        return isset($this->values[$id]);
    }

    /**
     * Un-sets a parameter or an object
     * @param string $id unique identifier for the parameter or object
     */
    function offsetUnset($id)
    {
        unset($this->values[$id]);
    }

    /**
     * Returns a closure that stores the result of the given closure
     * @param Closure $callable
     * @return Closure
     */
    function share(Closure $callable)
    {
        return function ($c) use ($callable) {
            static $object;

            if (is_null($object)) {
                $object = $callable($c);
            }

            return $object;
        };
    }

    /**
     * Protects a callable from being interpreted as a service
     * @param Closure $callable
     * @return Closure
     */
    function protect(Closure $callable)
    {
        return function ($c) use ($callable) {
            return $callable;
        };
    }

    /**
     * Gets a parameter or the closure defining an object
     * @param $id
     * @return mixed
     * @throws \InvalidArgumentException
     */
    function raw($id)
    {
        if (!array_key_exists($id, $this->values)) {
            throw new \InvalidArgumentException(sprintf('Identifier "%s" is not defined.', $id));
        }

        return $this->values[$id];
    }


    /**
     * Simple alias for offsetExists()
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * @var Dic
     */
    protected static $_instance;

    /**
     * @param Dic $dic
     */
    public static function setInstance(Dic $dic = null)
    {
        self::$_instance = $dic;
    }

    /**
     * @return Dic
     * @throws Exception
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            $dic = new self;

            self::setInstance($dic);
            self::initParams();

            $dic['script_start_time'] = microtime(true);
        }
        return self::$_instance;
    }

    /**
     * Reset to initial state
     */
    public static function resetToOutOfTheBoxState()
    {
        self::$_instance = null;
    }

    /**
     * Dic constructor
     */
    public function __construct()
    {
        $dic = $this;

        $dic['logger'] = $dic->share(function($dic) {
            $logger = new Logger($dic);

            return $logger;
        });

        $dic['view'] = $dic->share(function($dic) {
            $view = new \View\View($dic);

            return $view;
        });

        $dic['db_util_factory'] = $dic->share(function($dic) {
            $dbUtilFactory = new Db\Util\Factory($dic);

            return $dbUtilFactory;
        });

        $dic['db_entity_factory'] = $dic->share(function($dic) {
            $dbEntityFactory = new Db\Entity\Factory($dic);

            return $dbEntityFactory;
        });

        $dic['def_entity_factory'] = $dic->share(function($dic) {
            $defEntityFactory = new Def\Entity\Factory($dic);

            return $defEntityFactory;
        });

        $dic['service_factory'] = $dic->share(function($dic) {
            $serviceFactory = new \Service\Factory($dic);

            return $serviceFactory;
        });

        $dic['view_factory'] = $dic->share(function($dic) {
            $viewFactory = new \View\Factory($dic);

            return $viewFactory;
        });

        $dic['controller_factory'] = $dic->share(function($dic) {
            $controllerFactory = new \Controller\Factory($dic);

            return $controllerFactory;
        });

        $dic['middleware_factory'] = $dic->share(function($dic) {
            $middlewareFactory = new \Middleware\Factory($dic);

            return $middlewareFactory;
        });
    }

    /**
     * @param \Db\Model\Player $player
     */
    public static function setPlayer(\Db\Model\Player $player)
    {
        $dic = self::getInstance();
        $dic['player'] = $player;
    }

    /**
     * @param array $config
     * @param array $configLive
     */
    public static function setConfig(array $config, array $configLive = [])
    {
        self::getInstance()['config'] = self::mergeConfigs($config, $configLive);
    }

    /**
     * @return mixed
     */
    public static function config()
    {
        return self::getInstance()['config'];
    }

    /**
     * @return string
     */
    public static function getMiddlewareName()
    {
        $request = self::request();

        // determine middleware
        $middleware = (isset($request['m']) && in_array($request['m'], ['web', 'ajax', 'scripts'])) ? $request['m'] : 'web';

        return $middleware;
    }

    /**
     * @return \Controller\Response
     */
    public static function dispatch()
    {
        // determine middleware
        $middleware = self::getMiddlewareName();

        return self::middlewareFactory()->loadMiddleware($middleware)->getResult($middleware);
    }

    /**
     * @return array
     */
    public static function request()
    {
        $dic = self::getInstance();

        // add request changes
        return array_merge($dic['request'], $dic->requestChanges());
    }

    /**
     * @return array
     */
    public static function server()
    {
        return self::getInstance()['server'];
    }

    /**
     * @return array
     */
    public static function cookies()
    {
        return self::getInstance()['cookies'];
    }

    /**
     * @return array
     */
    public static function files()
    {
        return self::getInstance()['files'];
    }

    /**
     * @return Logger
     */
    public static function logger()
    {
        return self::getInstance()['logger'];
    }

    /**
     * @return View\View
     */
    public static function view()
    {
        return self::getInstance()['view'];
    }

    /**
     * @return Db\Util\Factory
     */
    public static function dbUtilFactory()
    {
        return self::getInstance()['db_util_factory'];
    }

    /**
     * @return Db\Entity\Factory
     */
    public static function dbEntityFactory()
    {
        return self::getInstance()['db_entity_factory'];
    }

    /**
     * @return Def\Entity\Factory
     */
    public static function defEntityFactory()
    {
        return self::getInstance()['def_entity_factory'];
    }

    /**
     * @return \Service\Factory
     */
    public static function serviceFactory()
    {
        return self::getInstance()['service_factory'];
    }

    /**
     * @return \View\Factory
     */
    public static function viewFactory()
    {
        return self::getInstance()['view_factory'];
    }

    /**
     * @return \Controller\Factory
     */
    public static function controllerFactory()
    {
        return self::getInstance()['controller_factory'];
    }

    /**
     * @return \Middleware\Factory
     */
    public static function middlewareFactory()
    {
        return self::getInstance()['middleware_factory'];
    }

    /**
     * @return \Db\Model\Player
     */
    public static function getPlayer()
    {
        // initialize player as guest
        if (empty(self::getInstance()['player'])) {
            self::setPlayer(self::dbEntityFactory()->player()->getGuest());
        }

        return self::getInstance()['player'];
    }

    /**
     * @return bool
     */
    public static function isSession()
    {
        return (!self::getPlayer()->isGuest());
    }

    /**
     * @return array
     */
    public static function getFlags()
    {
        $dic = self::getInstance();

        // initialize player as guest
        if (empty($dic['flags'])) {
            $dic['flags'] = array();
        }

        return self::getInstance()['flags'];
    }

    /**
     * @param array $flags
     */
    public static function setFlags(array $flags)
    {
        $dic = self::getInstance();

        // merge request changes if necessary
        if (!empty($dic->requestChanges()) && !empty($flags['request_changes'])) {
            $flags['request_changes'] = array_merge($dic->requestChanges(), $flags['request_changes']);
        }

        $dic['flags'] = array_merge($dic->getFlags(), $flags);
    }

    /**
     * @param string $message
     */
    public static function setError($message)
    {
        self::setFlags(['error' => $message, 'current' => 'Error']);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function getFlag($name)
    {
        $dic = self::getInstance();
        $flags = $dic->getFlags();

        return isset($flags[$name]) ? $flags[$name] : null;
    }

    /**
     * @return string
     */
    public static function info()
    {
        $flag = self::getInstance()->getFlag('info');
        return !is_null($flag) ? $flag : '';
    }

    /**
     * @return string
     */
    public static function warning()
    {
        $flag = self::getInstance()->getFlag('warning');
        return !is_null($flag) ? $flag : '';
    }

    /**
     * @return string
     */
    public static function error()
    {
        $flag = self::getInstance()->getFlag('error');
        return !is_null($flag) ? $flag : '';
    }

    /**
     * @return bool
     */
    public static function newUserFlag()
    {
        $flag = self::getInstance()->getFlag('new_user');
        return !is_null($flag) ? $flag : false;
    }

    /**
     * @return string
     */
    public static function currentSection()
    {
        $flag = self::getInstance()->getFlag('current');
        return !is_null($flag) ? $flag : 'Webpage';
    }

    /**
     * @return int
     */
    public static function levelGained()
    {
        $flag = self::getInstance()->getFlag('level_up');
        return !is_null($flag) ? $flag : 0;
    }

    /**
     * @return array
     */
    public static function requestChanges()
    {
        $flag = self::getInstance()->getFlag('request_changes');
        return !is_null($flag) ? $flag : [];
    }

    /**
     * @return int
     */
    public static function scriptStartTime()
    {
        return self::getInstance()['script_start_time'];
    }
}
