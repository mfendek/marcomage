<?php
/**
 * Abstract DB model
 * Provides common functionality for specific Database model classes
 */

namespace Db\Model;

use ArcomageException as Exception;
use Db\Entity\EntityAbstract;
use Util\Date;

abstract class ModelAbstract
{
    /**
     * @var EntityAbstract
     */
    protected $entity;

    /**
     * Initial values of fields
     * @var array
     */
    private $initialValues = array();

    /**
     * Checkpoint values of fields
     * @var array
     */
    private $checkpointValues = array();

    /**
     * Current values of fields
     * @var array
     */
    private $currentValues = array();

    /**
     * Dirty fields markers (used for fields with no diff option)
     * @var array
     */
    private $dirtyFields = array();

    /**
     * Dirty fields markers (checkpoint copy)
     * @var array
     */
    private $dirtyFieldsCheckpoint = array();

    /**
     * Created flag (used to determine save() operation)
     * @var bool
     */
    private $created = false;

    /**
     * Created flag (checkpoint copy)
     * @var bool
     */
    private $createdCheckpoint = false;

    /**
     * Deleted flag (used to determine save() operation)
     */
    private $deleted = false;

    /**
     * Deleted flag (checkpoint copy)
     */
    private $deletedCheckpoint = false;

    /**
     * @param EntityAbstract $entity
     */
    public function __construct(EntityAbstract $entity)
    {
        $this->entity = $entity;
    }

    /**
     * Get specified field value
     * @param string $field
     * @throws \Exception
     * @return mixed
     */
    protected function getFieldValue($field)
    {
        // validate field name
        if (!in_array($field, $this->entity->fieldNames())) {
            throw new Exception('model ('.$this->entity->entityName().') attempting to get invalid field name '.$field);
        }

        return $this->currentValues[$field];
    }

    /**
     * Set specified field value
     * @param string $field
     * @param mixed $value
     * @throws \Exception
     * @return $this
     */
    protected function setFieldValue($field, $value)
    {
        // validate field name
        if (!in_array($field, $this->entity->fieldNames())) {
            throw new Exception('model ('.$this->entity->entityName().') attempting to set invalid field name '.$field);
        }

        // validate field value
        if ($this->entity->fieldType($field) != '' && !$this->entity->validateFieldValue($field, $value)) {
            throw new Exception('model ('.$this->entity->entityName().'):'.$field.' attempting to set invalid field value '.$value);
        }

        // mark dirty the field if no diff option is set
        if ($this->entity->fieldHasOption($field, EntityAbstract::OPT_NO_DIFF)) {
            $this->dirtyFields[$field] = true;
        }

        //set new value to field
        $this->currentValues[$field] = $value;

        return $this;
    }

    /**
     * Return model id
     * @return string
     */
    public function modelId()
    {
        return $this->entity->modelId($this->currentValues);
    }

    /**
     * Load field values from array
     * @param array $data
     * @return $this
     */
    public function fromArray(array $data)
    {
        foreach ($this->entity->fieldNames() as $name) {
            $this->setFieldValue($name, $data[$name]);
        }

        return $this->cleanup();
    }

    /**
     * Export field values to array
     * @return array
     */
    public function toArray()
    {
        $data = array();
        foreach ($this->entity->fieldNames() as $name) {
            $data[$name] = $this->currentValues[$name];
        }

        return $data;
    }

    /**
     * Mark model as created
     * @return $this
     */
    public function markCreated()
    {
        $this->created = true;

        return $this;
    }

    /**
     * Reset created flag
     * @return $this
     */
    public function resetCreated()
    {
        $this->created = false;

        return $this;
    }

    /**
     * Mark model as deleted
     * @return $this
     */
    public function markDeleted()
    {
        $this->deleted = true;

        return $this;
    }

    /**
     * Reset deleted flag
     * @return $this
     */
    public function resetDeleted()
    {
        $this->deleted = false;

        return $this;
    }

    /**
     * Puts model into clean state
     * @return $this
     */
    public function cleanup()
    {
        // reset dirty fields
        $this->dirtyFields = array();
        $this->dirtyFieldsCheckpoint = array();

        // set all fields to current state
        foreach ($this->entity->fieldNames() as $name) {
            $this->initialValues[$name] = $this->currentValues[$name];
            $this->checkpointValues[$name] = $this->entity->serializeField($name, $this->currentValues[$name]);
        }

        // reset all flags to current state
        $this->createdCheckpoint = $this->created;
        $this->deletedCheckpoint = $this->deleted;

        return $this;
    }

