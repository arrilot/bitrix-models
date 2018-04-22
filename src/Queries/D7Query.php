<?php

namespace Arrilot\BitrixModels\Queries;

use Arrilot\BitrixModels\Adapters\D7Adapter;
use Illuminate\Support\Collection;

class D7Query extends BaseQuery
{
    /**
     * Query select.
     *
     * @var array
     */
    public $select = ['*'];

    /**
     * Query group by.
     *
     * @var array
     */
    public $group = [];

    /**
     * Query runtime.
     *
     * @var array
     */
    public $runtime = [];

    /**
     * Query limit.
     *
     * @var int|null
     */
    public $limit = null;

    /**
     * Query offset.
     *
     * @var int|null
     */
    public $offset = null;

    /**
     * Cache joins?
     *
     * @var bool
     */
    public $cacheJoins = false;

    /**
     * Data doubling?
     *
     * @var bool
     */
    public $dataDoubling = true;

    /**
     * Adapter to interact with Bitrix D7 API.
     *
     * @var D7Adapter
     */
    protected $bxObject;

    /**
     * Get count of users that match $filter.
     *
     * @return int
     */
    public function count()
    {
        $className = $this->bxObject->getClassName();
        $queryType = 'D7Query::count';
        $filter = $this->filter;

        $callback = function () use ($filter) {
            return (int) $this->bxObject->getCount($filter);
        };

        return $this->handleCacheIfNeeded(compact('className', 'filter', 'queryType'), $callback);
    }
    
    /**
     * Get list of items.
     *
     * @return Collection
     */
    protected function loadModels()
    {
        $params = [
            'select' => $this->select,
            'filter' => $this->filter,
            'group' => $this->group,
            'order' => $this->sort,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'runtime' => $this->runtime,
        ];

        if ($this->cacheTtl && $this->cacheJoins) {
            $params['cache'] = ['ttl' => $this->cacheTtl, 'cache_joins' => true];
        }

        $className = $this->bxObject->getClassName();
        $queryType = 'D7Query::getList';
        $keyBy = $this->keyBy;

        $callback = function () use ($className, $params) {
            $rows = [];
            $result = $this->bxObject->getList($params);
            while ($row = $result->fetch()) {
                $this->addItemToResultsUsingKeyBy($rows, new $this->modelName($row['ID'], $row));
            }

            return new Collection($rows);
        };

        return $this->handleCacheIfNeeded(compact('className', 'params', 'queryType', 'keyBy'), $callback);
    }

    /**
     * Setter for limit.
     *
     * @param  int|null  $value
     * @return $this
     */
    public function limit($value)
    {
        $this->limit = $value;
        
        return $this;
    }

    /**
     * Setter for offset.
     *
     * @param  int|null  $value
     * @return $this
     */
    public function offset($value)
    {
        $this->offset = $value;

        return $this;
    }
    
    /**
     * Setter for offset.
     *
     * @param  array|\Bitrix\Main\Entity\ExpressionField $fields
     * @return $this
     */
    public function runtime($fields)
    {
        $this->runtime = is_array($fields) ? $fields : [$fields];

        return $this;
    }

    /**
     * Setter for cacheJoins.
     *
     * @param  bool $value
     * @return $this
     */
    public function cacheJoins($value = true)
    {
        $this->cacheJoins = $value;

        return $this;
    }

    public function enableDataDoubling()
    {
        $this->dataDoubling = true;

        return $this;
    }

    public function disableDataDoubling()
    {
        $this->dataDoubling = false;

        return $this;
    }
    
    /**
     * For testing.
     *
     * @param $bxObject
     * @return $this
     */
    public function setAdapter($bxObject)
    {
        $this->bxObject = $bxObject;

        return $this;
    }
}
