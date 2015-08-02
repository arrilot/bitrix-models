<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\BaseQuery;
use Exception;

abstract class Base
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
