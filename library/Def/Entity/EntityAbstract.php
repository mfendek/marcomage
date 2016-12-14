<?php
/**
 * Abstract Definition entity
 */

namespace Def\Entity;

abstract class EntityAbstract
{
    /**
     * @var mixed
     */
    protected $db = null;

    /**
     * Initialize DB
     */
    abstract protected function initDb();

    /**
     * @return mixed
     */
    protected function getDb()
    {
        // initialize on first use
        if (empty($this->db)) {
            $this->initDb();
        }

        return $this->db;
    }
}
