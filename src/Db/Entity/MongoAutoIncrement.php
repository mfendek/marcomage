<?php
/**
 * Auto increment entity
 * used only for auto increment emulation
 */

namespace Db\Entity;

class MongoAutoIncrement extends MongoAbstract
{
    /**
     * DB schema
     * @return array
     */
    protected function schema()
    {
        return [
            'entity_name' => 'auto_increment',
            'primary_fields' => [
                '_id',
            ],
            'fields' => [
                '_id' => [
                    // entity name (which uses increment feature)
                    'type' => EntityAbstract::TYPE_STRING,
                    'default' => '',
                ],
                'counter' => [
                    // auto increment counter
                    'type' => EntityAbstract::TYPE_INT,
                    'default' => 0,
                ],
            ],
        ];
    }

    /**
     * Next Id - determine next sequence Id for specified collection
     * @param string $entityName entity name
     * @return \Db\Util\Result
     */
    public function nextId($entityName)
    {
        return $this->findAndModify(['_id' => $entityName], ['$inc' => ['counter' => 1]], ['counter'], ['new' => true]);
    }
}
