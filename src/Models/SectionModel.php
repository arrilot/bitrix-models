<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\SectionQuery;
use Exception;

class SectionModel extends BaseModel
{
    /**
     * Bitrix entity object.
     *
     * @var object
     */
    public static $bxObject;

    /**
     * Corresponding object class name.
     *
     * @var string
     */
    protected static $objectClass = 'CIBlockSection';

    /**
     * List of params that can modify query.
     *
     * @var array
     */
    protected static $additionalQueryModifiers = [
        'countElements',
    ];

    /**
     * Corresponding iblock id.
     * MUST be overridden.
     *
     * @throws Exception
     *
     * @return int
     */
    public static function iblockId()
    {
        throw new Exception('public static function iblockId() MUST be overridden');
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
     * Get all model attributes from cache or database.
     *
     * @return array
     */
    public function get()
    {
        $this->getFields();

        return $this->fields;
    }

    /**
     * Refresh model from database and place data to $this->fields.
     *
     * @return array
     */
    public function refresh()
    {
        $this->refreshFields();

        return $this->fields;
    }

    /**
     * Refresh user fields and save them to a class field.
     *
     * @return array
     */
    public function refreshFields()
    {
        if ($this->id === null) {
            return  $this->fields = [];
        }

        $this->fields = static::$bxObject->getByID($this->id)->fetch();

        $this->fieldsAreFetched = true;

        return $this->fields;
    }
}
