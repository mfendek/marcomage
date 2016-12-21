<?php
/**
 * Ajax middleware
 */

namespace Middleware;

use ArcomageException as Exception;
use Controller\Response;
use Controller\Result;
use Util\Encode;

class Ajax extends MiddlewareAbstract
{
    /**
     * @param Result $controllerResult
     * @return Response
     */
    protected function processResult(Result $controllerResult = null)
    {
        $data = array();
        $middlewareResult = $this->result();

        // case 1: middleware error
        if ($middlewareResult->isError()) {
            $data['error'] = $middlewareResult->error();
        }
        // case 2: controller processing active, no result given
        elseif ($this->result()->controllerProcessing() && empty($controllerResult)) {
            $data['error'] = $this->getDic()->error();
        }
        // case 3: controller action error
        elseif (!empty($controllerResult) && $controllerResult->isError()) {
            $data['error'] = $controllerResult->error();
        }
        // case 4: success
        else {
            $data = $controllerResult->data();
        }

        return new Response(
            json_encode($data, JSON_PRETTY_PRINT), ['content-type: application/json; charset=utf-8']
        );
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        $request = $this->request();

        $this->assertParamsNonEmpty(['action']);

        // white list of public actions
        $publicActions = [
            'card_lookup'
        ];

        // private action - session validation required
        if (!in_array($request['action'], $publicActions)) {
            $this->assertParamsNonEmpty(['username', 'session_id']);

            // decode username
            $request['username'] = Encode::postDecode($request['username']);

            $this->beginSession(['username' => $request['username'], 'session_id' => $request['session_id']]);

            if (!$this->isSession()) {
                throw new Exception('Invalid session', Exception::WARNING);
            }
        }
        // public action - attempt to check session, but allow guest access as well
        elseif (!empty($request['username']) && !empty($request['session_id'])) {
            $request['username'] = Encode::postDecode($request['username']);

            $this->beginSession(['username' => $request['username'], 'session_id' => $request['session_id']]);
        }

        $this->result()->enableControllerProcessing();

        // reformat controller action to standard format
        $this->getDic()->setFlags(['request_changes' => [$request['action'] => 1]]);
    }
}
