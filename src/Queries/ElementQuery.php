<?php

namespace Arrilot\BitrixModels\Queries;

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
class ElementQuery extends BaseQuery
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
    public function getList()
    {
        if ($this->queryShouldBeStopped) {
            return new Collection();
        }

        $items = [];

        $rsItems = $this->bxObject->GetList(
            $this->sort,
            $this->normalizeFilter(),
            $this->groupBy,
            $this->navigation,
            $this->normalizeSelect()
        );
    
        while ($arItem = $rsItems->Fetch()) {
            $this->addItemToResultsUsingKeyBy($items, new $this->modelName($arItem['ID'], $arItem));
        }

        return new Collection($items);
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

        return (int) $this->bxObject->GetList(false, $this->normalizeFilter(), []);
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

        if ($this->propsMustBeSelected()) {
            $this->addAllPropsToSelect();
        }

        $this->select[] = 'ID';
        $this->select[] = 'IBLOCK_ID';

        return $this->clearSelectArray();
    }

    /**
     * Add all iblock property codes to select.
     *
     * return null
     */
    protected function addAllPropsToSelect()
    {
        $this->select[] = 'ID';
        $this->select[] = 'IBLOCK_ID';

        //dd (static::$cIblockObject);
        $rsProps = static::$cIblockObject->GetProperties($this->iblockId);
        while ($prop = $rsProps->Fetch()) {
            $this->select[] = 'PROPERTY_'.$prop['CODE'];
        }
    }
}
