<?php

namespace Arrilot\BitrixModels\Queries;

use Base;

abstract class BaseQuery
{
    /**
     * Bitrix object to be queried.
     *
     * @var object
     */
    protected $object;

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
     * @var array|bool
     */
    protected $select = false;

    /**
     * The key to list items in array of results.
     * Set to false to have auto incrementing integer.
     *
     * @var string|bool
     */
    protected $keyBy = 'ID';

    /**
     * Do not fetch props.
     *
     * @var bool
     */
    protected $withoutProps = false;

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
     * Get item by its id.
     *
     * @param int $id
     *
     * @return Base
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

        return ($items && isset($items[0])) ? $items[0] : false;
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
        $this->select = $value;

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
     * Setter for withoutProps.
     *
     * @param $value
     *
     * @return $this
     */
    public function withoutProps($value = true)
    {
        $this->withoutProps = $value;

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
}
