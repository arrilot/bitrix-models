<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\BaseQuery;
use Exception;

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
        
        $bxObject = static::instantiateObject();
        $id = $bxObject->add($fields);
        $model->setId($id);
        
        $result = $id ? true : false;
        
        $model->onAfterCreate($result);
        $model->onAfterSave($result);
        
        if (!$result) {
            throw new Exception($bxObject->LAST_ERROR);
        }
        
        return $model;
    }

    /**
     * Delete model.
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->onBeforeDelete() === false) {
            return false;
        }

        $result = static::$bxObject->delete($this->id);

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
        $result = !empty($fields) ? static::$bxObject->update($this->id, $fields) : false;
        if ($this instanceof ElementModel) {
            $savePropsResult = $this->saveProps($selectedFields);
            $result = $result || $savePropsResult;
        }

        $this->onAfterUpdate($result);
        $this->onAfterSave($result);

        return $result;
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
            'PROPERTIES',
            'GROUPS',
            'PROPERTY_VALUES',
        ];

        return (!empty($selectedFields) && !in_array($field, $selectedFields))
            || in_array($field, $blacklistedFields)
            || (substr($field, 0, 1) === '~')
            || (substr($field, 0, 9) === 'PROPERTY_');
    }

    /**
     * Instantiate bitrix entity object.
     *
     * @throws Exception
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

        throw new Exception('Object initialization failed');
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
}
