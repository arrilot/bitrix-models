<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Exceptions\ExceptionFromBitrix;
use Arrilot\BitrixModels\Queries\ElementQuery;
use CIBlock;
use Illuminate\Support\Collection;
use LogicException;

/**
 * ElementQuery methods
 * @method static ElementQuery groupBy($value)
 * @method static static getByCode(string $code)
 * @method static static getByExternalId(string $id)
 *
 * Base Query methods
 * @method static Collection|static[] getList()
 * @method static static first()
 * @method static static getById(int $id)
 * @method static ElementQuery sort(string|array $by, string $order='ASC')
 * @method static ElementQuery order(string|array $by, string $order='ASC') // same as sort()
 * @method static ElementQuery filter(array $filter)
 * @method static ElementQuery addFilter(array $filters)
 * @method static ElementQuery resetFilter()
 * @method static ElementQuery navigation(array $filter)
 * @method static ElementQuery select($value)
 * @method static ElementQuery keyBy(string $value)
 * @method static ElementQuery limit(int $value)
 * @method static ElementQuery offset(int $value)
 * @method static ElementQuery page(int $num)
 * @method static ElementQuery take(int $value) // same as limit()
 * @method static ElementQuery forPage(int $page, int $perPage=15)
 * @method static \Illuminate\Pagination\LengthAwarePaginator paginate(int $perPage = 15, string $pageName = 'page')
 * @method static \Illuminate\Pagination\Paginator simplePaginate(int $perPage = 15, string $pageName = 'page')
 * @method static ElementQuery stopQuery()
 * @method static ElementQuery cache(float|int $minutes)
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
     * IBLOCK version (1 or 2)
     *
     * @var int
     */
    const IBLOCK_VERSION = 2;

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
     * Iblock PropertiesData from Bitrix DB
     *
     * @var null|array
     */
    protected static $iblockPropertiesData = [];

    /**
     * Have sections been already fetched from DB?
     *
     * @var bool
     */
    protected $sectionsAreFetched = false;

    /**
     * Log in Bitrix workflow ($bWorkFlow for CIBlockElement::Add/Update).
     *
     * @var bool
     */
    protected static $workFlow = false;

    /**
     * Update search after each create or update ($bUpdateSearch for CIBlockElement::Add/Update).
     *
     * @var bool
     */
    protected static $updateSearch = true;

    /**
     * Resize pictures during add/update ($bResizePictures for CIBlockElement::Add/Update).
     *
     * @var bool
     */
    protected static $resizePictures = false;

    /**
     * Getter for corresponding iblock id.
     *
     * @throws LogicException
     *
     * @return int
     */
    public static function iblockId()
    {
        $id = static::IBLOCK_ID;
        if (!$id) {
            throw new LogicException('You must set IBLOCK_ID constant inside a model or override iblockId() method');
        }
        
        return $id;
    }
    
    /**
     * Create new item in database.
     *
     * @param $fields
     *
     * @throws LogicException
     *
     * @return static|bool
     * @throws ExceptionFromBitrix
     */
    public static function create($fields)
    {
        if (!isset($fields['IBLOCK_ID'])) {
            $fields['IBLOCK_ID'] = static::iblockId();
        }

        return static::internalCreate($fields);
    }

    public static function internalDirectCreate($bxObject, $fields)
    {
        return $bxObject->add($fields, static::$workFlow, static::$updateSearch, static::$resizePictures);
    }

    /**
     * Fetches static::$iblockPropertiesData if it's not fetched and returns it.
     *
     * @return array
     */
    protected static function getCachedIblockPropertiesData()
    {
        $iblockId = static::iblockId();
        if (!empty(self::$iblockPropertiesData[$iblockId])) {
            return self::$iblockPropertiesData[$iblockId];
        }

        $props = [];
        $dbRes = CIBlock::GetProperties($iblockId, [], []);
        while($property = $dbRes->Fetch()) {
            $props[$property['CODE']] = $property;
        }

        return self::$iblockPropertiesData[$iblockId] = $props;
    }

    /**
     * Setter for self::$iblockPropertiesData[static::iblockId()] mainly for testing.
     *
     * @param $data
     * @return void
     */
    public static function setCachedIblockPropertiesData($data)
    {
        self::$iblockPropertiesData[static::iblockId()] = $data;
    }

    /**
     * Corresponding section model full qualified class name.
     * MUST be overridden if you are going to use section model for this iblock.
     *
     * @throws LogicException
     *
     * @return string
     */
    public static function sectionModel()
    {
        throw new LogicException('public static function sectionModel() MUST be overridden');
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
            $this->original = [];
            return $this->fields = [];
        }

        $sectionsBackup = isset($this->fields['IBLOCK_SECTION']) ? $this->fields['IBLOCK_SECTION'] : null;

        $this->fields = static::query()->getById($this->id)->fields;

        if (!empty($sectionsBackup)) {
            $this->fields['IBLOCK_SECTION'] = $sectionsBackup;
        }

        $this->fieldsAreFetched = true;

        $this->original = $this->fields;

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

        $this->fields['IBLOCK_SECTION'] = [];
        $dbSections = static::$bxObject->getElementGroups($this->id, true);
        while ($section = $dbSections->Fetch()) {
            $this->fields['IBLOCK_SECTION'][] = $section;
        }

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
     * Proxy for GetPanelButtons
     *
     * @param array $options
     * @return array
     */
    public function getPanelButtons($options = [])
    {
        return CIBlock::GetPanelButtons(
            static::iblockId(),
            $this->id,
            0,
            $options
        );
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

        $iblockPropertiesData = static::getCachedIblockPropertiesData();

        if ($saveOnlySelected) {
            foreach ($selectedFields as $code) {
                // if we pass PROPERTY_X_DESCRIPTION as selected field, we need to add PROPERTY_X_VALUE as well.
                if (preg_match('/^PROPERTY_(.*)_DESCRIPTION$/', $code, $matches) && !empty($matches[1])) {
                    $propertyCode = $matches[1];
                    $propertyValueKey = "PROPERTY_{$propertyCode}_VALUE";
                    if (!in_array($propertyValueKey, $selectedFields)) {
                        $selectedFields[] = $propertyValueKey;
                    }
                }

                // if we pass PROPERTY_X_ENUM_ID as selected field, we need to add PROPERTY_X_VALUE as well.
                if (preg_match('/^PROPERTY_(.*)_ENUM_ID$/', $code, $matches) && !empty($matches[1])) {
                    $propertyCode = $matches[1];
                    $propertyValueKey = "PROPERTY_{$propertyCode}_VALUE";
                    if (!in_array($propertyValueKey, $selectedFields)) {
                        $selectedFields[] = $propertyValueKey;
                    }
                }
            }
        }

        foreach ($this->fields as $code => $value) {
            if ($saveOnlySelected && !in_array($code, $selectedFields)) {
                continue;
            }

            if (preg_match('/^PROPERTY_(.*)_VALUE$/', $code, $matches) && !empty($matches[1])) {
                $propertyCode = $matches[1];
                $iblockPropertyData = (array) $iblockPropertiesData[$propertyCode];

                // if file was not changed skip it or it will be duplicated
                if ($iblockPropertyData && $iblockPropertyData['PROPERTY_TYPE'] === 'F' && !empty($this->original[$code]) && $this->original[$code] === $value) {
                    continue;
                }

                // if property type is a list we need to use enum ID/IDs as value/values
                if (array_key_exists("PROPERTY_{$propertyCode}_ENUM_ID", $this->fields)) {
                    $value = $this->fields["PROPERTY_{$propertyCode}_ENUM_ID"];
                } elseif ($iblockPropertyData && $iblockPropertyData['PROPERTY_TYPE'] === 'L' && $iblockPropertyData['MULTIPLE'] === 'Y') {
                    $value = array_keys($value);
                }

                // if property values have descriptions
                // we skip file properties here for now because they cause endless problems. Handle them manually.
                if (array_key_exists("PROPERTY_{$propertyCode}_DESCRIPTION", $this->fields) && (!$iblockPropertyData || $iblockPropertyData['PROPERTY_TYPE'] !== 'F')) {
                    $description = $this->fields["PROPERTY_{$propertyCode}_DESCRIPTION"];

                    if (is_array($value) && is_array($description)) {
                        // for multiple property
                        foreach ($value as $rowIndex => $rowValue) {
                            $propertyValues[$propertyCode][] = [
                                'VALUE' => $rowValue,
                                'DESCRIPTION' => $description[$rowIndex]
                            ];
                        }
                    } else {
                        // for single property
                        $propertyValues[$propertyCode] = [
                            'VALUE' => $value,
                            'DESCRIPTION' => $description
                        ];
                    }
                } else {
                    $propertyValues[$propertyCode] = $value;
                }
            }
        }

        return $propertyValues;
    }

    /**
     * Determine whether the field should be stopped from passing to "update".
     *
     * @param string $field
     * @param mixed  $value
     * @param array  $selectedFields
     *
     * @return bool
     */
    protected function fieldShouldNotBeSaved($field, $value, $selectedFields)
    {
        $blacklistedFields = [
            'ID',
            'IBLOCK_ID',
            'PROPERTIES',
            'PROPERTY_VALUES',
        ];

        return (!empty($selectedFields) && !in_array($field, $selectedFields))
            || in_array($field, $blacklistedFields)
            || ($field[0] === '~');
            //|| (substr($field, 0, 9) === 'PROPERTY_');
    }

    /**
     * @param $fields
     * @param $fieldsSelectedForSave
     * @return bool
     */
    protected function internalUpdate($fields, $fieldsSelectedForSave)
    {
        $fields = $fields ?: [];
        foreach ($fields as $key => $value) {
            if (substr($key, 0, 9) === 'PROPERTY_') {
                unset($fields[$key]);
            }
        }

        $result = !empty($fields) ? static::$bxObject->update($this->id, $fields, static::$workFlow, static::$updateSearch, static::$resizePictures) : false;
        $savePropsResult = $this->saveProps($fieldsSelectedForSave);
        $result = $result || $savePropsResult;

        return $result;
    }

    /**
     * Get value from language field according to current language.
     *
     * @param $field
     * @return mixed
     */
    protected function getValueFromLanguageField($field)
    {
        $key = $field . '_' . $this->getCurrentLanguage() . '_VALUE';

        return isset($this->fields[$key]) ? $this->fields[$key] : null;
    }

    /**
     * @param $value
     */
    public static function setWorkflow($value)
    {
        static::$workFlow = $value;
    }

    /**
     * @param $value
     */
    public static function setUpdateSearch($value)
    {
        static::$updateSearch = $value;
    }

    /**
     * @param $value
     */
    public static function setResizePictures($value)
    {
        static::$resizePictures = $value;
    }
}
