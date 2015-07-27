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
        if (is_null($this->fields)) {
            $this->fetch();
        }

        return $this->fields;
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
}
