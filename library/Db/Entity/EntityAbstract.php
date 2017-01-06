<?php
/**
 * Abstract DB entity (mapper and DAO wrapper)
 * Provides common functionality for specific Database entity classes
 */

namespace Db\Entity;

use ArcomageException as Exception;
use Db\Model\ModelAbstract;
use Db\Util\UtilAbstract;
use Util\Rename;

abstract class EntityAbstract
{
    /**
     * comma-separated list serialization
     */
    const OPT_SERIALIZE_LIST = 'opt_serialize_list';

    /**
     * custom tile serialization
     */
    const OPT_SERIALIZE_TILES = 'opt_serialize_tiles';

    /**
     * JSON serialization
     */
    const OPT_SERIALIZE_JSON = 'opt_serialize_json';

    /**
     * key value pair serialization
     */
    const OPT_SERIALIZE_KEY_VAL = 'opt_serialize_key_val';

    /**
     * PHP native serialization
     */
    const OPT_SERIALIZE_PHP = 'opt_serialize_php';

    /**
     * Gz compress serialization
     */
    const OPT_SERIALIZE_GZIP = 'opt_serialize_gzip';

    /**
     * disabled difference checking when determining dirty field
     */
    const OPT_NO_DIFF = 'opt_no_diff';

    /**
     * unsigned (applicable only for integers)
     */
    const OPT_UNSIGNED = 'opt_unsigned';

    /**
     * indicates that primary id is provided by DB on model insertion (applicable only for primary key)
     */
    const OPT_AUTO_ID = 'opt_auto_id';

    /**
     * fill current datetime automatically on insert operation (applicable only for datetime)
     */
    const OPT_INSERT_DATETIME = 'opt_insert_datetime';

    /**
     * fill current datetime automatically on update operation (applicable only for datetime)
     */
    const OPT_UPDATE_DATETIME = 'opt_update_datetime';

    const TYPE_STRING = 'type_string';
    const TYPE_INT = 'type_int';
    const TYPE_DATE = 'type_date';
    const TYPE_DATETIME = 'type_datetime';
    const TYPE_BINARY = 'type_binary';
    const OPERATION_NONE = 'operation_none';
    const OPERATION_CREATE = 'operation_create';
    const OPERATION_READ = 'operation_read';
    const OPERATION_UPDATE = 'operation_update';
    const OPERATION_DELETE = 'operation_delete';

    /**
     * Models identity map
     * @var array
     */
    protected $models = array();

    /**
     * DB util
     * @var UtilAbstract
     */
    protected $db;

    /**
     * Internal config (usage depends on specific DB entity)
     * @var array
     */
    protected $config = array();

    /**
     * @param UtilAbstract $db
     * @param array [$config]
     */
    public function __construct(UtilAbstract $db, array $config = array())
    {
        $this->db = $db;
        $this->config = $config;
    }

    /**
     * Serialize field value based on specified serialization type
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function serializeValue($value, $type)
    {
        // case 1: comma-separated list
        if ($type == self::OPT_SERIALIZE_LIST) {
            $value = (count($value) > 0) ? implode(',', $value) : '';
        }
        // case 2: JSON
        elseif ($type == self::OPT_SERIALIZE_JSON) {
            $value = json_encode($value);
        }
        // case 3: serialized tiles
        elseif ($type == self::OPT_SERIALIZE_TILES) {
            $value = (count($value) > 0) ? implode(';', $value) : '';
        }
        // case 4: serialized key value pairs
        elseif ($type == self::OPT_SERIALIZE_KEY_VAL) {
            if (count($value) > 0) {
                $serialized = array();
                foreach ($value as $key => $val) {
                    $serialized[] = $key.','.$val;
                }

                $value = implode(';', $serialized);
            }
            else {
                $value = '';
            }
        }
        // case 5: serialized PHP native
        elseif ($type == self::OPT_SERIALIZE_PHP) {
            $value = serialize($value);
        }
        // case 6: serialized GZIP
        elseif ($type == self::OPT_SERIALIZE_GZIP) {
            $value = gzcompress($value);
        }

        return $value;
    }

    /**
     * De-serialize field value based on specified serialization type
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private function deserializeValue($value, $type)
    {
        // case 1: comma-separated list
        if ($type == self::OPT_SERIALIZE_LIST) {
            $value = ($value != '') ? explode(',', $value) : array();
        }
        // case 2: JSON
        elseif ($type == self::OPT_SERIALIZE_JSON) {
            $value = json_decode($value, true);
        }
        // case 3: serialized tiles
        elseif ($type == self::OPT_SERIALIZE_TILES) {
            $value = ($value != '') ? explode(';', $value) : array();
        }
        // case 4: serialized key value pairs
        elseif ($type == self::OPT_SERIALIZE_KEY_VAL) {
            if ($value != '') {
                $deSerialized = array();
                foreach (explode(';', $value) as $pair) {
                    $pair = explode(',', $pair);
                    $pairKey = $pair[0];
                    $pairValue = $pair[1];
                    $deSerialized[$pairKey] = $pairValue;
                }

                $value = $deSerialized;
            }
            else {
                $value = array();
            }
        }
        // case 5: serialized native PHP
        elseif ($type == self::OPT_SERIALIZE_PHP) {
            $value = unserialize($value);
        }
        // case 6: serialized GZIP
        elseif ($type == self::OPT_SERIALIZE_GZIP) {
            $value = gzuncompress($value);
        }

        return $value;
    }

    /**
     * Serialize model data
     * @param array $data
     * @return array
     */
    private function serializeModelData(array $data)
    {
        foreach ($this->fieldNames() as $name) {
            // field is not present in the changed data - nothing to do
            if (!isset($data[$name])) {
                continue;
            }

            $data[$name] = $this->serializeField($name, $data[$name]);
        }

        return $data;
    }

