<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Adapters\D7Adapter;
use Arrilot\BitrixModels\Queries\D7Query;
use Exception;

class D7Model extends BaseBitrixModel
{
    const TABLE_CLASS = null;

    /**
     * Adapter to interact with Bitrix D7 API.
     *
     * @var D7Adapter
     */
    protected static $adapter;

    /**
     * Constructor.
     *
     * @param $id
     * @param $fields
     */
    public function __construct($id = null, $fields = null)
    {
        $this->id = $id;
        $this->fill($fields);
        static::instantiateAdapter();
    }
    
    /**
     * Setter for adapter (for testing)
     * @param $adapter
     */
    public static function setAdapter($adapter)
    {
        static::$adapter = $adapter;
    }

    /**
     * Instantiate adapter if it's not instantiated.
     */
    public static function instantiateAdapter()
    {
        if (static::$adapter) {
            return;
        }

        static::$adapter = new D7Adapter(static::tableClass());
    }

    /**
     * Instantiate a query object for the model.
     *
     * @return D7Query
     */
    public static function query()
    {
        return new D7Query(static::$adapter, get_called_class());
    }
    
    /**
     * @return string
     * @throws Exception
     */
    public static function tableClass()
    {
        $tableClass = static::TABLE_CLASS;
        if (!$tableClass) {
            throw new Exception('You must set TABLE_CLASS constant inside a model or override tableClass() method');
        }
    
        return $tableClass;
    }

    /**
     * Internal part of create to avoid problems with static and inheritance
     *
     * @param $fields
     *
     * @throws Exception
     *
     * @return static|bool
     */
    protected static function internalCreate($fields)
    {
        $model = new static(null, $fields);
        
        if ($model->onBeforeSave() === false || $model->onBeforeCreate() === false) {
            return false;
        }

        static::instantiateAdapter();
        $resultObject = static::$adapter->add($fields);
        $result = $resultObject->isSuccess();
        if ($result) {
            $model->setId($resultObject->getId());
        }

        $model->onAfterCreate($result);
        $model->onAfterSave($result);

        if (!$result) {
            throw new Exception(implode('; ', $resultObject->getErrorMessages()));
        }

        return $model;
    }

    /**
     * Delete model
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->onBeforeDelete() === false) {
            return false;
        }
    
        static::instantiateAdapter();
        $resultObject = static::$adapter->delete($this->id);
        $result = $resultObject->isSuccess();

        $this->onAfterDelete($result);

        return $result;
    }

    /**
     * Save model to database.
     *
     * @param array $selectedFields save only these fields instead of all.
     *
     * @return bool
     */
    public function save($selectedFields = [])
    {
        $selectedFields = is_array($selectedFields) ? $selectedFields : func_get_args();

        if ($this->onBeforeSave() === false || $this->onBeforeUpdate() === false) {
            return false;
        }

        $fields = $this->normalizeFieldsForSave($selectedFields);
        static::instantiateAdapter();
        $resultObject = static::$adapter->update($this->id, $fields);
        $result = $resultObject->isSuccess();

        $this->onAfterUpdate($result);
        $this->onAfterSave($result);

        return $result;
    }
    
    /**
     * Determine whether the field should be stopped from passing to "update".
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $selectedFields
     *
     * @return bool
     */
    protected function fieldShouldNotBeSaved($field, $value, $selectedFields)
    {
        return (!empty($selectedFields) && !in_array($field, $selectedFields)) || $field === 'ID';
    }
}
