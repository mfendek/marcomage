<?php
/**
 * Abstract service
 * Used to structure relevant parts of code together in one file
 */

namespace Service;

abstract class ServiceAbstract
{
    /**
     * DIC reference
     * @var \Dic
     */
    protected $dic;

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
}
