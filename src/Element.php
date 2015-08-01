<?

namespace Arrilot\BitrixModels;

use Exception;

class Element extends Model
{
    /**
     * Corresponding object class name.
     *
     * @var string
     */
    protected static $objectClass = 'CIBlockElement';

    /**
     * Corresponding iblock id.
     *
     * @var null|int
     */
    protected static $iblockId;

    /**
     * Create new element in database.
     *
     * @param $fields
     *
     * @return static
     * @throws Exception
     */
    public static function create($fields)
    {
        $element = static::instantiateObject();
        $id = $element->add($fields);

        if (!$id) {
            throw new Exception($element->LAST_ERROR);
        }

        $fields['ID'] = $id;

        return new static($id, $fields);
    }

    /**
     * Create new element in database.
     *
     * @param array $params
     *
     * @return static
     * @throws Exception
     */
    public static function getList($params = [])
    {
        $element = static::instantiateObject();

        self::normalizeGetListParams($params);

        $items = [];
        $rsItems = $element->getList($params['sort'], $params['filter'], $params['groupBy'], $params['navigation'], $params['select']);
        while($obItem = $rsItems->GetNextElement())
        {
            $item = $obItem->getFields();
            if ($params['withProps']) {
                $item['PROPERTIES'] = $obItem->getProperties();
                static::setPropertyValues($item);
            }

            $items[$item['ID']] = $item;
        }

        return $items;
    }

    /**
     * Normalize params for static::getList().
     *
     * @param $params
     *
     * @return null
     */
    protected static function normalizeGetListParams(&$params)
    {
        $inspectedParamsWithDefaults = [
            'sort'       => ["SORT" => "ASC"],
            'filter'     => [],
            'groupBy'    => false,
            'navigation' => false,
            'select'     => [],
            'withProps'  => true,
        ];

        foreach ($inspectedParamsWithDefaults as $param => $default) {
            if (!isset($params[$param])) {
                $params[$param] = $default;
            }
        }

        if (static::$iblockId) {
            $params['filter']['IBLOCK_ID'] = static::$iblockId;
        }
    }

    /**
     * Constructor.
     *
     * @param $id
     * @param $fields
     *
     * @throws Exception
     */
    public function __construct($id, $fields = null)
    {
        parent::__construct($id, $fields);

        static::setPropertyValues($this->fields);
    }

    /**
     * Fetch element fields from database and place them to $this->fields.
     *
     * @return null
     * @throws InvalidModelIdException
     */
    public function fetch()
    {
        $obElement = static::$object->getByID($this->id)->getNextElement();
        if (!$obElement) {
            throw new InvalidModelIdException();
        }

        $this->fields = $obElement->getFields();

        $this->fields['PROPERTIES'] = $obElement->getProperties();

        static::setPropertyValues($this->fields);

        $this->hasBeenFetched = true;
    }

    /**
     * Set $field['PROPERTY_VALUES'] from $field['PROPERTIES'].
     *
     * @param array $fields
     *
     * @return null
     */
    protected static function setPropertyValues(&$fields)
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
     * Get model fields from cache or database.
     *
     * @return array
     */
    public function get()
    {
        if (!$this->hasBeenFetched) {
            $this->fetch();
        }

        return $this->fields;
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
