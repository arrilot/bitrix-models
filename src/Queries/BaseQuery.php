<?php

namespace Arrilot\BitrixModels\Queries;

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
    protected $listBy = 'ID';

    /**
     * Are props needed in results?
     *
     * @var bool
     */
    protected $withProps = false;

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
     * Setter for listBy.
     *
     * @param $value
     *
     * @return $this
     */
    public function listBy($value)
    {
        $this->listBy = $value;

        return $this;
    }

    /**
     * Setter for withProps.
     *
     * @param $value
     *
     * @return $this
     */
    public function withProps($value = true)
    {
        $this->withProps = $value;

        return $this;
    }

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
}
