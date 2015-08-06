<?php

namespace Arrilot\BitrixModels\Queries;

use Arrilot\BitrixModels\Models\BaseModel;

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
     * Query sort.
     *
     * @var array
     */
    protected $sort = [];

    /**
     * Query filter.
     *
     * @var array
     */
    protected $filter = [];

    /**
     * Query navigation.
     *
     * @var array|bool
     */
    protected $navigation = false;

    /**
     * Query select.
     *
     * @var array
     */
    protected $select = ['FIELDS', 'PROPS'];

    /**
     * The key to list items in array of results.
     * Set to false to have auto incrementing integer.
     *
     * @var string|bool
     */
    protected $keyBy = 'ID';

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
    }

    /**
     * Get item by its id.
     *
     * @param int $id
     *
     * @return BaseModel|false
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
     * Adds $item to $results using keyBy value
     *
     * @param $results
     * @param $item
     *
     * @return array
     */
    protected function addUsingKeyBy(&$results, &$item)
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
        return !$this->select && in_array('FIELDS', $this->select);
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
     * Remove extra fields from $this->select before sending it to bitrix's getList.
     *
     * @return array
     */
    protected function prepareSelectForGetList()
    {
        $strip = ['FIELDS', 'PROPS', 'PROPERTIES', 'PROPERTY_VALUES', 'GROUPS', 'GROUP_ID'];

        return array_diff($this->select, $strip);
    }
}
