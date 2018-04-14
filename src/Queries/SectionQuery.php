<?php

namespace Arrilot\BitrixModels\Queries;

use Arrilot\BitrixModels\Helpers;
use Illuminate\Support\Collection;
use Arrilot\BitrixModels\Models\SectionModel;

/**
 * @method SectionQuery active()
 */
class SectionQuery extends OldCoreQuery
{
    /**
     * Query sort.
     *
     * @var array
     */
    public $sort = ['SORT' => 'ASC'];

    /**
     * Query bIncCnt.
     * This is sent to getList directly.
     *
     * @var array|false
     */
    public $countElements = false;

    /**
     * Iblock id.
     *
     * @var int
     */
    protected $iblockId;

    /**
     * List of standard entity fields.
     *
     * @var array
     */
    protected $standardFields = [
        'ID',
        'CODE',
        'EXTERNAL_ID',
        'IBLOCK_ID',
        'IBLOCK_SECTION_ID',
        'TIMESTAMP_X',
        'SORT',
        'NAME',
        'ACTIVE',
        'GLOBAL_ACTIVE',
        'PICTURE',
        'DESCRIPTION',
        'DESCRIPTION_TYPE',
        'LEFT_MARGIN',
        'RIGHT_MARGIN',
        'DEPTH_LEVEL',
        'SEARCHABLE_CONTENT',
        'SECTION_PAGE_URL',
        'MODIFIED_BY',
        'DATE_CREATE',
        'CREATED_BY',
        'DETAIL_PICTURE',
    ];

    /**
     * Constructor.
     *
     * @param object $bxObject
     * @param string $modelName
     */
    public function __construct($bxObject, $modelName)
    {
        parent::__construct($bxObject, $modelName);

        $this->iblockId = $modelName::iblockId();
    }

    /**
     * CIBlockSection::getList substitution.
     *
     * @return Collection
     */
    protected function loadModels()
    {
        $queryType = 'SectionQuery::getList';
        $sort = $this->sort;
        $filter = $this->normalizeFilter();
        $countElements = $this->countElements;
        $select = $this->normalizeSelect();
        $navigation = $this->navigation;
        $keyBy = $this->keyBy;

        $callback = function() use ($sort, $filter, $countElements, $select, $navigation) {
            $sections = [];
            $rsSections = $this->bxObject->getList($sort, $filter, $countElements, $select, $navigation);
            while ($arSection = $this->performFetchUsingSelectedMethod($rsSections)) {

                // Если передать nPageSize, то Битрикс почему-то перестает десериализовать множественные свойсвта...
                // Проверим это еще раз, и если есть проблемы то пофиксим.
                foreach ($arSection as $field => $value) {
                    if (
                    is_string($value)
                    && Helpers::startsWith($value, 'a:')
                    && (Helpers::startsWith($field, 'UF_') || Helpers::startsWith($field, '~UF_'))
                    ) {
                        $unserializedValue = @unserialize($value);
                        $arSection[$field] = $unserializedValue === false ? $value : $unserializedValue;
                    }
                }

                $this->addItemToResultsUsingKeyBy($sections, new $this->modelName($arSection['ID'], $arSection));
            }

            return new Collection($sections);
        };

        $cacheParams = compact('queryType', 'sort', 'filter', 'countElements', 'select', 'navigation', 'keyBy');

        return $this->handleCacheIfNeeded($cacheParams, $callback);
    }

    /**
     * Get the first section with a given code.
     *
     * @param string $code
     *
     * @return SectionModel
     */
    public function getByCode($code)
    {
        $this->filter['CODE'] = $code;

        return $this->first();
    }

    /**
     * Get the first section with a given external id.
     *
     * @param string $id
     *
     * @return SectionModel
     */
    public function getByExternalId($id)
    {
        $this->filter['EXTERNAL_ID'] = $id;

        return $this->first();
    }

    /**
     * Get count of sections that match filter.
     *
     * @return int
     */
    public function count()
    {
        if ($this->queryShouldBeStopped) {
            return 0;
        }

        $queryType = 'SectionQuery::count';
        $filter = $this->normalizeFilter();
        $callback = function() use ($filter) {
            return (int) $this->bxObject->getCount($filter);
        };

        return $this->handleCacheIfNeeded(compact('queryType', 'filter'), $callback);
    }

    /**
     * Setter for countElements.
     *
     * @param $value
     *
     * @return $this
     */
    public function countElements($value)
    {
        $this->countElements = $value;

        return $this;
    }

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

        if ($this->propsMustBeSelected()) {
            $this->select[] = 'IBLOCK_ID';
            $this->select[] = 'UF_*';
        }

        $this->select[] = 'ID';

        return $this->clearSelectArray();
    }
}
