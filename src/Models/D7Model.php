<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Adapters\D7Adapter;
use Arrilot\BitrixModels\Exceptions\ExceptionFromBitrix;
use Arrilot\BitrixModels\Queries\D7Query;
use LogicException;

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
     *
     * @return D7Adapter
     */
    public static function instantiateAdapter()
    {
        if (static::$adapter) {
            return static::$adapter;
        }

        return static::$adapter = new D7Adapter(static::tableClass());
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
     * @throws LogicException
     */
    public static function tableClass()
    {
        $tableClass = static::TABLE_CLASS;
        if (!$tableClass) {
            throw new LogicException('You must set TABLE_CLASS constant inside a model or override tableClass() method');
        }
    
        return $tableClass;
    }

    /**
     * Internal part of create to avoid problems with static and inheritance
     *
     * @param $fields
     *
     * @throws ExceptionFromBitrix
     *
     * @return static|bool
     */
    protected static function internalCreate($fields)
    {
        $model = new static(null, $fields);

        if ($model->onBeforeSave() === false || $model->onBeforeCreate() === false) {
            return false;
        }

        $resultObject = static::instantiateAdapter()->add($model->fields);
        $result = $resultObject->isSuccess();
        if ($result) {
            $model->setId($resultObject->getId());
        }

        $model->setEventErrorsOnFail($resultObject);
        $model->onAfterCreate($result);
        $model->onAfterSave($result);
        $model->throwExceptionOnFail($resultObject);

        return $model;
    }

    /**
     * Delete model
     *
     * @return bool
     * @throws ExceptionFromBitrix
     */
    public function delete()
    {
        if ($this->onBeforeDelete() === false) {
            return false;
        }

        $resultObject = static::instantiateAdapter()->delete($this->id);
        $result = $resultObject->isSuccess();

        $this->setEventErrorsOnFail($resultObject);
        $this->onAfterDelete($result);
        $this->throwExceptionOnFail($resultObject);

        return $result;
    }
    
    /**
     * Save model to database.
     *
     * @param array $selectedFields save only these fields instead of all.
     * @return bool
     * @throws ExceptionFromBitrix
     */
    public function save($selectedFields = [])
    {
        $this->fieldsSelectedForSave = is_array($selectedFields) ? $selectedFields : func_get_args();
        if ($this->onBeforeSave() === false || $this->onBeforeUpdate() === false) {
            return false;
        }

        $fields = $this->normalizeFieldsForSave($this->fieldsSelectedForSave);
        $resultObject = static::instantiateAdapter()->update($this->id, $fields);
        $result = $resultObject->isSuccess();

        $this->setEventErrorsOnFail($resultObject);
        $this->onAfterUpdate($result);
        $this->onAfterSave($result);
        $this->throwExceptionOnFail($resultObject);

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

    /**
     * Throw bitrix exception on fail
     *
     * @param \Bitrix\Main\Entity\Result $resultObject
     * @throws ExceptionFromBitrix
     */
    protected function throwExceptionOnFail($resultObject)
    {
        if (!$resultObject->isSuccess()) {
            throw new ExceptionFromBitrix(implode('; ', $resultObject->getErrorMessages()));
        }
    }

    /**
     * Set eventErrors field on error.
     *
     * @param \Bitrix\Main\Entity\Result $resultObject
     */
    protected function setEventErrorsOnFail($resultObject)
    {
        if (!$resultObject->isSuccess()) {
            $this->eventErrors = (array) $resultObject->getErrorMessages();
        }
    }
}
