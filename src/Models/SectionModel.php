<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\SectionQuery;
use Exception;

class SectionModel extends BaseModel
{
    /**
     * Corresponding IBLOCK_ID
     *
     * @var int
     */
    const IBLOCK_ID = null;

    /**
     * Corresponding object class name.
     *
     * @var string
     */
    protected static $objectClass = 'CIBlockSection';

    /**
     * Getter for corresponding iblock id.
     *
     * @throws Exception
     *
     * @return int
     */
    public static function iblockId()
    {
        $id = static::IBLOCK_ID;
        if (!$id) {
            throw new Exception('You must set $iblockId property or override iblockId() method');
        }
        
        return $id;
    }

    /**
     * Instantiate a query object for the model.
     *
     * @return SectionQuery
     */
    public static function query()
    {
        return new SectionQuery(static::instantiateObject(), get_called_class());
    }

    /**
     * Get IDs of direct children of the section.
     * Additional filter can be specified.
     *
     * @param array $filter
     *
     * @return array
     */
    public function getDirectChildren(array $filter = [])
    {
        return static::query()
            ->filter($filter)
            ->filter(['SECTION_ID' => $this->id])
            ->select('ID')
            ->getList()
            ->transform(function ($section) {
                return (int) $section['ID'];
            })
            ->all();
    }

    /**
     * Get IDs of all children of the section (direct or not).
     * Additional filter can be specified.
     *
     * @param array $filter
     * @param array|string $sort
     *
     * @return array
     */
    public function getAllChildren(array $filter = [], $sort = ['LEFT_MARGIN' => 'ASC'])
    {
        if (!isset($this->fields['LEFT_MARGIN']) || !isset($this->fields['RIGHT_MARGIN'])) {
            $this->refresh();
        }

        return static::query()
            ->sort($sort)
            ->filter($filter)
            ->filter([
                '!ID' => $this->id,
                '>LEFT_MARGIN' => $this->fields['LEFT_MARGIN'],
                '<RIGHT_MARGIN' => $this->fields['RIGHT_MARGIN'],
            ])
            ->select('ID')
            ->getList()
            ->transform(function ($section) {
                return (int) $section['ID'];
            })
            ->all();
    }
}
