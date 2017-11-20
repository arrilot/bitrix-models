<?php

namespace Arrilot\BitrixModels\Models;


use Arrilot\BitrixModels\Models\ArrayableModel;
use Arrilot\BitrixModels\ModelEventsTrait;
use Bitrix\Highloadblock\HighloadBlockTable;
use Arrilot\BitrixModels\Queries\HLQuery;

class HLModel extends ArrayableModel
{
    use ModelEventsTrait;

    /**
     * Bitrix entity object.
     *
     * @var object
     */
    public static $bxObject;

    protected static $tableName;


    /**
     * Have fields been already fetched from DB?
     *
     * @var bool
     */
    protected $fieldsAreFetched = false;

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

    static public function tableName()
    {
        return static::$tableName;
    }

    /**
     * Get all model attributes from cache or database.
     *
     * @return array
     */
    public function get()
    {
        $this->load();

        return $this->fields;
    }

    /**
     * Load model fields from database if they are not loaded yet.
     *
     * @return $this
     */
    public function load()
    {
        if (!$this->fieldsAreFetched) {
            $this->refresh();
        }

        return $this;
    }

    /**
     * Get model fields from cache or database.
     *
     * @return array
     */
    public function getFields()
    {
        if ($this->fieldsAreFetched) {
            return $this->fields;
        }

        return $this->refreshFields();
    }

    /**
     * Refresh model from database and place data to $this->fields.
     *
     * @return array
     */
    public function refresh()
    {
        return $this->refreshFields();
    }

    /**
     * Refresh model fields and save them to a class field.
     *
     * @return array
     */
    public function refreshFields()
    {
        if ($this->id === null) {
            return $this->fields = [];
        }

        $this->fields = static::query()->getById($this->id)->fields;

        $this->fieldsAreFetched = true;

        return $this->fields;
    }

    /**
     * Fill model fields if they are already known.
     * Saves DB queries.
     *
     * @param array $fields
     *
     * @return void
     */
    public function fill($fields)
    {
        if (!is_array($fields)) {
            return;
        }

        if (isset($fields['ID'])) {
            $this->id = $fields['ID'];
        }

        $this->fields = $fields;

        $this->fieldsAreFetched = true;

        if (method_exists($this, 'afterFill')) {
            $this->afterFill();
        }
    }


    /**
     * Create new item in database.
     *
     * @param $fields
     *
     * @throws Exception
     *
     * @return static|bool
     */
    public static function create($fields)
    {
        return static::internalCreate($fields);
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
        $id = $bxObject::add($fields);
        $model->setId($id);

        $result = $id ? true : false;

        $model->onAfterCreate($result);
        $model->onAfterSave($result);

        return $model;
    }

    /**
     * Get count of items that match $filter.
     *
     * @param array $filter
     *
     * @return int
     */
    public static function count(array $filter = [])
    {
        return static::query()->filter($filter)->count();
    }

    /**
     * Get item by its id.
     *
     * @param int $id
     *
     * @return static|bool
     */
    public static function find($id)
    {
        return static::query()->getById($id);
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

        $result = static::$bxObject::delete($this->id);

        $this->onAfterDelete($result);

        return $result;
    }

    /**
     * Update model.
     *
     * @param array $fields
     *
     * @return bool
     */
    public function update(array $fields = [])
    {
        $keys = [];
        foreach ($fields as $key => $value) {
            array_set($this->fields, $key, $value);
            $keys[] = $key;
        }

        return $this->save($keys);
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
        $result = !empty($fields) ? static::$bxObject::update($this->id, $fields) : false;

        $this->onAfterUpdate($result);
        $this->onAfterSave($result);

        return $result;
    }

    /**
     * Create an array of fields that will be saved to database.
     *
     * @param $selectedFields
     *
     * @return array
     */
    protected function normalizeFieldsForSave($selectedFields)
    {
        $fields = [];
        if ($this->fields === null) {
            return [];
        }

        foreach ($this->fields as $field => $value) {
            if (!$this->fieldShouldNotBeSaved($field, $value, $selectedFields)) {
                $fields[$field] = $value;
            }
        }

        return $fields;
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
        ];

        return (!empty($selectedFields) && !in_array($field, $selectedFields))
            || in_array($field, $blacklistedFields)
            || !(substr($field, 0, 3) === 'UF_');
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
        $tableName = static::tableName();
        if (static::$bxObject[$tableName]) {
            return static::$bxObject[$tableName];
        }

        $item = HighloadBlockTable::getList(['filter' => ['TABLE_NAME' => $tableName ]])->fetch();
        return static::$bxObject[$tableName] = HighloadBlockTable::compileEntity($item)->getDataClass();
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
     * Instantiate a query object for the model.
     *
     * @throws Exception
     *
     * @return BaseQuery
     */
    public static function query()
    {
        return new HLQuery(static::instantiateObject(), get_called_class());
    }

    /**
     * Set current model id.
     *
     * @param $id
     */
    protected function setId($id)
    {
        $this->id = $id;
        $this->fields['ID'] = $id;
    }

    /**
     * Handle dynamic static method calls into a new query.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return static::query()->$method(...$parameters);
    }
}