    /**
     * Deserialize model data (also add defaults)
     * @param array $data
     * @return array
     */
    private function deserializeModelData(array $data)
    {
        foreach ($this->fieldNames() as $name) {
            // add default value for missing fields
            $value = (isset($data[$name])) ? $data[$name] : $this->fieldDefault($name);

            $data[$name] = $this->deserializeField($name, $value);
        }

        return $data;
    }

    /**
     * Setup model
     * @param array $data
     * @return ModelAbstract
     */
    private function setupModel(array $data)
    {
        // deserialize data
        $data = $this->deserializeModelData($data);

        // determine model name
        $modelName = '\Db\Model\\'. Rename::underscoreToClassName($this->modelName());

        /* @var ModelAbstract $model */
        $model = new $modelName($this);

        // load model data and mark as created
        $model->fromArray($data);

        // store model in identity map
        $this->models[$model->modelId()] = $model;

        return $model;
    }

    /**
     * Model name (defaults to entity name if not specified)
     * @return string
     */
    private function modelName()
    {
        $schema = $this->schema();

        return !empty($schema['model_name']) ? $schema['model_name'] : $this->entityName();
    }

    /**
     * DB schema
     * @return array
     */
    abstract protected function schema();

    // sample schema
//        $schema = [
//            'entity_name' => 'some_entity', // mandatory
//            'primary_fields' => [ // mandatory
//                'some_field',
//            ],
//            'fields' => [
//                'some_field' => [
//                    'type' => '',
//                    'default' => '', // mandatory
//                    'alias' => '',
//                    'options' => [
//                        'serialize_list',
//                        'no_diff',
//                    ],
//                ],
//            ],
//        ];

    /**
     * Field options
     * @param string $field
     * @return array
     */
    protected function fieldOptions($field)
    {
        $schema = $this->schema();

        $options = (isset($schema['fields'][$field]['options'])) ? $schema['fields'][$field]['options'] : array();

        return $options;
    }

    /**
     * Field alias
     * @param string $field
     * @param bool $forceAlias force alias flag
     * @return string
     */
    protected function fieldAlias($field, $forceAlias = false)
    {
        // aliases can be disabled in environment config
        if (!$forceAlias && (!isset($this->config['aliases']) || !$this->config['aliases'])) {
            return $field;
        }

        $schema = $this->schema();

        $alias = (isset($schema['fields'][$field]['alias'])) ? $schema['fields'][$field]['alias'] : $field;

        return $alias;
    }

    /**
     * Find field that has auto id option
     * returns empty string if none is found
     * @return string
     */
    protected function findAutoIdField()
    {
        foreach ($this->primaryFields() as $field) {
            if ($this->fieldHasOption($field, self::OPT_AUTO_ID)) {
                return $field;
            }
        }

        return '';
    }

    /**
     * Insert provided data
     * @param array $data
     * @return \Db\Util\Result
     */
    abstract protected function create(array $data);

    /**
     * Read data based on specified conditions
     * @param array $conditions
     * @return \Db\Util\Result
     */
    abstract protected function read(array $conditions);

    /**
     * Update data based on specified conditions and changes
     * @param array $conditions
     * @param array $data
     * @return \Db\Util\Result
     */
    abstract protected function update(array $conditions, array $data);

    /**
     * Delete data based on specified conditions
     * @param array $conditions
     * @return \Db\Util\Result
     */
    abstract protected function delete(array $conditions);

