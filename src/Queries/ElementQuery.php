<?php

namespace Arrilot\BitrixModels\Queries;

use Arrilot\BitrixModels\Models\ElementModel;

/**
 * @method UserQuery active()
 */
class ElementQuery extends BaseQuery
{
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
     * Constructor.
     *
     * @param object $object
     * @param string $modelName
     * @param int $iblockId
     */
    public function __construct($object, $modelName, $iblockId)
    {
        parent::__construct($object, $modelName);

        $this->iblockId = $iblockId;
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
     * Get list of items.
     *
     * @return ElementModel[]
     */
    public function getList()
    {
        $items = [];
        $rsItems = $this->object->getList(
            $this->sort,
            $this->normalizeFilter(),
            $this->groupBy,
            $this->navigation,
            $this->normalizeSelect()
        );
        while($obItem = $rsItems->getNextElement()) {
            $arItem = $obItem->getFields();
            if ($this->propsMustBeSelected()) {
                $arItem['PROPERTIES'] = $obItem->getProperties();
                $this->setPropertyValues($arItem);
            }

            $this->addItemToResultsUsingKeyBy($items, new $this->modelName($arItem['ID'], $arItem));
        }

        return $items;
    }

    /**
     * Get count of elements that match $filter.
     *
     * @return int
     */
    public function count()
    {
        return $this->object->getList(false, $this->normalizeFilter(), []);
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
        if (empty($fields) || empty($fields['PROPERTIES'])) {
            return;
        }

        foreach ($fields['PROPERTIES'] as $code => $prop) {
            $fields['PROPERTY_VALUES'][$code] = $prop['VALUE'];
        }

        return;
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
            return [];
        }

        $strip = ['FIELDS', 'PROPS', 'PROPERTIES', 'PROPERTY_VALUES'];

        return array_diff($this->select, $strip);
    }
}
