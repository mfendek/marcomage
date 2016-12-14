<?php
/**
 * Generic DB writer
 */

namespace Writer;

use Db\Util\Factory;

abstract class WriterAbstract
{
    /**
     * DB util factory
     * @var Factory
     */
    protected $factory;

    /**
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param array $config log specific config
     * @param array $data
     */
    abstract public function log(array $config, array $data);
}
