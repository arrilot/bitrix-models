<?php

namespace Arrilot\BitrixModels\Models;

use ArrayAccess;
use ArrayIterator;
use Arrilot\BitrixModels\Queries\BaseQuery;
use Exception;
use IteratorAggregate;

abstract class BaseModel implements ArrayAccess, IteratorAggregate
{
    /**
     * ID of the model.
     *
     * @var null|int
     */
    public $id;

    /**
     * Array of model fields.
     *
     * @var null|array
     */
    public $fields;

    /**
     * Have fields been already fetched from DB?
     *
     * @var bool
     */
    protected $fieldsAreFetched = false;

    /**
     * List of params that can modify query.
     *
     * @var array
     */
    protected static $queryModifiers = [];

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
            $this->afterFill($fields);
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
     * @return static
     * @throws Exception
     */
    public static function create($fields)
    {
        $object = static::instantiateObject();
        $id = $object->add($fields);

        if (!$id) {
            throw new Exception($object->LAST_ERROR);
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

        foreach (static::$queryModifiers as $modifier) {
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
        return static::$object->delete($this->id);
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
            $this->fields[$key] = $value;
            $keys[] = $key;
        }

        return $this->save($keys);
    }

    /**
     * Set method for ArrayIterator.
     *
     * @param $offset
     * @param $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->fields[] = $value;
        } else {
            $this->fields[$offset] = $value;
        }
    }

    /**
     * Exists method for ArrayIterator
     *
     * @param $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->fields[$offset]);
    }

    /**
     * Unset method for ArrayIterator
     *
     * @param $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->fields[$offset]);
    }

    /**
     * Get method for ArrayIterator.
     *
     * @param $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->fields[$offset]) ? $this->fields[$offset] : null;
    }

    /**
     * Get an iterator for fields.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->fields);
    }

    /**
     * Cast model to array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->fields;
    }

    /**
     * Instantiate bitrix entity object.
     *
     * @return object
     * @throws Exception
     */
    public static function instantiateObject()
    {
        if (static::$object) {
            return static::$object;
        }

        if (class_exists(static::$objectClass)) {
            return static::$object = new static::$objectClass;
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
        static::$object = null;
    }

    /**
     * Instantiate a query object for the model.
     *
     * @return BaseQuery
     * @throws Exception
     */
    public static function query()
    {
        throw new Exception('public static function query() is not implemented');
    }

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
     * Save model to database.
     *
     * @param array $fields save only these fields instead of all
     *
     * @return bool
     */
    abstract public function save(array $fields = []);
}
