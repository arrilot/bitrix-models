<?

namespace Arrilot\BitrixModels;

use Exception;

class User extends Model
{
    /**
     * Bitrix entity class.
     *
     * @var CUser
     */
    protected static $entityClass = 'CUser';

    /**
     * Constructor.
     *
     * @param $id
     * @param $fields
     */
    public function __construct($id, $fields = null)
    {
        global $USER;

        $this->entity = new self::$entityClass;
        $this->id = $id;

        $currentUserId = $USER->getID();
        if (empty($fields['GROUP_ID'])) {
            $fields['GROUP_ID'] = ($currentUserId && $id == $currentUserId)
                ? $USER->getUserGroupArray()
                : $this->entity->getUserGroup($id);
        }

        $fields['GROUPS'] = $fields['GROUP_ID']; // for backward compatibility

        $this->fields = $fields;
    }

    /**
     * Fetch model fields from database and place them to $this->fields.
     *
     * @return null
     * @throws InvalidModelIdException
     */
    public function fetch()
    {
        $this->fields = $this->entity->getByID($this->id)->fetch();

        if (!$this->fields) {
            throw new InvalidModelIdException();
        }

        $this->setAdditionalFieldsWhileFetching();
    }

    /**
     * Add additional fields to $this->fields if they are not set yet.
     *
     * @return null
     */
    protected function setAdditionalFieldsWhileFetching()
    {
        if (!isset($this->fields['GROUP_ID'])) {
            $this->fields['GROUP_ID'] = $this->entity->getUserGroup($this->id);
            $this->fields['GROUPS'] = $this->fields['GROUP_ID'];
        }
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

        $this->setAdditionalFieldsWhileFetching();

        return $this->fields;
    }

    /**
     * Determine if model has already been fetched or filled with all fields.
     *
     * @return bool
     */
    protected function isFetched()
    {
        return isset($this->fields['NAME']);
    }

    /**
     * Get a new instance for the current user.
     */
    public static function current()
    {
        global $USER;

        return new static($USER->getId());
    }

    /**
     * Create new user in database.
     *
     * @param $fields
     *
     * @return static
     * @throws Exception
     */
    public static function create($fields)
    {
        $user = new self::$entityClass;
        $id = $user->add($fields);

        if (!$id) {
            throw new Exception($user->LAST_ERROR);
        }

        $fields['ID'] = $id;

        return new static($id, $fields);
    }

        /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->hasRoleWithId(1);
    }

    /**
     * Check if user has role with a given ID.
     *
     * @param $role_id
     *
     * @return bool
     */
    public function hasRoleWithId($role_id)
    {
        return in_array($role_id, $this->fields['GROUP_ID']);
    }

    /**
     * Check if user is authorized.
     *
     * @return bool
     */
    public function isAuthorized()
    {
        global $USER;

        return ($USER->getId() == $this->id) && $USER->isAuthorized();
    }

    /**
     * Delete user.
     *
     * @return bool
     */
    public function delete()
    {
        return $this->entity->delete($this->id);
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
        $this->get();

        $fields = $this->collectFieldsForSave($selectedFields);

        return $this->entity->update($this->id, $fields);
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
        $blacklistedFields = [
            'ID',
            'GROUPS',
        ];

        $fields = [];

        foreach ($this->fields as $field => $value) {
            // skip if it is not in selected fields
            if ($selectedFields && !in_array($field, $selectedFields)) {
                continue;
            }

            // skip blacklisted fields
            if (in_array($field, $blacklistedFields)) {
                continue;
            }

            // skip trash fields
            if (substr($field, 0, 1) === '~') {
                continue;
            }

            $fields[$field] = $value;
        }

        return $fields;
    }
}
