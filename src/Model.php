<?

namespace Arrilot\BitrixModels;

use Exception;

abstract class Model
{
    /**
     * ID of the model.
     *
     * @var int
     */
    public $id;

    /**
     * Array of model fields.
     *
     * @var array
     */
    public $fields;

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
     * Constructor.
     *
     * @param      $id
     * @param null $fields
     */
    public function __construct($id, $fields = null)
    {
        $this->id = $id;

        $this->fields = $fields;

        static::instantiateObject();
    }

    /**
     * Get model fields from cache or database.
     *
     * @return array
     */
    public function get()
    {
        if (!$this->isFetched()) {
            $this->fetch();
        }

        return $this->fields;
    }

    /**
     * Determine if model has already been fetched or filled with all fields.
     *
     * @return bool
     */
    protected function isFetched()
    {
        return !is_null($this->fields);
    }

    /**
     * Activate model.
     *
     * @return bool
     */
    public function activate()
    {
        $this->fields['ACTIVE'] = 'Y';

        return $this->save(['ACTIVE']);
    }

    /**
     * Deactivate model.
     *
     * @return bool
     */
    public function deactivate()
    {
        $this->fields['ACTIVE'] = 'N';

        return $this->save(['ACTIVE']);
    }

    /**
     * Delete model.
     *
     * @return bool
     */
    public function delete()
    {
        return static::$object->delete($this->id);
    }

    /**
     * Update model.
     *
     * @param array $fields
     *
     * @return bool
     */
    public function update(array $fields = [])
    {
        $keys = [];
        foreach ($fields as $key => $value) {
            $this->fields[$key] = $value;
            $keys[] = $key;
        }

        return $this->save($keys);
    }

    /**
     * Refresh model from database.
     *
     * @return null
     */
    public function refresh()
    {
        $this->fetch();
    }


    /**
     * Instantiate bitrix entity object.
     *
     * @return object
     * @throws Exception
     */
    public static function instantiateObject()
    {
        if (static::$object) {
            return static::$object;
        }

        if (class_exists(static::$objectClass)) {
            return static::$object = new static::$objectClass;
        }

        throw new Exception('Object initialization failed');
    }

    /**
     * Destroy bitrix entity object.
     *
     * @return null
     */
    public static function destroyObject()
    {
        static::$object = null;
    }

    /**
     * Fetch model fields from database and place them to $this->fields.
     *
     * @return null
     */
    abstract public function fetch();

    /**
     * Save model to database.
     *
     * @param array $fields save only these fields instead of all
     *
     * @return bool
     */
    abstract public function save(array $fields = []);
}
