<?php
/**
 * Abstract middleware
 */

namespace Middleware;

use ArcomageException as Exception;
use Controller\Response;
use Controller\Result as ControllerResult;

abstract class MiddlewareAbstract extends \ControllerAbstract
{
    /**
     * @var Result
     */
    private $result;

    /**
     * @return Result
     */
    protected function result()
    {
        if (empty($this->result)) {
            $this->result = new Result();
        }

        return $this->result;
    }

    /**
     */
    abstract protected function execute();

    /**
     * @param ControllerResult $result
     * @return Response
     */
    abstract protected function processResult(ControllerResult $result = null);

    /**
     * @param array [$overrideData] data override
     * @throws Exception
     */
    protected function beginSession(array $overrideData = [])
    {
        // attempt to login player
        try {
            $request = $this->request();
            $cookies = $this->getDic()->cookies();
            $dbEntityPlayer = $this->dbEntity()->player();
            $servicePlayer = $this->service()->player();

            // key => [value, timeout]
            $newCookies = array();

            // manual login override
            if (!empty($overrideData)) {
                $request = array_merge($request, $overrideData);
            }

            // case 1: the user is providing a username/password pair for a new session
            if (isset($request['username']) && isset($request['password'])) {
                $player = $dbEntityPlayer->getPlayer($request['username']);
                if (empty($player)) {
                    throw new Exception('Player not found in DB', Exception::WARNING);
                }

                if (md5($request['password']) != $player->getPassword()) {
                    throw new Exception('Wrong password', Exception::WARNING);
                }

                $newCookies = $servicePlayer->beginSession($player, 'maybe');
            }
            // case 2: the user is providing a name/session id pair via cookies
            elseif (isset($cookies['username']) && isset($cookies['session_id'])) {
                $player = $dbEntityPlayer->getPlayer($cookies['username']);
                if (empty($player)) {
                    throw new Exception('Player not found in DB', Exception::WARNING);
                }

                $servicePlayer->validateSession($player, $cookies['session_id']);

                // cookies are working
                $newCookies = $servicePlayer->beginSession($player, 'yes', $cookies['session_id']);
            }
            // case 3: the user is providing a name/session id pair via POST, probably because he/she doesn't use cookies >_>
            elseif (isset($request['username']) && isset($request['session_id'])) {
                $player = $dbEntityPlayer->getPlayer($request['username']);
                if (empty($player)) {
                    throw new Exception('Player not found in DB', Exception::WARNING);
                }

                // compare the values against the database to see if they are valid
                $servicePlayer->validateSession($player, $request['session_id']);

                // assume cookies are disabled
                $newCookies = $servicePlayer->beginSession($player, 'no', $request['session_id']);
            }
            // case 4: the user did not provide any means of identification
            else {
                // proceed as guest
                $player = $this->dbEntity()->player()->getGuest();
            }

            // store current player
            $this->getDic()->setPlayer($player);

            // set new cookies
            foreach ($newCookies as $key => $data) {
                $this->result()->setCookie($key, $data[0], $data[1]);
            }
        }
        catch (Exception $e) {
            $this->destroySession();

            // re-trow exception on error
            if ($e->getCode() == Exception::ERROR) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
        }
    }

    /**
     * Destroy all session related data
     */
    protected function destroySession()
    {
        $cookies = $this->getDic()->cookies();

        if (isset($cookies['username'])) {
            $this->result()->setCookie('username', null, -1);
        }

        if (isset($cookies['session_id'])) {
            $this->result()->setCookie('session_id', null, -1);
        }

        // demote player to guest
        $this->getDic()->setPlayer($this->dbEntity()->player()->getGuest());
    }

    /**
     * @param string $middleware
     * @return Response
     */
    public function getResult($middleware)
    {
        // clear result
        $this->result = null;

        try {
            // execute middleware
            $this->execute();

            // middleware raw output is set
            if (!empty($this->result()->rawOutput())) {
                return new Response($this->result()->rawOutput(), $this->result()->headers());
            }
        }
        catch (Exception $e) {
            // log error if necessary
            if ($e->getCode() == Exception::ERROR) {
                $this->getDic()->logger()->logException($e);
            }

            $this->result()->setError($e->getMessage());
        }

        // pass control flags from middleware result to DIC
        $this->getDic()->setFlags($this->result()->exportFlags());

        $controllerResult = null;

        // execute controller action if necessary
        if ($this->result()->isSuccess() && $this->result()->controllerProcessing()) {
            try {
                $controllerResult = $this->getDic()->controllerFactory()->executeControllerAction($middleware);

                // controller raw output is set
                if (!empty($controllerResult->rawOutput())) {
                    return new Response($controllerResult->rawOutput(), $controllerResult->headers());
                }
            }
            catch (Exception $e) {
                // log error if necessary
                if ($e->getCode() == Exception::ERROR) {
                    $this->getDic()->logger()->logException($e);
                }

                $this->getDic()->setError($e->getMessage());
            }

            // pass control flags from controller result to DIC
            if (!empty($controllerResult)) {
                $this->getDic()->setFlags($controllerResult->exportFlags());
            }
        }

        // generate response
        $response = $this->processResult($controllerResult);

        // pass cookies to response
        $response->setCookies($this->result()->cookies());

        return $response;
    }
}
