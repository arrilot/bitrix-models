<?php

namespace Arrilot\BitrixModels\Queries;

use Arrilot\BitrixCacher\Cache;
use CIBlock;
use Illuminate\Support\Collection;
use Arrilot\BitrixModels\Models\ElementModel;
use Exception;

/**
 * @method ElementQuery active()
 * @method ElementQuery sortByDate(string $sort = 'desc')
 * @method ElementQuery fromSectionWithId(int $id)
 * @method ElementQuery fromSectionWithCode(string $code)
 */
class ElementQuery extends OldCoreQuery
{
    /**
     * CIblock object or test double.
     *
     * @var object.
     */
    public static $cIblockObject;

    /**
     * Query sort.
     *
     * @var array
     */
    public $sort = ['SORT' => 'ASC'];

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
    protected $iblockId;

    /**
     * Iblock version.
     *
     * @var int
     */
    protected $iblockVersion;

    /**
     * List of standard entity fields.
     *
     * @var array
     */
    protected $standardFields = [
        'ID',
        'TIMESTAMP_X',
        'TIMESTAMP_X_UNIX',
        'MODIFIED_BY',
        'DATE_CREATE',
        'DATE_CREATE_UNIX',
        'CREATED_BY',
        'IBLOCK_ID',
        'IBLOCK_SECTION_ID',
        'ACTIVE',
        'ACTIVE_FROM',
        'ACTIVE_TO',
        'SORT',
        'NAME',
        'PREVIEW_PICTURE',
        'PREVIEW_TEXT',
        'PREVIEW_TEXT_TYPE',
        'DETAIL_PICTURE',
        'DETAIL_TEXT',
        'DETAIL_TEXT_TYPE',
        'SEARCHABLE_CONTENT',
        'IN_SECTIONS',
        'SHOW_COUNTER',
        'SHOW_COUNTER_START',
        'CODE',
        'TAGS',
        'XML_ID',
        'EXTERNAL_ID',
        'TMP_ID',
        'CREATED_USER_NAME',
        'DETAIL_PAGE_URL',
        'LIST_PAGE_URL',
        'CREATED_DATE',
    ];

    /**
     * Constructor.
     *
     * @param object $bxObject
     * @param string $modelName
     */
    public function __construct($bxObject, $modelName)
    {
        static::instantiateCIblockObject();
        parent::__construct($bxObject, $modelName);

        $this->iblockId = $modelName::iblockId();
        $this->iblockVersion = $modelName::IBLOCK_VERSION ?: 2;
    }

