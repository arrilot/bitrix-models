<?php

namespace Arrilot\BitrixModels\Queries;

class ElementQuery extends BaseQuery
{
    /**
     * Query sort.
     *
     * @var array
     */
    protected $sort = ['SORT' => 'ASC'];

    /**
     * Query select.
     *
     * @var array|bool
     */
    protected $select = [];

    /**
     * Query group by.
     *
     * @var array
     */
    protected $groupBy = false;

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
     * @param int $iblockId
     */
    public function __construct($object, $iblockId)
    {
        $this->object = $object;

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
        $this->filter = array_merge($filter, [
            'IBLOCK_ID' => $this->iblockId
        ]);

        return $this;
    }

    /**
     * CIblockElement::getList substitution.
     *
     * @return array
     */
    public function getList()
    {
        $items = [];
        $rsItems = $this->object->getList($this->sort, $this->filter, $this->groupBy, $this->navigation, $this->select);
        while($obItem = $rsItems->getNextElement()) {
            $item = $obItem->getFields();
            if ($this->withProps) {
                $item['PROPERTIES'] = $obItem->getProperties();
                $this->setPropertyValues($item);
            }

            $listByValue = ($this->listBy && isset($item[$this->listBy])) ? $item[$this->listBy] : false;

            if ($listByValue) {
                $items[$listByValue] = $item;
            } else {
                $items[] = $item;
            }
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
        return $this->object->getList(false, $this->filter, []);
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
}
