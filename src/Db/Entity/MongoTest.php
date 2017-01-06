<?php
/**
 * Test - Mongo entity test and example use
 */

namespace Db\Entity;

use Db\Model\Test;
use Util\Date;

class MongoTest extends MongoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'test',
            'primary_fields' => [
                '_id',
            ],
            'fields' => [
                '_id' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                        EntityAbstract::OPT_AUTO_ID,
                    ],
                ],
                'a' => [
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                    'alias' => 'c1',
                    'options' => [
                        EntityAbstract::OPT_UNSIGNED,
                    ],
                ],
                'b' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    'alias' => 'c2',
                ],
                'c' => [
                    'type' => EntityAbstract::TYPE_DATE,
                    'default' => Date::DATE_ZERO,
                    'alias' => 'c3',
                ],
                'd' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'alias' => 'c4',
                ],
                'e' => [
                    'type' => EntityAbstract::TYPE_DATETIME,
                    'default' => Date::DATETIME_ZERO,
                    'alias' => 'c5',
                    'options' => [
                        EntityAbstract::OPT_INSERT_DATETIME,
                        EntityAbstract::OPT_UPDATE_DATETIME,
                    ],
                ],
                'f' => [
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                    'alias' => 'c6',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_LIST,
                    ],
                ],
                'g' => [
                    'type' => EntityAbstract::TYPE_BINARY,
                    'default' => '',
                    'alias' => 'c7',
                    'options' => [
                        EntityAbstract::OPT_NO_DIFF,
                        EntityAbstract::OPT_SERIALIZE_LIST,
                        EntityAbstract::OPT_SERIALIZE_GZIP,
                    ],
                ],
                'h' => [
                    'type' => EntityAbstract::TYPE_MONGO_ID,
                    'default' => '',
                    'alias' => 'c8',
                ],
            ],
        ];
    }

    /**
     * @param array $data
     * @return Test
     */
    public function createTest(array $data)
    {
        return parent::createModel($data);
    }

    /**
     * @param int $id
     * @param bool [$asserted]
     * @return Test
     */
    public function getTest($id, $asserted = false)
    {
        return parent::getModel(['_id' => $id], $asserted);
    }

    /**
     * @param int $id
     * @return Test
     */
    public function getTestAsserted($id)
    {
        return $this->getTest($id, true);
    }

    /**
     * @return \Db\Util\Result
     */
    public function testFind()
    {
        return $this->find(
            ['a' => ['$gt' => 0]],
            ['_id', 'a'], [
                'limit' => 2,
                'sort' => ['a' => 1],
                'skip' => 1,
            ]
        );
    }

    /**
     * @return \Db\Util\Result
     */
    public function testFindOne()
    {
        return $this->findOne(['a' => ['$gt' => 0]],['_id', 'a']);
    }

    /**
     * @return \Db\Util\Result
     */
    public function testInsert()
    {
        return $this->insert(['a' => 66]);
    }

    /**
     * @return \Db\Util\Result
     */
    public function testBatchInsert()
    {
        return $this->batchInsert([
            ['a' => 55],
            ['a' => 77]
        ]);
    }

    /**
     * @return \Db\Util\Result
     */
    public function testRemove()
    {
        return $this->remove(['a' => 44], ['limit' => 1]);
    }

    /**
     * @return \Db\Util\Result
     */
    public function testUpdate()
    {
        return $this->modify(['a' => 66], ['$set' => ['a' => 67]], ['multi' => true]);
    }

    /**
     * @return \Db\Util\Result
     */
    public function testCount()
    {
        return $this->count(['a' => 55]);
    }

    /**
     * @return \Db\Util\Result
     */
    public function testFindAndModify()
    {
        return $this->findAndModify(
            ['a' => 55],
            ['$set' => ['a' => 44]],
            ['a'],
            ['new' => true]
        );
    }
}
