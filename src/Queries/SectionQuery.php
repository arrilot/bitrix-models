<?php

namespace Arrilot\BitrixModels\Queries;

use Illuminate\Support\Collection;

/**
 * @method SectionQuery active()
 */
class SectionQuery extends BaseQuery
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
    public function getList()
    {
        $sections = [];
        $rsSections = $this->bxObject->getList(
            $this->sort,
            $this->normalizeFilter(),
            $this->countElements,
            $this->normalizeSelect(),
            $this->navigation
        );
        while ($arSection = $rsSections->fetch()) {
            $this->addItemToResultsUsingKeyBy($sections, new $this->modelName($arSection['ID'], $arSection));
        }

        return new Collection($sections);
    }

    /**
     * Get count of sections that match filter.
     *
     * @return int
     */
    public function count()
    {
        return $this->bxObject->getCount($this->normalizeFilter());
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
            $this->select = $this->select + $this->standardFields;
        }

        if ($this->propsMustBeSelected()) {
            $this->select[] = 'IBLOCK_ID';
            $this->select[] = 'UF_*';
        }

        return $this->clearSelectArray();
    }
}
