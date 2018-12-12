<?php

namespace Arrilot\BitrixModels\Queries;

use Arrilot\BitrixModels\Models\BaseBitrixModel;
use BadMethodCallException;
use Bitrix\Main\Data\Cache;
use Closure;
use CPHPCache;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use LogicException;

abstract class BaseQuery
{
    use BaseRelationQuery;

    /**
     * Query select.
     *
     * @var array
     */
    public $select = [];
    /**
     * Bitrix object to be queried.
     *
     * @var object|string
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
     * The key to list items in array of results.
     * Set to false to have auto incrementing integer.
     *
     * @var string|bool
     */
    public $keyBy = 'ID';

    /**
     * Number of minutes to cache a query
     *
     * @var double|int
     */
    public $cacheTtl = 0;

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
     * Подготавливает запрос и вызывает loadModels()
     *
     * @return Collection
     */
    public function getList()
    {
        if (!is_null($this->primaryModel)) {
            // Запрос - подгрузка релейшена. Надо добавить filter
            $this->filterByModels([$this->primaryModel]);
        }

        if ($this->queryShouldBeStopped) {
            return new Collection();
        }

        $models = $this->loadModels();

        if (!empty($this->with)) {
            $this->findWith($this->with, $models);
        }

        return $models;
    }

    /**
     * Get list of items.
     *
     * @return Collection
     */
    abstract protected function loadModels();

    /**
     * Constructor.
     *
     * @param object|string $bxObject
     * @param string $modelName
     */
    public function __construct($bxObject, $modelName)
    {
        $this->bxObject = $bxObject;
        $this->modelName = $modelName;
        $this->model = new $modelName();
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
        $this->filter['ID'] = $id;

        return $this->getList()->first(null, false);
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
     * Another setter for sort.
     *
     * @param mixed  $by
     * @param string $order
     *
     * @return $this
     */
    public function order($by, $order = 'ASC')
    {
        return $this->sort($by, $order);
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
     * @param array $filters
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
     * Setter for cache ttl.
     *
     * @param float|int $minutes
     *
     * @return $this
     */
    public function cache($minutes)
    {
        $this->cacheTtl = $minutes;

        return $this;
    }

    /**
     * Setter for keyBy.
     *
     * @param string $value
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
     * Set the limit and offset for a given page.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return $this
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->take($perPage)->page($page);
    }

    /**
     * Paginate the given query into a paginator.
     *
     * @param  int  $perPage
     * @param  string  $pageName
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15, $pageName = 'page')
    {
        $page = Paginator::resolveCurrentPage($pageName);
        $total = $this->count();
        $results = $this->forPage($page, $perPage)->getList();

        return new LengthAwarePaginator($results, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Get a paginator only supporting simple next and previous links.
     *
     * This is more efficient on larger data-sets, etc.
     *
     * @param  int  $perPage
     * @param  string  $pageName
     *
     * @return \Illuminate\Pagination\Paginator
     */
    public function simplePaginate($perPage = 15, $pageName = 'page')
    {
        $page = Paginator::resolveCurrentPage($pageName);
        $results = $this->forPage($page, $perPage + 1)->getList();

        return new Paginator($results, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
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
     * @param BaseBitrixModel $object
     *
     * @return void
     */
    protected function addItemToResultsUsingKeyBy(&$results, BaseBitrixModel $object)
    {
        $item = $object->fields;
        if (!array_key_exists($this->keyBy, $item)) {
            throw new LogicException("Field {$this->keyBy} is not found in object");
        }

        $keyByValue = $item[$this->keyBy];

        if (!isset($results[$keyByValue])) {
            $results[$keyByValue] = $object;
        } else {
            $oldFields = $results[$keyByValue]->fields;
            foreach ($oldFields as $field => $oldValue) {
                // пропускаем служебные поля.
                if (in_array($field, ['_were_multiplied', 'PROPERTIES'])) {
                    continue;
                }

                $alreadyMultiplied = !empty($oldFields['_were_multiplied'][$field]);

                // мультиплицируем только несовпадающие значения полей
                $newValue = $item[$field];
                if ($oldValue !== $newValue) {
                    // если еще не мультиплицировали поле, то его надо превратить в массив.
                    if (!$alreadyMultiplied) {
                        $oldFields[$field] = [
                            $oldFields[$field]
                        ];
                        $oldFields['_were_multiplied'][$field] = true;
                    }

                    // добавляем новое значению поле если такого еще нет.
                    if (empty($oldFields[$field]) || (is_array($oldFields[$field]) && !in_array($newValue, $oldFields[$field]))) {
                        $oldFields[$field][] = $newValue;
                    }
                }
            }

            $results[$keyByValue]->fields = $oldFields;
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
     * Store closure's result in the cache for a given number of minutes.
     *
     * @param string $key
     * @param double $minutes
     * @param Closure $callback
     * @return mixed
     */
    protected function rememberInCache($key, $minutes, Closure $callback)
    {
        $minutes = (double) $minutes;
        if ($minutes <= 0) {
            return $callback();
        }

        $cache = Cache::createInstance();
        if ($cache->initCache($minutes * 60, $key, '/bitrix-models')) {
            $vars = $cache->getVars();
            return !empty($vars['isCollection']) ? new Collection($vars['cache']) : $vars['cache'];
        }

        $cache->startDataCache();
        $result = $callback();

        // Bitrix cache is bad for storing collections. Let's convert it to array.
        $isCollection = $result instanceof Collection;
        if ($isCollection) {
            $result = $result->all();
        }

        $cache->endDataCache(['cache' => $result, 'isCollection' => $isCollection]);

        return $isCollection ? new Collection($result) : $result;
    }

    protected function handleCacheIfNeeded($cacheKeyParams, Closure $callback)
    {
        return $this->cacheTtl
            ? $this->rememberInCache(md5(json_encode($cacheKeyParams)), $this->cacheTtl, $callback)
            : $callback();
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
    
    protected function prepareMultiFilter(&$key, &$value)
    {
    
    }
}