    /**
     * Instantiate bitrix entity object.
     *
     * @throws Exception
     *
     * @return object
     */
    public static function instantiateCIblockObject()
    {
        if (static::$cIblockObject) {
            return static::$cIblockObject;
        }

        if (class_exists('CIBlock')) {
            return static::$cIblockObject = new CIBlock();
        }

        throw new Exception('CIblock object initialization failed');
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

    /**
     * Get list of items.
     *
     * @return Collection
     */
    protected function loadModels()
    {
        $sort = $this->sort;
        $filter = $this->normalizeFilter();
        $groupBy = $this->groupBy;
        $navigation = $this->navigation;
        $select = $this->normalizeSelect();
        $queryType = 'ElementQuery::getList';
        $fetchUsing = $this->fetchUsing;
        $keyBy = $this->keyBy;
        list($select, $chunkQuery) = $this->multiplySelectForMaxJoinsRestrictionIfNeeded($select);

        $callback = function() use ($sort, $filter, $groupBy, $navigation, $select, $chunkQuery) {
            if ($chunkQuery) {
                $itemsChunks = [];
                foreach ($select as $chunkIndex => $selectForChunk) {
                    $rsItems = $this->bxObject->GetList($sort, $filter, $groupBy, $navigation, $selectForChunk);
                    while ($arItem = $this->performFetchUsingSelectedMethod($rsItems)) {
                        $this->addItemToResultsUsingKeyBy($itemsChunks[$chunkIndex], new $this->modelName($arItem['ID'], $arItem));
                    }
                }

                $items = $this->mergeChunks($itemsChunks);
            } else {
                $items = [];
                $rsItems = $this->bxObject->GetList($sort, $filter, $groupBy, $navigation, $select);
                while ($arItem = $this->performFetchUsingSelectedMethod($rsItems)) {
                    $this->addItemToResultsUsingKeyBy($items, new $this->modelName($arItem['ID'], $arItem));
                }
            }
            return new Collection($items);
        };

        $cacheKeyParams = compact('sort', 'filter', 'group', 'navigation', 'select', 'queryType', 'keyBy', 'fetchUsing');

        return $this->handleCacheIfNeeded($cacheKeyParams, $callback);
    }

    /**
     * Get the first element with a given code.
     *
     * @param string $code
     *
     * @return ElementModel
     */
    public function getByCode($code)
    {
        $this->filter['CODE'] = $code;

        return $this->first();
    }

    /**
     * Get the first element with a given external id.
     *
     * @param string $id
     *
     * @return ElementModel
     */
    public function getByExternalId($id)
    {
        $this->filter['EXTERNAL_ID'] = $id;

        return $this->first();
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

        $filter = $this->normalizeFilter();
        $queryType = "ElementQuery::count";

        $callback = function () use ($filter) {
            return (int) $this->bxObject->GetList(false, $filter, []);
        };

        return $this->handleCacheIfNeeded(compact('filter', 'queryType'), $callback);
    }

//    /**
//     * Normalize properties's format converting it to 'PROPERTY_"CODE"_VALUE'.
//     *
//     * @param array $fields
//     *
//     * @return null
//     */
//    protected function normalizePropertyResultFormat(&$fields)
//    {
//        if (empty($fields['PROPERTIES'])) {
//            return;
//        }
//
//        foreach ($fields['PROPERTIES'] as $code => $prop) {
//            $fields['PROPERTY_'.$code.'_VALUE'] = $prop['VALUE'];
//            $fields['~PROPERTY_'.$code.'_VALUE'] = $prop['~VALUE'];
//            $fields['PROPERTY_'.$code.'_DESCRIPTION'] = $prop['DESCRIPTION'];
//            $fields['~PROPERTY_'.$code.'_DESCRIPTION'] = $prop['~DESCRIPTION'];
//            $fields['PROPERTY_'.$code.'_VALUE_ID'] = $prop['PROPERTY_VALUE_ID'];
//            if (isset($prop['VALUE_ENUM_ID'])) {
//                $fields['PROPERTY_'.$code.'_ENUM_ID'] = $prop['VALUE_ENUM_ID'];
//            }
//        }
//    }

    /**
     * Normalize filter before sending it to getList.
     * This prevents some inconsistency.
     *
     * @return array
     */
    protected function normalizeFilter()
    {
        $this->filter['IBLOCK_ID'] = $this->iblockId;

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
        if ($this->fieldsMustBeSelected()) {
            $this->select = array_merge($this->standardFields, $this->select);
        }

        $this->select[] = 'ID';
        $this->select[] = 'IBLOCK_ID';

        return $this->clearSelectArray();
    }

    /**
     * Fetch all iblock property codes from database
     *
     * return array
     */
    protected function fetchAllPropsForSelect()
    {
        $props = [];
        $rsProps = static::$cIblockObject->GetProperties($this->iblockId);
        while ($prop = $rsProps->Fetch()) {
            $props[] = 'PROPERTY_'.$prop['CODE'];
        }

        return $props;
    }
    
    protected function multiplySelectForMaxJoinsRestrictionIfNeeded($select)
    {
        if (!$this->propsMustBeSelected()) {
            return [$select, false];
        }

        $chunkSize = 20;
        $props = $this->fetchAllPropsForSelect();
        if ($this->iblockVersion !== 1 || (count($props) <= $chunkSize)) {
            return [array_merge($select, $props), false];
        }

        // начинаем формировать селекты из свойств
        $multipleSelect = array_chunk($props, $chunkSize);

        // добавляем в каждый селект поля "несвойства"
        foreach ($multipleSelect as $i => $partOfProps) {
            $multipleSelect[$i] = array_merge($select, $partOfProps);
        }

        return [$multipleSelect, true];
    }
    
    protected function mergeChunks($chunks)
    {
        $items = [];
        foreach ($chunks as $chunk) {
            foreach ($chunk as $k => $item) {
                if (isset($items[$k])) {
                    $item->fields['_were_multiplied'] = array_merge((array) $items[$k]->fields['_were_multiplied'], (array) $item->fields['_were_multiplied']);
                    $items[$k]->fields = (array) $item->fields + (array) $items[$k]->fields;
                } else {
                    $items[$k] = $item;
                }
            }
        }

        return $items;
    }
}
