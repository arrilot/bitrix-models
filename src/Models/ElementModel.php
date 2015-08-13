<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\ElementQuery;
use Exception;

class ElementModel extends BaseModel
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
    protected static $objectClass = 'CIBlockElement';

    /**
     * List of additional params that can modify query.
     *
     * @var array
     */
    protected static $additionalQueryModifiers = [
        'groupBy',
        'fetchUsing',
    ];

    /**
     * Have sections been already fetched from DB?
     *
     * @var bool
     */
    protected $sectionsAreFetched = false;

    /**
     * Method that is used to fetch getList results.
     * Available values: 'getNext' or 'getNextElement'.
     *
     * @var string
     */
    public static $fetchUsing = 'getNextElement';

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
     * Corresponding section model full qualified class name.
     * MUST be overridden if you are going to use section model for this iblock.
     *
     * @throws Exception
     *
     * @return string
     */
    public static function sectionModel()
    {
        throw new Exception('public static function sectionModel() MUST be overridden');
    }

    /**
     * Instantiate a query object for the model.
     *
     * @return ElementQuery
     */
    public static function query()
    {
        return new ElementQuery(static::instantiateObject(), get_called_class());
    }

    /**
     * Fill extra fields when $this->field is called.
     *
     * @return null
     */
    protected function afterFill()
    {
        $this->normalizePropertyFormat();
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
     * Get element's sections from cache or database.
     *
     * @return array
     */
    public function getSections()
    {
        if ($this->sectionsAreFetched) {
            return $this->fields['IBLOCK_SECTION'];
        }

        return $this->refreshSections();
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
     * Refresh element's fields and save them to a class field.
     *
     * @return array
     */
    public function refreshFields()
    {
        if ($this->id === null) {
            return  $this->fields = [];
        }

        $sectionsBackup = isset($this->fields['IBLOCK_SECTION']) ? $this->fields['IBLOCK_SECTION'] : null;

        $this->fields = static::query()->getById($this->id)->fields;

        if (!empty($sectionsBackup)) {
            $this->fields['IBLOCK_SECTION'] = $sectionsBackup;
        }

        $this->fieldsAreFetched = true;

        return $this->fields;
    }

    /**
     * Refresh element's sections and save them to a class field.
     *
     * @return array
     */
    public function refreshSections()
    {
        if ($this->id === null) {
            return [];
        }

        $this->fields['IBLOCK_SECTION'] = static::$bxObject->getElementGroups($this->id, true);
        $this->sectionsAreFetched = true;

        return $this->fields['IBLOCK_SECTION'];
    }

    /**
     * Get element direct section as ID or array of fields.
     *
     * @param bool $withProps
     *
     * @return false|int|array
     */
    public function getSection($withProps = false)
    {
        $fields = $this->getFields();
        if (!$withProps) {
            return $fields['IBLOCK_SECTION_ID'];
        }

        /** @var SectionModel $sectionModel */
        $sectionModel = static::sectionModel();
        if (!$fields['IBLOCK_SECTION_ID']) {
            return false;
        }

        return $sectionModel::getById($fields['IBLOCK_SECTION_ID'])->toArray();
    }

    /**
     * Get element direct section as model object.
     *
     * @param bool $withProps
     *
     * @return false|SectionModel
     */
    public function section($withProps = false)
    {
        $fields = $this->getFields();

        /** @var SectionModel $sectionModel */
        $sectionModel = static::sectionModel();

        return $withProps
            ? $sectionModel::getById($fields['IBLOCK_SECTION_ID'])
            : new $sectionModel($fields['IBLOCK_SECTION_ID']);
    }

    /**
     * Save model to database.
     *
     * @param array $selectedFields save only these fields instead of all.
     *
     * @return bool
     */
    public function save($selectedFields = [])
    {
        $selectedFields = is_array($selectedFields) ? $selectedFields : func_get_args();

        $this->saveProps($selectedFields);

        $fields = $this->normalizeFieldsForSave($selectedFields);

        return !empty($fields) ? static::$bxObject->update($this->id, $fields) : true;
    }

    /**
     * Save props to database.
     * If selected is not empty then only props from it are saved.
     *
     * @param array $selected
     *
     * @return null
     */
    public function saveProps($selected = [])
    {
        $propertyValues = $this->constructPropertyValuesForSave($selected);
        if (empty($propertyValues)) {
            return;
        }

        $bxMethod = empty($selected) ? 'setPropertyValues' : 'setPropertyValuesEx';
        static::$bxObject->$bxMethod(
            $this->id,
            static::iblockId(),
            $propertyValues
        );
    }

    /**
     * Normalize properties's format converting it to 'PROPERTY_"CODE"_VALUE'.
     *
     * @return null
     */
    protected function normalizePropertyFormat()
    {
        if (empty($this->fields['PROPERTIES'])) {
            return;
        }

        foreach ($this->fields['PROPERTIES'] as $code => $prop) {
            $this->fields['PROPERTY_'.$code.'_VALUE'] = $prop['VALUE'];
            $this->fields['~PROPERTY_'.$code.'_VALUE'] = $prop['~VALUE'];
            $this->fields['PROPERTY_'.$code.'_DESCRIPTION'] = $prop['DESCRIPTION'];
            $this->fields['~PROPERTY_'.$code.'_DESCRIPTION'] = $prop['~DESCRIPTION'];
            $this->fields['PROPERTY_'.$code.'_VALUE_ID'] = $prop['PROPERTY_VALUE_ID'];
        }
    }

    /**
     * Construct 'PROPERTY_VALUES' => [...] from flat fields array.
     * This is used in save.
     * If $selectedFields are specified only those are saved.
     *
     * @param $selectedFields
     *
     * @return array
     */
    protected function constructPropertyValuesForSave($selectedFields = [])
    {
        $propertyValues = [];
        $saveOnlySelected = !empty($selectedFields);
        foreach ($this->fields as $code => $value) {
            if ($saveOnlySelected && !in_array($code, $selectedFields)) {
                continue;
            }

            if (preg_match('/^PROPERTY_(.*)_VALUE$/', $code, $matches) && !empty($matches[1])) {
                $propertyValues[$matches[1]] = $value;
            }
        }

        return $propertyValues;
    }
}
