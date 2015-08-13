<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\BaseQuery;
use Exception;

abstract class BaseModel extends ArrayableModel
{
    /**
     * Have fields been already fetched from DB?
     *
     * @var bool
     */
    protected $fieldsAreFetched = false;

    /**
     * List of additional params that can modify query.
     *
     * @var array
     */
    protected static $additionalQueryModifiers = [];

    /**
     * Refresh model from database and place data to $this->fields.
     *
     * @return array
     */
    abstract public function refresh();

    /**
     * Refresh model fields from database and place them to $this->fields.
     *
     * @return array
     */
    abstract public function refreshFields();

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
     * Get all model attributes from cache or database.
     *
     * @return array
     */
    public function get()
    {
        if (!$this->fieldsAreFetched) {
            $this->refresh();
        }

        return $this->fields;
    }

    /**
     * Get user groups from cache or database.
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
     * Fill model fields if they are already known.
     * Saves DB queries.
     *
     * @param array $fields
     *
     * @return null
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
     * Create new item in database.
     *
     * @param $fields
     *
     * @throws Exception
     *
     * @return static
     */
    public static function create($fields)
    {
        $bxObject = static::instantiateObject();
        $id = $bxObject->add($fields);

        if (!$id) {
            throw new Exception($bxObject->LAST_ERROR);
        }

        $fields['ID'] = $id;

        return new static($id, $fields);
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
     * @return static
     */
    public static function find($id)
    {
        return static::query()->getById($id);
    }

    /**
     * Get item by its id.
     *
     * @param int $id
     *
     * @return static
     */
    public static function getById($id)
    {
        return static::query()->getById($id);
    }

    /**
     * Get list of items.
     *
     * @param array $params
     *
     * @return static[]
     */
    public static function getList($params = [])
    {
        $query = static::query();
        $modifiers = array_merge(static::$additionalQueryModifiers, [
            'sort',
            'filter',
            'navigation',
            'select',
            'keyBy',
            'limit',
            'take',
        ]);

        foreach ($modifiers as $modifier) {
            if (isset($params[$modifier])) {
                $query = $query->{$modifier}($params[$modifier]);
            }
        }

        return $query->getList();
    }

    /**
     * Delete model.
     *
     * @return bool
     */
    public function delete()
    {
        return static::$bxObject->delete($this->id);
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

        if ($this instanceof ElementModel) {
            $this->saveProps($selectedFields);
        }

        $fields = $this->normalizeFieldsForSave($selectedFields);

        return !empty($fields) ? static::$bxObject->update($this->id, $fields) : true;
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
            return $fields;
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

    /**
     * Instantiate a query object for the model.
     *
     * @throws Exception
     *
     * @return BaseQuery
     */
    public static function query()
    {
        throw new Exception('public static function query() is not implemented');
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
}
