<?php
/**
 * Abstract controller - servers as a parent for both middleware and controllers
 */

use ArcomageException as Exception;

abstract class ControllerAbstract
{
    /**
     * DIC reference
     * @var \Dic
     */
    protected $dic;

    /**
     * Check if all params are present
     * @param array $params
     * @throws Exception
     */
    protected function assertParamsExist(array $params)
    {
        $request = $this->request();

        foreach ($params as $param) {
            if (!isset($request[$param])) {
                throw new Exception('Param (' . $param . ') is missing', Exception::WARNING);
            }
        }
    }

    /**
     * Check if all params are non-empty
     * @param array $params
     * @throws Exception
     */
    protected function assertParamsNonEmpty(array $params)
    {
        $request = $this->request();

        foreach ($params as $param) {
            if (empty($request[$param])) {
                throw new Exception('Param (' . $param . ') is empty', Exception::WARNING);
            }
        }
    }

    /**
     * @return \Dic
     */
    protected function getDic()
    {
        return $this->dic;
    }

    /**
     * @return \Db\Util\Pdo
     */
    protected function getDb()
    {
        return $this->getDic()->dbUtilFactory()->pdo();
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
     * @return array
     */
    protected function request()
    {
        return $this->getDic()->request();
    }

    /**
     * @return \Db\Model\Player
     */
    protected function getCurrentPlayer()
    {
        return $this->getDic()->getPlayer();
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
        return \Db\Model\Player::checkAccess($this->getCurrentPlayer()->getUserType(), $accessRight);
    }

    /**
     * @param \Dic $dic
     */
    public function __construct(\Dic $dic)
    {
        $this->dic = $dic;
    }
}
