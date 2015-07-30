<?

namespace Arrilot\BitrixModels;

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
     * Bitrix entity object for this type of model.
     *
     * @var object
     */
    protected $entity;

    /**
     * Bitrix entity class.
     *
     * @var string
     */
    protected static $entityClass = 'stdClass';

    /**
     * Constructor.
     *
     * @param $id
     * @param null $fields
     */
    public function __construct($id, $fields = null)
    {
        $this->id = $id;

        $this->fields = $fields;

        $this->entity = new self::$entityClass;
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
     * Fetch model fields from database and place them to $this->fields.
     *
     * @return null
     */
    abstract public function fetch();

    /**
     * Delete model with $this->id.
     *
     * @return bool
     */
    abstract public function delete();

    /**
     * Save model to database.
     *
     * @param array $fields save only these fields instead of all
     *
     * @return bool
     */
    abstract public function save(array $fields = []);
}
