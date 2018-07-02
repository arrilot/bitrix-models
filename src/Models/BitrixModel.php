<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Exceptions\ExceptionFromBitrix;
use Arrilot\BitrixModels\Queries\BaseQuery;
use LogicException;

abstract class BitrixModel extends BaseBitrixModel
{
    /**
     * Bitrix entity object.
     *
     * @var object
     */
    public static $bxObject;

    /**
     * Corresponding object class name.
     *
     * @var string
     */
    protected static $objectClass = '';

    /**
     * Fetch method and parameters.
     *
     * @var array|string
     */
    public static $fetchUsing = [
        'method' => 'Fetch',
        'params' => [],
    ];

    /**
     * Constructor.
     *
     * @param $id
     * @param $fields
     */
    public function __construct($id = null, $fields = null)
    {
        static::instantiateObject();

        $this->id = $id;

        $this->fill($fields);
    }

    /**
     * Activate model.
     *
     * @return bool
     */
    public function activate()
    {
        $this->fields['ACTIVE'] = 'Y';

        return $this->save(['ACTIVE']);
    }

    /**
     * Deactivate model.
     *
     * @return bool
     */
    public function deactivate()
    {
        $this->fields['ACTIVE'] = 'N';

        return $this->save(['ACTIVE']);
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

        $bxObject = static::instantiateObject();
        $id = static::internalDirectCreate($bxObject, $model->fields);
        $model->setId($id);
        
        $result = $id ? true : false;

        $model->setEventErrorsOnFail($result, $bxObject);
        $model->onAfterCreate($result);
        $model->onAfterSave($result);
        $model->resetEventErrors();
        $model->throwExceptionOnFail($result, $bxObject);

        return $model;
    }

    public static function internalDirectCreate($bxObject, $fields)
    {
        return $bxObject->add($fields);
    }

    /**
     * Delete model.
     *
     * @return bool
     * @throws ExceptionFromBitrix
     */
    public function delete()
    {
        if ($this->onBeforeDelete() === false) {
            return false;
        }

        $result = static::$bxObject->delete($this->id);

        $this->setEventErrorsOnFail($result, static::$bxObject);
        $this->onAfterDelete($result);
        $this->resetEventErrors();
        $this->throwExceptionOnFail($result, static::$bxObject);

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
        $fieldsSelectedForSave = is_array($selectedFields) ? $selectedFields : func_get_args();
        $this->fieldsSelectedForSave = $fieldsSelectedForSave;
        if ($this->onBeforeSave() === false || $this->onBeforeUpdate() === false) {
            $this->fieldsSelectedForSave = [];
            return false;
        } else {
            $this->fieldsSelectedForSave = [];
        }

        $fields = $this->normalizeFieldsForSave($fieldsSelectedForSave);
        $result = $fields === null
            ? true
            : $this->internalUpdate($fields, $fieldsSelectedForSave);

        $this->setEventErrorsOnFail($result, static::$bxObject);
        $this->onAfterUpdate($result);
        $this->onAfterSave($result);
        $this->resetEventErrors();
        $this->throwExceptionOnFail($result, static::$bxObject);

        return $result;
    }

    /**
     * @param $fields
     * @param $fieldsSelectedForSave
     * @return bool
     */
    protected function internalUpdate($fields, $fieldsSelectedForSave)
    {
        return !empty($fields) ? static::$bxObject->update($this->id, $fields) : false;
    }

    /**
     * Scope to get only active items.
     *
     * @param BaseQuery $query
     *
     * @return BaseQuery
     */
    public function scopeActive($query)
    {
        $query->filter['ACTIVE'] = 'Y';

        return $query;
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
        $blacklistedFields = [
            'ID',
            'IBLOCK_ID',
            'GROUPS',
        ];

        return (!empty($selectedFields) && !in_array($field, $selectedFields))
            || in_array($field, $blacklistedFields)
            || ($field[0] === '~')
            || (substr($field, 0, 9) === 'PROPERTY_')
            || (is_array($this->original) && array_key_exists($field, $this->original) && $this->original[$field] === $value);
    }

    /**
     * Instantiate bitrix entity object.
     *
     * @throws LogicException
     *
     * @return object
     */
    public static function instantiateObject()
    {
        if (static::$bxObject) {
            return static::$bxObject;
        }

        if (class_exists(static::$objectClass)) {
            return static::$bxObject = new static::$objectClass();
        }

        throw new LogicException('Object initialization failed');
    }

    /**
     * Destroy bitrix entity object.
     *
     * @return void
     */
    public static function destroyObject()
    {
        static::$bxObject = null;
    }

    /**
     * Set eventErrors field on error.
     *
     * @param bool $result
     * @param object $bxObject
     */
    protected function setEventErrorsOnFail($result, $bxObject)
    {
        if (!$result) {
            $this->eventErrors = (array) $bxObject->LAST_ERROR;
        }
    }

    /**
     * Throw bitrix exception on fail
     *
     * @param bool $result
     * @param object $bxObject
     * @throws ExceptionFromBitrix
     */
    protected function throwExceptionOnFail($result, $bxObject)
    {
        if (!$result) {
            throw new ExceptionFromBitrix($bxObject->LAST_ERROR);
        }
    }
}
