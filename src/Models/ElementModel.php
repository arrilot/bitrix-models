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
        'keyBy',
        'fetchUsing',
    ];

    /**
     * Have props been already fetched from DB?
     *
     * @var bool
     */
    protected $propsAreFetched = false;

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
     * Get element's props from cache or database.
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

        $obElement = static::$object->getByID($this->id)->getNextElement();
        $this->fields = $obElement->getFields();
        $this->fields['PROPERTIES'] = $obElement->getProperties();
        $this->setPropertyValuesFromProperties();

        if (!empty($sectionsBackup)) {
            $this->fields['IBLOCK_SECTION'] = $sectionsBackup;
        }

        $this->fieldsAreFetched = true;
        $this->propsAreFetched = true;

        return $this->fields;
    }

    /**
     * Refresh element's fields and save them to a class field.
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
     * Refresh element's sections and save them to a class field.
     *
     * @return array
     */
    public function refreshSections()
    {
        if ($this->id === null) {
            return [];
        }

        $this->fields['IBLOCK_SECTION'] = static::$object->getElementGroups($this->id, true);
        $this->sectionsAreFetched = true;

        return $this->fields['IBLOCK_SECTION'];
    }

    /**
     * Get element direct section as ID or array of fields.
     *
     * @param bool $withProps
     *
     * @return false|int
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
}
