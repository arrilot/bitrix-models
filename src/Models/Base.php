<?php

namespace Arrilot\BitrixModels\Models;

use ArrayAccess;
use ArrayIterator;
use Arrilot\BitrixModels\Queries\BaseQuery;
use Exception;
use IteratorAggregate;

abstract class Base implements ArrayAccess, IteratorAggregate
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
    protected $hasBeenFetched = false;

    /**
     * Constructor.
     *
     * @param      $id
     * @param null $fields
     */
    public function __construct($id, $fields = null)
    {
        static::instantiateObject();

        $this->id = $id;

        if (!is_null($fields)) {
            $this->hasBeenFetched = true;
        }

        $this->fields = $fields;
    }

    /**
     * Get model fields from cache or database.
     *
     * @return array
     */
    public function get()
    {
        if (!$this->hasBeenFetched) {
            $this->fetch();
        }

        return $this->fields;
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
     * Get list of items.
     *
     * @param array $params
     *
     * @return array
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
     * Refresh model from database.
     *
     * @return void
     */
    public function refresh()
    {
        $this->fetch();
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
     * Fetch model fields from database and place them to $this->fields.
     *
     * @return void
     */
    abstract protected function fetch();

    /**
     * Save model to database.
     *
     * @param array $fields save only these fields instead of all
     *
     * @return bool
     */
    abstract public function save(array $fields = []);
}
