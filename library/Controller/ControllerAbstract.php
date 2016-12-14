<?php
/**
 * Abstract controller
 */

namespace Controller;

use ArcomageException as Exception;

abstract class ControllerAbstract extends \ControllerAbstract
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
     * @return \Db\Model\Setting
     */
    protected function getCurrentSettings()
    {
        // guest
        if (!$this->isSession()) {
            return $this->dbEntity()->setting()->getGuestSettings();
        }

        return $this->dbEntity()->setting()->getSettingAsserted(
            $this->getCurrentPlayer()->getUsername()
        );
    }

    /**
     * Execute controller action
     * @param string $action
     * @throws Exception
     * @return Result
     */
    public function executeAction($action)
    {
        // check action validity
        if (!method_exists($this, $action)) {
            throw new Exception('action not found ' . $action);
        }

        // clear result
        $this->result = null;

        try {
            // execute action
            $this->$action();
        }
        catch (Exception $e) {
            // log error if necessary
            if ($e->getCode() == Exception::ERROR) {
                $this->getDic()->logger()->logException($e);
            }

            $this->result()->setError($e->getMessage());
        }

        return $this->result();
    }
}
