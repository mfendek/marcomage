<?php
/**
 * File specific writer
 */

namespace Writer;

class File extends WriterAbstract
{
    /**
     * @param array $config log specific config
     * @param array $data
     */
    public function log(array $config, array $data)
    {
        // process large uri
        if (isset($data['uri']) && mb_strlen($data['uri']) > 200) {
            $data['uri'] = mb_substr($data['uri'], 0, 200);
        }

        $message = [];
        foreach ($data as $key => $value) {
            $message[]= $key . ' : ' . $value;
        }

        error_log(implode(', ', $message));
    }
}
