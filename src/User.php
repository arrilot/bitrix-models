<?

namespace Arrilot\BitrixModels;

use CUser;
use Exception;

class User extends Model
{
    /**
     * The array of user groups.
     *
     * @var array
     */
    public $groups;

    /**
     * Constructor.
     *
     * @param $id
     * @param $fields
     */
    public function __construct($id, $fields = null)
    {
        global $USER;

        $currentUserId = $USER->getID();

        $this->id = $id;
        $this->fields = $fields;

        $this->groups = ($currentUserId && $id == $currentUserId)
            ? $USER->getUserGroupArray()
            : (new CUser)->getUserGroup($id);
    }

    /**
     * Fetch model fields from database and place them to $this->fields.
     *
     * @return null
     * @throws InvalidModelIdException
     */
    public function fetch()
    {
        $this->fields = (new CUser)->getByID($this->id)->fetch();

        if (!$this->fields) {
            throw new InvalidModelIdException();
        }

        $this->setAdditionalFieldsWhileFetching();
    }

    /**
     * Add additional fields to $this->fields if they are not set yet.
     */
    protected function setAdditionalFieldsWhileFetching()
    {
        if (!isset($this->fields['GROUPS'])) {
            $this->fields['GROUPS'] = $this->groups; // for BC
        }
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

        $this->setAdditionalFieldsWhileFetching();

        return $this->fields;
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
        $user = new CUser;
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
        return in_array($role_id, $this->groups);
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
        return (new CUser)->delete($this->id);
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

        return (new CUser)->update($this->id, $fields);
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

        $extraPossibleFields = [
            'GROUP_ID' => $this->groups,
        ];

        $fields = [];

        foreach (array_merge($this->fields, $extraPossibleFields) as $field => $value) {
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