    /**
     * PTK fields
     * @return array
     */
    protected function ptkFields()
    {
        $schema = $this->schema();

        return (isset($schema['ptk_fields'])) ? $schema['ptk_fields'] : [];
    }

    /**
     * Serialize field value
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function serializeField($name, $value)
    {
        // NOTE: serialization types are not strictly mutually exclusive, but not all combinations make sense
        // it's important to maintain sane order when combining serialization types

        // comma-separated list serialization
        if ($this->fieldHasOption($name, self::OPT_SERIALIZE_LIST)) {
            $value = $this->serializeValue($value, self::OPT_SERIALIZE_LIST);
        }
        // JSON serialization
        elseif ($this->fieldHasOption($name, self::OPT_SERIALIZE_JSON)) {
            $value = $this->serializeValue($value, self::OPT_SERIALIZE_JSON);
        }
        // serialized tiles
        elseif ($this->fieldHasOption($name, self::OPT_SERIALIZE_TILES)) {
            $value = $this->serializeValue($value, self::OPT_SERIALIZE_TILES);
        }
        // serialized key value pairs
        elseif ($this->fieldHasOption($name, self::OPT_SERIALIZE_KEY_VAL)) {
            $value = $this->serializeValue($value, self::OPT_SERIALIZE_KEY_VAL);
        }
        // serialized PHP native
        elseif ($this->fieldHasOption($name, self::OPT_SERIALIZE_PHP)) {
            $value = $this->serializeValue($value, self::OPT_SERIALIZE_PHP);
        }

        // serialized GZIP
        if ($this->fieldHasOption($name, self::OPT_SERIALIZE_GZIP)) {
            $value = $this->serializeValue($value, self::OPT_SERIALIZE_GZIP);
        }

        return $value;
    }

    /**
     * De-serialize field value
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    public function deserializeField($name, $value)
    {
        // NOTE: serialization types are not mutually exclusive, but not all combinations make sense
        // it's important to maintain sane order when combining serialization types

        // serialized GZIP
        if ($this->fieldHasOption($name, self::OPT_SERIALIZE_GZIP)) {
            $value = ($value !== '') ? $this->deserializeValue($value, self::OPT_SERIALIZE_GZIP) : $value;
        }

        // comma-separated list serialization
        if ($this->fieldHasOption($name, self::OPT_SERIALIZE_LIST)) {
            $value = $this->deserializeValue($value, self::OPT_SERIALIZE_LIST);
        }
        // JSON serialization
        elseif ($this->fieldHasOption($name, self::OPT_SERIALIZE_JSON)) {
            $value = $this->deserializeValue($value, self::OPT_SERIALIZE_JSON);
        }
        // serialized tiles
        elseif ($this->fieldHasOption($name, self::OPT_SERIALIZE_TILES)) {
            $value = $this->deserializeValue($value, self::OPT_SERIALIZE_TILES);
        }
        // serialized key value pairs
        elseif ($this->fieldHasOption($name, self::OPT_SERIALIZE_KEY_VAL)) {
            $value = $this->deserializeValue($value, self::OPT_SERIALIZE_KEY_VAL);
        }
        // serialized PHP native
        elseif ($this->fieldHasOption($name, self::OPT_SERIALIZE_PHP)) {
            $value = $this->deserializeValue($value, self::OPT_SERIALIZE_PHP);
        }

        return $value;
    }

    /**
     * Return all available models (used for DB sync)
     */
    public function models()
    {
        return $this->models;
    }

    /**
     * Entity name
     * @return string
     */
    public function entityName()
    {
        $schema = $this->schema();

        return $schema['entity_name'];
    }

    /**
     * Primary fields
     * @return array
     */
    public function primaryFields()
    {
        $schema = $this->schema();

        return $schema['primary_fields'];
    }

    /**
     * Field names
     * @return array
     */
    public function fieldNames()
    {
        $schema = $this->schema();

        return array_keys($schema['fields']);
    }

    /**
     * Field type
     * @param string $field
     * @return string
     */
    public function fieldType($field)
    {
        $schema = $this->schema();

        $type = (isset($schema['fields'][$field]['type'])) ? $schema['fields'][$field]['type'] : '';

        return $type;
    }

    /**
     * Field default
     * @param string $field
     * @return mixed
     */
    public function fieldDefault($field)
    {
        $schema = $this->schema();

        return $schema['fields'][$field]['default'];
    }

    /**
     * Create field map
     * @param $forceAlias
     * @return array
     */
    public function fieldMap($forceAlias)
    {
        $map = array();
        foreach ($this->fieldNames() as $field) {
            $map[$this->entityName().'_'.$field] = $this->fieldAlias($field, $forceAlias);
        }

        return $map;
    }

