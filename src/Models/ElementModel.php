<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\ElementQuery;
use Exception;
use Illuminate\Support\Collection;

/**
 * ElementQuery methods
 * @method static ElementQuery groupBy($value)
 * @method static ElementModel getByCode(string $code)
 * @method static ElementModel getByExternalId(string $id)
 *
 * Base Query methods
 * @method static Collection getList()
 * @method static ElementModel first()
 * @method static ElementModel getById(int $id)
 * @method static ElementQuery sort(string|array $by, string $order='ASC')
 * @method static ElementQuery filter(array $filter)
 * @method static ElementQuery addFilter(array $filters)
 * @method static ElementQuery resetFilter()
 * @method static ElementQuery navigation(array $filter)
 * @method static ElementQuery select($value)
 * @method static ElementQuery keyBy(string $value)
 * @method static ElementQuery limit(int $value)
 * @method static ElementQuery page(int $num)
 * @method static ElementQuery take(int $value)
 * @method static ElementQuery forPage(int $page, int $perPage=15)
 * @method static \Illuminate\Pagination\LengthAwarePaginator paginate(int $perPage = 15, string $pageName = 'page')
 * @method static \Illuminate\Pagination\Paginator simplePaginate(int $perPage = 15, string $pageName = 'page')
 * @method static ElementQuery stopQuery()
 *
 * Scopes
 * @method static ElementQuery active()
 * @method static ElementQuery sortByDate(string $sort = 'DESC')
 * @method static ElementQuery fromSectionWithId(int $id)
 * @method static ElementQuery fromSectionWithCode(string $code)
 */
class ElementModel extends BitrixModel
{
    /**
     * Corresponding IBLOCK_ID
     *
     * @var int
     */
    const IBLOCK_ID = null;

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
     * Have sections been already fetched from DB?
     *
     * @var bool
     */
    protected $sectionsAreFetched = false;

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
            throw new Exception('You must set IBLOCK_ID constant inside a model or override iblockId() method');
        }
        
        return $id;
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
     * Scope to sort by date.
     *
     * @param ElementQuery $query
     * @param string       $sort
     *
     * @return ElementQuery
     */
    public function scopeSortByDate($query, $sort = 'DESC')
    {
        return $query->sort(['ACTIVE_FROM' => $sort]);
    }

    /**
     * Scope to get only items from a given section.
     *
     * @param ElementQuery $query
     * @param mixed        $id
     *
     * @return ElementQuery
     */
    public function scopeFromSectionWithId($query, $id)
    {
        $query->filter['SECTION_ID'] = $id;

        return $query;
    }

    /**
     * Scope to get only items from a given section.
     *
     * @param ElementQuery $query
     * @param string       $code
     *
     * @return ElementQuery
     */
    public function scopeFromSectionWithCode($query, $code)
    {
        $query->filter['SECTION_CODE'] = $code;

        return $query;
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
     * Load all model attributes from cache or database.
     *
     * @return $this
     */
    public function load()
    {
        $this->getFields();

        return $this;
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
     * @deprecated in favour of `->section()`
     * Get element direct section as ID or array of fields.
     *
     * @param bool $load
     *
     * @return false|int|array
     */
    public function getSection($load = false)
    {
        $fields = $this->getFields();
        if (!$load) {
            return $fields['IBLOCK_SECTION_ID'];
        }

        /** @var SectionModel $sectionModel */
        $sectionModel = static::sectionModel();
        if (!$fields['IBLOCK_SECTION_ID']) {
            return false;
        }

        return $sectionModel::query()->getById($fields['IBLOCK_SECTION_ID'])->toArray();
    }

    /**
     * Get element direct section as model object.
     *
     * @param bool $load
     *
     * @return false|SectionModel
     */
    public function section($load = false)
    {
        $fields = $this->getFields();

        /** @var SectionModel $sectionModel */
        $sectionModel = static::sectionModel();

        return $load
            ? $sectionModel::query()->getById($fields['IBLOCK_SECTION_ID'])
            : new $sectionModel($fields['IBLOCK_SECTION_ID']);
    }

    /**
     * Save props to database.
     * If selected is not empty then only props from it are saved.
     *
     * @param array $selected
     *
     * @return bool
     */
    public function saveProps($selected = [])
    {
        $propertyValues = $this->constructPropertyValuesForSave($selected);
        if (empty($propertyValues)) {
            return false;
        }

        $bxMethod = empty($selected) ? 'setPropertyValues' : 'setPropertyValuesEx';
        static::$bxObject->$bxMethod(
            $this->id,
            static::iblockId(),
            $propertyValues
        );

        return true;
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
