<?php

namespace Arrilot\BitrixModels\Queries;

use Arrilot\BitrixModels\Models\ElementModel;
use CIBlock;
use Exception;
use Illuminate\Support\Collection;

/**
 * @method UserQuery active()
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
     * Method that is used to fetch getList results.
     * Available values: 'getNext' or 'getNextElement'.
     *
     * @var string
     */
    protected $fetchUsing = 'getNextElement';

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
        $this->fetchUsing = $modelName::$fetchUsing;
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

        if (class_exists('CIblock')) {
            return static::$cIblockObject = new CIblock();
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
     * Setter for filter.
     *
     * @param array $filter
     *
     * @return $this
     */
    public function filter(array $filter = [])
    {
        $this->filter = $filter;
        $this->filter['IBLOCK_ID'] = $this->iblockId;

        return $this;
    }

    /**
     * Setter for fetchUsing.
     *
     * @param string $fetchUsing
     *
     * @return $this
     */
    public function fetchUsing($fetchUsing)
    {
        $this->fetchUsing = $fetchUsing;

        return $this;
    }

    /**
     * Get list of items.
     *
     * @return Collection
     */
    public function getList()
    {
        $items = [];

        $rsItems = $this->bxObject->getList(
            $this->sort,
            $this->normalizeFilter(),
            $this->groupBy,
            $this->navigation,
            $this->normalizeSelect()
        );

        if ($this->shouldBeFetchedUsingGetNext()) {
            while ($arItem = $rsItems->getNext()) {
                $this->setPropertyValues($arItem);
                $this->addItemToResultsUsingKeyBy($items, new $this->modelName($arItem['ID'], $arItem));
            }
        } else {
            while ($obItem = $rsItems->getNextElement()) {
                $arItem = $obItem->getFields();
                if ($this->propsMustBeSelected()) {
                    $arItem['PROPERTIES'] = $obItem->getProperties();
                    $this->setPropertyValues($arItem);
                }

                $this->addItemToResultsUsingKeyBy($items, new $this->modelName($arItem['ID'], $arItem));
            }
        }

        return new Collection($items);
    }

    /**
     * Get count of elements that match $filter.
     *
     * @return int
     */
    public function count()
    {
        return $this->bxObject->getList(false, $this->normalizeFilter(), []);
    }

    /**
     * Set $field['PROPERTY_VALUES'] from $field['PROPERTIES'].
     *
     * @param array $fields
     *
     * @return null
     */
    protected function setPropertyValues(&$fields)
    {
        if (!empty($fields['PROPERTIES'])) {
            foreach ($fields['PROPERTIES'] as $code => $prop) {
                $fields['PROPERTY_VALUES'][$code] = $prop['VALUE'];
            }

            return;
        }

        $propertyValues = [];
        foreach ($fields as $code => $value) {
            if (preg_match('/^PROPERTY_(.*)_VALUE$/', $code, $matches) && !empty($matches[1])) {
                $propertyValues[$matches[1]] = $value;
                $garbagePropertyFields = [
                    'PROPERTY_'.$matches[1].'_VALUE',
                    '~PROPERTY_'.$matches[1].'_VALUE',
                    'PROPERTY_'.$matches[1].'_VALUE_ID',
                    '~PROPERTY_'.$matches[1].'_VALUE_ID',
                    'PROPERTY_'.$matches[1].'_PROPERTY_VALUE_ID',
                    '~PROPERTY_'.$matches[1].'_PROPERTY_VALUE_ID',
                    'PROPERTY_'.$matches[1].'_DESCRIPTION',
                    '~PROPERTY_'.$matches[1].'_DESCRIPTION',
                ];
                foreach ($garbagePropertyFields as $key) {
                    unset($fields[$key]);
                }
            }
        }

        if (!empty($propertyValues)) {
            $fields['PROPERTY_VALUES'] = $propertyValues;
        }
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

        if ($this->propsMustBeSelected() && $this->shouldBeFetchedUsingGetNext()) {
            $this->addAllPropsToSelect();
        }

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

        $rsProps = static::$cIblockObject->getProperties($this->iblockId);
        while ($prop = $rsProps->fetch()) {
            $this->select[] = 'PROPERTY_'.$prop['CODE'];
        }
    }

    /**
     * Determine if we should fetch using GetNext() method.
     *
     * @return bool
     */
    protected function shouldBeFetchedUsingGetNext()
    {
        return $this->fetchUsing === 'getNext' || $this->fetchUsing === 'GetNext';
    }
}
