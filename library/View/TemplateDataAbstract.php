<?php
/**
 * Abstract template data
 * Used to prepare data used in template rendering
 */

namespace View;

use ArcomageException as Exception;
use Util\Rename;

abstract class TemplateDataAbstract
{
    /**
     * Input data
     * @var array
     */
    private $inputData = array();

    /**
     * DIC reference
     * @var \Dic
     */
    protected $dic;

    /**
     * @return array
     */
    protected function input()
    {
        return $this->inputData;
    }

    /**
     * Check if all data keys are present
     * @param array $keys
     * @throws Exception
     */
    protected function assertInputExist(array $keys)
    {
        $data = $this->input();

        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                throw new Exception('Data key (' . $key . ') is missing', Exception::WARNING);
            }
        }
    }

    /**
     * Check if all data keys are non-empty
     * @param array $keys
     * @throws Exception
     */
    protected function assertInputNonEmpty(array $keys)
    {
        $data = $this->input();

        foreach ($keys as $key) {
            if (empty($data[$key])) {
                throw new Exception('Data key (' . $key . ') is empty', Exception::WARNING);
            }
        }
    }

    /**
     * @param \Dic $dic
     */
    public function __construct(\Dic $dic)
    {
        $this->dic = $dic;
    }

    /**
     * Get data for specified section
     * @param $section
     * @param array [$inputData] input data
     * @throws Exception
     * @return Result
     */
    public function getData($section, array $inputData = [])
    {
        // transform section name
        $section = Rename::inputToActionName($section);

        // check action validity
        if (!method_exists($this, $section)) {
            throw new Exception('section not found ' . $section);
        }

        // set input data
        $this->inputData = $inputData;

        return $this->$section();
    }

    /**
     * @return \Dic
     */
    protected function getDic()
    {
        return $this->dic;
    }

    /**
     * @return \Db\Entity\Factory
     */
    protected function dbEntity()
    {
        return $this->getDic()->dbEntityFactory();
    }

    /**
     * @return \Def\Entity\Factory
     */
    protected function defEntity()
    {
        return $this->getDic()->defEntityFactory();
    }

    /**
     * @return \Service\Factory
     */
    protected function service()
    {
        return $this->getDic()->serviceFactory();
    }

    /**
     * @return \Db\Model\Player
     */
    protected function getCurrentPlayer()
    {
        return $this->getDic()->getPlayer();
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
     * Check if user is logged in
     */
    protected function isSession()
    {
        return $this->getDic()->isSession();
    }

    /**
     * @param string $accessRight
     * @return bool
     */
    protected function checkAccess($accessRight)
    {
        return \Access::checkAccess($this->getCurrentPlayer()->getUserType(), $accessRight);
    }
}
