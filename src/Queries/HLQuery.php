<?php

namespace Arrilot\BitrixModels\Queries;


use Arrilot\BitrixModels\Models\HLModel;
use Illuminate\Support\Collection;
use LogicException;

class HLQuery extends BaseQuery
{
    public $keyBy = 'ID';

    /**
     * Query sort.
     *
     * @var array
     */
    public $select = false;
    public $sort = ['ID' => 'ASC'];
    public $limit;
    public $page;
    public $ttl = 0;

    /**
     * Query group by.
     *
     * @var array
     */
    public $groupBy = false;

    /**
     * Iblock id.
     *
     * @var int
     */
    protected $tableName;

    /**
     * Constructor.
     *
     * @param object $bxObject
     * @param string $modelName
     */
    public function __construct($bxObject, $modelName)
    {
        parent::__construct($bxObject, $modelName);

        $this->tableName = $modelName::tableName();
    }

    /**
     * Setter for groupBy.
     *
     * @param $value
     *
     * @return $this
     */
    public function groupBy($value)
    {
        $this->groupBy = $value;

        return $this;
    }

    public function limit($value)
    {
        $this->limit = $value;

        return $this;
    }

    public function page($num)
    {
        $this->page = $num;

        return $this;
    }

    public function cache($ttl)
    {
        $this->ttl = (int) $ttl;

        return $this;
    }

    /**
     * Get list of items.
     *
     * @return Collection
     */
    public function getList()
    {
        if ($this->queryShouldBeStopped) {
            return new Collection();
        }

        $items = [];


        $params = [
            'filter' => $this->normalizeFilter(),
            'order' => $this->sort,
            'select' => $this->normalizeSelect(),
            //'cache' => ['ttl' => 3600],
        ];

        if ($this->groupBy) $params['group'] = $this->groupBy;
        if ($this->limit) $params['limit'] = $this->limit;
        if ($this->ttl) $params['cache'] = ['ttl' => $this->ttl];
        if ($this->page && $this->limit) $params['offset'] = $this->page * $this->limit;

        $rsItems = $this->bxObject::getList($params);
        while ($arItem = $rsItems->fetch()) {
            $this->addItemToResultsUsingKeyBy($items, new $this->modelName($arItem['ID'], $arItem));
        }

        return new Collection($items);
    }

    protected function addItemToResultsUsingKeyBy(&$results, HLModel $object)
    {
        $item = $object->fields;

        if (!isset($item[$this->keyBy])) {
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
     * Get count of elements that match $filter.
     *
     * @return int
     */
    public function count()
    {
        if ($this->queryShouldBeStopped) {
            return 0;
        }

        return (int) $this->bxObject->getCount($this->normalizeFilter());
    }

    /**
     * Normalize filter before sending it to getList.
     * This prevents some inconsistency.
     *
     * @return array
     */
    protected function normalizeFilter()
    {
        return $this->filter;
    }

    /**
     * Normalize select before sending it to getList.
     * This prevents some inconsistency.
     *
     * @return array
     */
    protected function normalizeSelect()
    {
        return ( !$this->select || empty($this->select) ) ? ['*'] : $this->select;
    }
}