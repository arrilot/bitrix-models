<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\ElementQuery;
use Exception;

class Element extends Base
{
    /**
     * Bitrix entity object.
     *
     * @var object
     */
    public static $object;

    /**
     * Corresponding object class name.
     *
     * @var string
     */
    protected static $objectClass = 'CIBlockElement';

    /**
     * List of params that can modify query.
     *
     * @var array
     */
    protected static $queryModifiers = [
        'sort',
        'filter',
        'groupBy',
        'navigation',
        'select',
        'withProps',
        'listBy',
    ];

    /**
     * Have props been already fetched from DB?
     *
     * @var bool
     */
    protected $propsAreFetched = false;

    /**
     * Corresponding iblock id.
     * MUST be overriden.
     *
     * @return int
     * @throws Exception
     */
    public static function iblockId()
    {
        throw new Exception('public static function iblockId() MUST be overriden');
    }

    /**
     * Instantiate a query object for the model.
     *
     * @return ElementQuery
     */
    public static function query()
    {
        return new ElementQuery(static::instantiateObject(), static::iblockId());
    }

    /**
     * Fill extra fields when $this->field is called.
     *
     * @param $fields
     *
     * @return null
     */
    protected function afterFill($fields)
    {
        $this->setPropertyValuesFromProperties();
    }

    /**
     * Set $this->fields['PROPERTY_VALUES'] from $this->fields['PROPERTIES'].
     *
     * @return void
     */
    protected function setPropertyValuesFromProperties()
    {
        if (isset($this->fields['PROPERTY_VALUES'])) {
            $this->propsAreFetched = true;

            return;
        }

        if (empty($this->fields) || empty($this->fields['PROPERTIES'])) {
            return;
        }

        foreach ($this->fields['PROPERTIES'] as $code => $prop) {
            $this->fields['PROPERTY_VALUES'][$code] = $prop['VALUE'];
        }

        $this->propsAreFetched = true;
    }

    /**
     * Get all model attributes from cache or database.
     *
     * @return array
     */
    public function get()
    {
        $this->getFields();

        $this->getProps();

        return $this->fields;
    }

    /**
     * Get elements props from cache or database.
     *
     * @return array
     */
    public function getProps()
    {
        if ($this->propsAreFetched) {
            return $this->fields['PROPERTY_VALUES'];
        }

        return $this->refreshProps();
    }

    /**
     * Refresh model from database and place data to $this->fields.
     *
     * @return array
     */
    public function refresh()
    {
        return $this->refreshFields();
    }

    /**
     * Refresh element fields and save them to a class field.
     *
     * @return array
     */
    public function refreshFields()
    {
        if (!$this->id) {
            throw new NotSetModelIdException();
        }

        $obElement = static::$object->getByID($this->id)->getNextElement();
        $this->fields = $obElement->getFields();
        $this->fields['PROPERTIES'] = $obElement->getProperties();
        $this->setPropertyValuesFromProperties();

        $this->fieldsAreFetched = true;
        $this->propsAreFetched = true;

        return $this->fields;
    }

    /**
     * Refresh element fields and save them to a class field.
     *
     * @return array
     */
    public function refreshProps()
    {
        // Refresh fields as long as we can't actually refresh props
        // without refreshing the fields
        $this->refreshFields();

        return $this->fields['PROPERTY_VALUES'];
    }

    /**
     * Save model to database.
     *
     * @param array $selectedFields save only these fields instead of all.
     *
     * @return bool
     */
    public function save(array $selectedFields = [])
    {
        $fields = $this->collectFieldsForSave($selectedFields);

        return static::$object->update($this->id, $fields);
    }

    /**
     * Create an array of fields that will be saved to database.
     *
     * @param $selectedFields
     *
     * @return array
     */
    protected function collectFieldsForSave($selectedFields)
    {
        if (empty($this->fields)) {
            return [];
        }

        $blacklisted = [
            'ID',
            'IBLOCK_ID',
            'PROPERTIES'
        ];

        $fields = [];
        foreach ($this->fields as $field => $value) {
            // skip if is not in selected fields
            if ($selectedFields && !in_array($field, $selectedFields)) {
                continue;
            }

            // skip blacklisted fields
            if (in_array($field, $blacklisted)) {
                continue;
            }

            // skip trash fields
            if ($value === '' || substr($field, 0, 1) === '~') {
                continue;
            }

            $fields[$field] = $value;
        }

        return $fields;
    }
}
