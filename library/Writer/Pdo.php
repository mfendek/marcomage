<?php
/**
 * PDO specific DB writer
 */

namespace Writer;

class Pdo extends WriterAbstract
{
    /**
     * @param array $config log specific config
     * @param array $data
     */
    public function log(array $config, array $data)
    {
        $db = $this->factory->pdo();

        $entityName = $config['entity_name'];

        // process large uri
        if (isset($data['uri']) && mb_strlen($data['uri']) > 200) {
            $data['uri'] = mb_substr($data['uri'], 0, 200);
        }

        $fields = array();
        $placeholders = array();
        $params = array();
        foreach ($data as $key => $value) {
            $fields[] = '`'.$key.'`';
            $placeholders[] = '?';
            $params[] = $value;
        }

        $db->query('INSERT INTO `'.$entityName.'` ('.implode(',', $fields).') VALUES ('.implode(',', $placeholders).')', $params);
    }
}
