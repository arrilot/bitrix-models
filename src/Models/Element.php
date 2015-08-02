<?

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\NotSetModelIdException;
use Arrilot\BitrixModels\Queries\ElementQuery;
use Exception;

class Element extends Base
{
    /**
     * Corresponding object class name.
     *
     * @var string
     */
    protected static $objectClass = 'CIBlockElement';

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
     * Create new element in database.
     *
     * @param $fields
     *
     * @return static
     * @throws Exception
     */
    public static function create($fields)
    {
        $object = static::instantiateObject();
        $id = $object->add($fields);

        if (!$id) {
            throw new Exception($object->LAST_ERROR);
        }

        $fields['ID'] = $id;

        return new static($id, $fields);
    }

    /**
     * CIblockElement::getList substitution.
     *
     * @param array $params
     *
     * @return array
     */
    public static function getList($params = [])
    {
        $query = static::query();

        $modifiers = [
            'sort',
            'filter',
            'groupBy',
            'navigation',
            'select',
            'withProps',
            'listBy',
        ];
        foreach ($modifiers as $modifier) {
            if (isset($params[$modifier])) {
                $query = $query->{$modifier}($params[$modifier]);
            }
        }

        return $query->getList();
    }

    /**
     * Get count of elements that match $filter.
     *
     * @param array $filter
     *
     * @return int
     */
    public static function count(array $filter = [])
    {
        return static::query()->filter($filter)->count();
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
     * @return array
     * @throws NotSetModelIdException
     */
    protected function fetch()
    {
        if (!$this->id) {
            throw new NotSetModelIdException();
        }

        $obElement = static::$object->getByID($this->id)->getNextElement();
        $this->fields = $obElement->getFields();

        $this->fields['PROPERTIES'] = $obElement->getProperties();

        static::setPropertyValues($this->fields);

        $this->hasBeenFetched = true;

        return $this->fields;
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