    /**
     * Determine if specified field has option
     * @param string $field
     * @param $option
     * @return bool
     */
    public function fieldHasOption($field, $option)
    {
        return in_array($option, $this->fieldOptions($field));
    }

    /**
     * Validate field value based on field configuration
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    public function validateFieldValue($field, $value)
    {
        // validation cases
        if ($this->fieldType($field) == self::TYPE_INT) {
            // basic integer validation
            if (!is_numeric($value)) {
                return false;
            }

            // unsigned validation
            if (in_array(self::OPT_UNSIGNED, $this->fieldOptions($field)) && $value < 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return model id based on specified data
     * @param array $data
     * @throws Exception
     * @return string
     */
    public function modelId(array $data)
    {
        $modelId = array();
        foreach ($this->primaryFields() as $name) {
            $value = $data[$name];

            // validate integer data type
            if ($this->fieldType($name) == self::TYPE_INT && filter_var($value, FILTER_VALIDATE_INT) === false) {
                throw new Exception(
                    'Invalid primary key value - integer validation failed',
                    Exception::WARNING
                );
            }

            $modelId[] = $value;
        }

        return implode('_', $modelId);
    }

    /**
     * Create new model
     * @param array $data
     * @return mixed
     */
    public function createModel(array $data)
    {
        $model = $this->setupModel($data);
        $model->markCreated();

        return $model;
    }

    /**
     * Get model based on specified conditions
     * @param array $conditions
     * @param bool [$asserted]
     * @throws Exception
     * @return mixed
     */
    public function getModel(array $conditions, $asserted = false)
    {
        $modelId = $this->modelId($conditions);

        // initialize model
        if (!isset($this->models[$modelId])) {
            $result = $this->read($conditions);

            if ($result->isError()) {
                throw new Exception(
                    'failed to load model ('.$this->entityName().') '.print_r($conditions, true)
                );
            }

            if ($result->isNoEffect()) {
                if ($asserted) {
                    throw new Exception(
                        'model not found ('.$this->entityName().') '.print_r($conditions, true),
                        Exception::WARNING
                    );
                }

                return null;
            }

            $model = $this->setupModel($result->data());

            // update model id (may be different because of case sensitivity of the keys)
            $modelId = $model->modelId();
        }

        return $this->models[$modelId];
    }

    /**
     * Get model based on specified conditions (throws on failure)
     * @param array $conditions
     * @return ModelAbstract
     */
    public function getModelAsserted(array $conditions)
    {
        return $this->getModel($conditions, true);
    }

    /**
     * Save model to database
     * @param ModelAbstract $model
     * @return bool
     */
    public function saveModel(ModelAbstract $model)
    {
        $opData = $model->determineOperation();
        $operation = $opData['op'];
        $primaryKeys = $opData['primary'];
        $data = $opData['data'];

        // serialize model data
        $data = $this->serializeModelData($data);

        // case 1: create
        if ($operation == self::OPERATION_CREATE) {
            $autoIdField = $this->findAutoIdField();

            // check if all primary keys are set
            foreach ($this->primaryFields() as $field) {
                // omit primary field that has the auto-id option
                if ($field != $autoIdField && empty($data[$field])) {
                    return false;
                }
            }

            $result = $this->create($data);
            if ($result->isError()) {
                return false;
            }

            // model no longer has created flag
            $model->resetCreated();

            // add new id to auto id field if necessary
            if (!empty($autoIdField) && !empty($result['new_id'])) {
                $model->fromArray(array_merge(
                    $model->toArray(), [$autoIdField => $result['new_id']]
                ));
            }
        }
        // case 2: update
        elseif ($operation == self::OPERATION_UPDATE) {
            $result = $this->update($primaryKeys, $data);
            if ($result->isErrorOrNoEffect()) {
                return false;
            }
        }
        // case 3: delete
        elseif ($operation == self::OPERATION_DELETE) {
            $result = $this->delete($primaryKeys);
            if ($result->isErrorOrNoEffect()) {
                return false;
            }

            // this will put model into 'limbo' mode since it has both flags set
            // any future save() operations will do nothing
            $model->markCreated();
        }

        // cleanup model (data is already saved)
        $model->cleanup();

        return true;
    }

    /**
     * Remove model from identity map
     * Warning: should be used only in specific exceptional cases
     * @param ModelAbstract $model
     */
    public function destroyModel(ModelAbstract $model)
    {
        if (isset($this->models[$model->modelId()])) {
            unset($this->models[$model->modelId()]);
        }
    }
}
