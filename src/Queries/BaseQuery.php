<?php

namespace Arrilot\BitrixModels\Queries;

use BadMethodCallException;

abstract class BaseQuery
{
    /**
     * Bitrix object to be queried.
     *
     * @var object
     */
    protected $object;

    /**
     * Name of the model that calls the query.
     *
     * @var string
     */
    protected $modelName;

    /**
     * Model that calls the query.
     *
     * @var object
     */
    protected $model;

    /**
     * Query sort.
     *
     * @var array
     */
    public $sort = [];

    /**
     * Query filter.
     *
     * @var array
     */
    public $filter = [];

    /**
     * Query navigation.
     *
     * @var array|bool
     */
    public $navigation = false;

    /**
     * Query select.
     *
     * @var array
     */
    public $select = ['FIELDS', 'PROPS'];

    /**
     * The key to list items in array of results.
     * Set to false to have auto incrementing integer.
     *
     * @var string|bool
     */
    public $keyBy = false;

    /**
     * Get count of users that match $filter.
     *
     * @return int
     */
    abstract public function count();

    /**
     * Get list of items.
     *
     * @return array
     */
    abstract public function getList();

    /**
     * Constructor.
     *
     * @param object $object
     * @param string $modelName
     */
    public function __construct($object, $modelName)
    {
        $this->object = $object;
        $this->modelName = $modelName;
        $this->model = new $modelName();
    }

    /**
     * Get item by its id.
     *
     * @param int $id
     *
     * @return mixed
     */
    public function getById($id)
    {
        if (!$id) {
            return false;
        }

        $this->sort = [];
        $this->keyBy = false;
        $this->filter['ID'] = $id;

        $items = $this->getList();

        return !empty($items) ? $items[0] : false;
    }

    /**
     * Setter for sort.
     *
     * @param $value
     *
     * @return $this
     */
    public function sort($value)
    {
        $this->sort = $value;

        return $this;
    }

    /**
     * Setter for filter.
     *
     * @param array $filter
     *
     * @return $this
     */
    public function filter(array $filter = [])
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Setter for navigation.
     *
     * @param $value
     *
     * @return $this
     */
    public function navigation($value)
    {
        $this->navigation = $value;

        return $this;
    }

    /**
     * Setter for select.
     *
     * @param $value
     *
     * @return $this
     */
    public function select($value)
    {
        $this->select = is_array($value) ? $value : func_get_args();

        return $this;
    }

    /**
     * Setter for keyBy.
     *
     * @param $value
     *
     * @return $this
     */
    public function keyBy($value)
    {
        $this->keyBy = $value;

        return $this;
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function limit($value)
    {
        $this->navigation['nPageSize'] = $value;

        return $this;
    }

    /**
     * Alias for "limit".
     *
     * @param  int  $value
     * @return $this
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Adds $item to $results using keyBy value.
     *
     * @param $results
     * @param $item
     *
     * @return array
     */
    protected function addItemToResultsUsingKeyBy(&$results, $item)
    {
        $keyByValue = ($this->keyBy && isset($item[$this->keyBy])) ? $item[$this->keyBy] : false;

        if ($keyByValue) {
            $results[$keyByValue] = $item;
        } else {
            $results[] = $item;
        }
    }

    /**
     * Determine if all fields must be selected.
     *
     * @return bool
     */
    protected function fieldsMustBeSelected()
    {
        return in_array('FIELDS', $this->select);
    }

    /**
     * Determine if all fields must be selected.
     *
     * @return bool
     */
    protected function propsMustBeSelected()
    {
        return in_array('PROPS', $this->select)
            || in_array('PROPERTIES', $this->select)
            || in_array('PROPERTY_VALUES', $this->select);
    }

    /**
     * Set $array[$new] as $array[$old] and delete $array[$old].
     *
     * @param array $array
     * @param $old
     * @param $new
     *
     * return null
     */
    protected function substituteField(&$array, $old, $new)
    {
        if (isset($array[$old]) && !isset($array[$new])) {
            $array[$new] = $array[$old];
        }

        unset($array[$old]);
    }

    /**
     * Clear select array from duplication and additional fields.
     *
     * @return array
     */
    protected function clearSelectArray()
    {
        $strip = ['FIELDS', 'PROPS', 'PROPERTIES', 'PROPERTY_VALUES', 'GROUPS', 'GROUP_ID', 'GROUPS_ID'];

        return array_values(array_diff(array_unique($this->select), $strip));
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @throws BadMethodCallException
     *
     * @return $this
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this->model, 'scope'.$method)) {
            array_unshift($parameters, $this);

            return call_user_func_array([$this->model, 'scope'.$method], $parameters);
        }

        $className = get_class($this);

        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}