    /**
     * Save checkpoint
     * @return $this
     */
    public function checkpoint()
    {
        // store all current fields
        foreach ($this->entity->fieldNames() as $name) {
            $this->checkpointValues[$name] = $this->entity->serializeField($name, $this->currentValues[$name]);
        }

        // store created and deleted flags
        $this->createdCheckpoint = $this->created;
        $this->deletedCheckpoint = $this->deleted;

        // store dirty fields
        $this->dirtyFieldsCheckpoint = $this->dirtyFields;

        return $this;
    }

    /**
     * Rollback to checkpoint
     * @return $this
     */
    public function rollback()
    {
        // restore all current fields
        foreach ($this->entity->fieldNames() as $name) {
            $this->currentValues[$name] = $this->entity->deserializeField($name, $this->checkpointValues[$name]);
        }

        // restore created and deleted flags
        $this->created = $this->createdCheckpoint;
        $this->deleted = $this->deletedCheckpoint;

        // restore dirty fields
        $this->dirtyFields = $this->dirtyFieldsCheckpoint;

        return $this;
    }

    /**
     * Save model
     * @return bool
     */
    public function save()
    {
        $operationData = $this->determineOperation();
        $operation = $operationData['op'];
        $entity = $this->entity;

        // add automatically added values before saving
        foreach ($entity->fieldNames() as $name) {
            // case 1: insert operation
            if ($operation == EntityAbstract::OPERATION_CREATE
                && $entity->fieldType($name) == EntityAbstract::TYPE_DATETIME
                && $entity->fieldHasOption($name, EntityAbstract::OPT_INSERT_DATETIME)) {
                $this->setFieldValue($name, Date::timeToStr());
            }
            // case 2: update operation
            elseif ($operation == EntityAbstract::OPERATION_UPDATE
                && $entity->fieldType($name) == EntityAbstract::TYPE_DATETIME
                && $entity->fieldHasOption($name, EntityAbstract::OPT_UPDATE_DATETIME)) {
                $this->setFieldValue($name, Date::timeToStr());
            }
        }

        return $this->entity->saveModel($this);
    }

    /**
     * Determine what CRUD operation should be used, also provides necessary data
     * @return array
     */
    public function determineOperation()
    {
        // determine what properties need to be saved
        $data = array();
        foreach ($this->entity->fieldNames() as $name) {
            // field is recognized as dirty if one of these conditions are true
            // 1 - model is marked as created
            // 2 - field is explicitly marked as dirty
            // 3 - field has a different value compared to initial state
            if ($this->created || isset($this->dirtyFields[$name]) || $this->currentValues[$name] != $this->initialValues[$name]) {
                $data[$name] = $this->currentValues[$name];
            }
        }

        // determine primary keys
        $primaryKeys = array();
        foreach ($this->entity->primaryFields() as $name) {
            $primaryKeys[$name] = $this->currentValues[$name];
        }

        // case 1: both created and deleted flags are set
        if ($this->created && $this->deleted) {
            // we have nothing to do
            $operation = EntityAbstract::OPERATION_NONE;
            $primaryKeys = array();
            $data = array();
        }
        // case 2: created flag is set
        elseif ($this->created && !$this->deleted) {
            // create operation
            $operation = EntityAbstract::OPERATION_CREATE;
            $primaryKeys = array();
        }
        // case 3: deleted flag is set
        elseif (!$this->created && $this->deleted) {
            // delete operation
            $operation = EntityAbstract::OPERATION_DELETE;
            $data = array();
        }
        // case 4: no flag is set - update data
        elseif (count($data) > 0) {
            $operation = EntityAbstract::OPERATION_UPDATE;
        }
        // case 5: no flag is set but nothing is changed
        else {
            // we have nothing to do
            $operation = EntityAbstract::OPERATION_NONE;
            $primaryKeys = array();
            $data = array();
        }

        return [
            'op' => $operation,
            'primary' => $primaryKeys,
            'data' => $data,
        ];
    }

    /**
     * Destroy model
     * Warning: should be used only in specific exceptional cases
     * @return bool
     */
    public function destroy()
    {
        $this->entity->destroyModel($this);
    }
}
