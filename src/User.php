<?

namespace Arrilot\BitrixModels;

use CUser;

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
    public function __construct($id = null, $fields = null)
    {
        global $USER;

        if (is_null($id)) {
            $this->id = $USER->getID();
            $this->groups = $USER->getUserGroupArray();
        } else {
            $this->id = $id;
            $this->groups = (new CUser)->getUserGroup($id);
        }

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
        $this->fields = (new CUser)->getByID($this->id)->fetch();

        if (!$this->fields) {
            throw new InvalidModelIdException();
        }

        $this->fields['GROUPS'] = $this->groups;
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
     * Activate user.
     *
     * @return bool
     */
    public function activate()
    {
        return (new CUser)->update($this->id, ['ACTIVE' => 'Y']);
    }

    /**
     * Deactivate user.
     *
     * @return bool
     */
    public function deactivate()
    {
        return (new CUser)->update($this->id, ['ACTIVE' => 'N']);
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
}
