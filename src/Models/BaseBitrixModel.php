<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\ModelEventsTrait;
use Arrilot\BitrixModels\Queries\BaseQuery;
use LogicException;

abstract class BaseBitrixModel extends ArrayableModel
{
    use ModelEventsTrait;

    /**
     * Array of model fields keys that needs to be saved with next save().
     *
     * @var array
     */
    protected $fieldsSelectedForSave = [];

    /**
     * Array of errors that are passed to model events.
     *
     * @var array
     */
    protected $eventErrors = [];

    /**
     * Have fields been already fetched from DB?
     *
     * @var bool
     */
    protected $fieldsAreFetched = false;
    
    /**
     * Internal part of create to avoid problems with static and inheritance
     *
     * @param $fields
     *
     * @throws LogicException
     *
     * @return static|bool
     */
    abstract protected static function internalCreate($fields);
    
    /**
     * Save model to database.
     *
     * @param array $selectedFields save only these fields instead of all.
     *
     * @return bool
     */
    abstract public function save($selectedFields = []);

    /**
     * Determine whether the field should be stopped from passing to "update".
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $selectedFields
     *
     * @return bool
     */
    abstract protected function fieldShouldNotBeSaved($field, $value, $selectedFields);
    
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
     * Create new item in database.
     *
     * @param $fields
     *
     * @throws LogicException
     *
     * @return static|bool
     */
    public static function create($fields)
    {
        return static::internalCreate($fields);
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
     * Instantiate a query object for the model.
     *
     * @throws LogicException
     *
     * @return BaseQuery
     */
    public static function query()
    {
        throw new LogicException('public static function query() is not implemented');
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
