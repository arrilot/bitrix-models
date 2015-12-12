<?php

namespace Arrilot\BitrixModels\Queries;

use BadMethodCallException;
use Illuminate\Support\Collection;

/**
 * @method ElementQuery active()
 */
abstract class BaseQuery
{
    /**
     * Bitrix object to be queried.
     *
     * @var object
     */
    protected $bxObject;

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
     * Indicates that the query should be stopped instead of touching the DB.
     * Can be set in query scopes or manually.
     *
     * @var bool
     */
    protected $queryShouldBeStopped = false;

    /**
     * Get count of users that match $filter.
     *
     * @return int
     */
    abstract public function count();

    /**
     * Get list of items.
     *
     * @return Collection
     */
    abstract public function getList();

    /**
     * Constructor.
     *
     * @param object $bxObject
     * @param string $modelName
     */
    public function __construct($bxObject, $modelName)
    {
        $this->bxObject = $bxObject;
        $this->modelName = $modelName;
        $this->model = new $modelName();
    }

    /**
     * Paginate the given query into a paginator.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);
        $total = $this->getCountForPagination($columns);
        $results = $this->forPage($page, $perPage)->get($columns);
        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get the first item that matches query params.
     *
     * @return mixed
     */
    public function first()
    {
        return $this->limit(1)->getList()->first(null, false);
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
        if (!$id || $this->queryShouldBeStopped) {
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
     * @param mixed  $by
     * @param string $order
     *
     * @return $this
     */
    public function sort($by, $order = 'ASC')
    {
        $this->sort = is_array($by) ? $by : [$by => $order];

        return $this;
    }

    /**
     * Setter for filter.
     *
     * @param array $filter
     *
     * @return $this
     */
    public function filter($filter)
    {
        $this->filter = array_merge($this->filter, $filter);

        return $this;
    }

    /**
     * Reset filter.
     *
     * @return $this
     */
    public function resetFilter()
    {
        $this->filter = [];

        return $this;
    }

    /**
     * Add another filter to filters array.
     *
     * @param $filters
     *
     * @return $this
     */
    public function addFilter($filters)
    {
        foreach ($filters as $field => $value) {
            $this->filter[$field] = $value;
        }

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
     * @param int $value
     *
     * @return $this
     */
    public function limit($value)
    {
        $this->navigation['nPageSize'] = $value;

        return $this;
    }

    /**
     * Set the "page number" value of the query.
     *
     * @param int $num
     *
     * @return $this
     */
    public function page($num)
    {
        $this->navigation['iNumPage'] = $num;

        return $this;
    }

    /**
     * Alias for "limit".
     *
     * @param int $value
     *
     * @return $this
     */
    public function take($value)
    {
        return $this->limit($value);
    }

    /**
     * Stop the query from touching DB.
     *
     * @return $this
     */
    public function stopQuery()
    {
        $this->queryShouldBeStopped = true;

        return $this;
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

            $query = call_user_func_array([$this->model, 'scope'.$method], $parameters);

            if ($query === false) {
                $this->stopQuery();
            }

            return $query instanceof static ? $query : $this;
        }

        $className = get_class($this);

        throw new BadMethodCallException("Call to undefined method {$className}::{$method}()");
    }
}
