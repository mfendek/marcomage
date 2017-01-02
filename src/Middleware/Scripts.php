<?php
/**
 * Scripts middleware
 */

namespace Middleware;

use ArcomageException as Exception;
use Controller\Response;
use Controller\Result;

class Scripts extends MiddlewareAbstract
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
            $data[] = $middlewareResult->error();
        }
        // case 2: controller processing active, no result given
        elseif ($this->result()->controllerProcessing() && empty($controllerResult)) {
            $data['error'] = $this->getDic()->error();
        }
        // case 3: controller action error
        elseif (!empty($controllerResult) && $controllerResult->isError()) {
            $data[] = $controllerResult->error();
        }
        // case 4: success
        else {
            $data = $controllerResult->data();
        }

        return new Response(
            implode("\n", $data), ['Content-Type: text/plain; charset=utf-8']
        );
    }

    /**
     * @throws Exception
     */
    public function execute()
    {
        $request = $this->request();
        $config = $this->getDic()->config();

        $this->assertParamsNonEmpty(['password', 'name']);

        $password = $config['scripts']['password'];

        // script execution not allowed
        if (empty($password)) {
            throw new Exception('Maintenance scripts execution is disabled', Exception::WARNING);
        }

        // validate password
        if ($request['password'] != $password) {
            throw new Exception('Incorrect password', Exception::WARNING);
        }

        $this->result()->enableControllerProcessing();

        // reformat controller action to standard format
        $this->getDic()->setFlags(['request_changes' => [$request['name'] => 1]]);

        // disable questions log
        $this->getDb()->disableQuestionsLog();
    }
}
