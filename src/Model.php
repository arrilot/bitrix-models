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
     * Constructor.
     *
     * @param $id
     * @param null $fields
     */
    public function __construct($id, $fields = null)
    {
        $this->id = $id;

        $this->fields = $fields;
    }

    /**
     * Get model fields from cache or database.
     *
     * @return array
     */
    public function get()
    {
        if (!$this->fields) {
            $this->fetch();
        }

        $this->fetch();

        return $this->fields;
    }

    /**
     * Fetch model fields from database and save them to $this->fields.
     *
     * @return null
     */
    abstract public function fetch();
}
