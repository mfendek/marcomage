<?php
/**
 * XML reader utility wrapper
 * Provides low level XML reader services
 */

namespace Util;

use ArcomageException as Exception;

class Xml
{
    /**
     * current file name
     * @var string
     */
    protected $file = '';

    /**
     * raw DB resource
     */
    protected $resource;

    /**
     * Initialize DB resource
     */
    protected function init()
    {
        $this->resource = new \XMLReader();
    }

    /**
     * Load DB resource
     */
    protected function loadDb()
    {
        // init DB resource if necessary
        if (empty($this->resource)) {
            $this->init();
        }

        return $this->resource;
    }

    /**
     * Move reader cursor to next node (omit comments, WS and significant WS)
     */
    protected function read()
    {
        while ($this->db()->read()) {
            if (!in_array($this->db()->nodeType, [
                \XMLReader::COMMENT,
                \XMLReader::WHITESPACE,
                \XMLReader::SIGNIFICANT_WHITESPACE
            ])) {
                break;
            }
        }
    }

    /**
     * Return raw initialized DB resource
     * @return \XMLReader
     */
    protected function db()
    {
        $this->loadDb();

        return $this->resource;
    }

    /**
     * @param string $fileName
     * @throws Exception
     */
    protected function openFile($fileName)
    {
        if (!$this->db()->open($fileName)) {
            throw new Exception('failed to open XML file '.$fileName);
        }

        $this->file = $fileName;
    }

    /**
     * Read file
     * @param string $fileName
     * @param string [$nameSpace]
     * @throws Exception
     * @return array found items
     */
    public function readFile($fileName, $nameSpace = '')
    {
        $this->openFile($fileName);

        // read root element
        $this->read();

        // extract root element name
        $elemName = $this->db()->name;

        // search for specified items
        $result = array();

        do {
            // read item element
            $this->read();

            // check end-of-data
            if ($this->db()->nodeType == \XMLReader::END_ELEMENT) {
                break;
            }

            $this->db()->moveToAttribute('id');
            $id = $this->db()->value;

            // extract item data
            $this->db()->moveToElement();
            $data = $this->db()->readInnerXml();
            $data = simplexml_load_string(
                '<'.$elemName.'>'.$data.'</'.$elemName.'>',
                'SimpleXMLElement',
                0,
                $nameSpace
            );
            if (!$data) {
                throw new Exception('failed to load internal XML item id '.$id.' in file '.$this->file);
            }

            // data is indexed by id
            $result[$id] = $data;
        }
        while ($this->db()->next());

        $this->db()->close();

        return $result;
    }
}
