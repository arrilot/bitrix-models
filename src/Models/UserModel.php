<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\UserQuery;

class UserModel extends BaseModel
{
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
    protected static $objectClass = 'CUser';

    /**
     * Have groups been already fetched from DB?
     *
     * @var bool
     */
    protected $groupsAreFetched = false;

    /**
     * Instantiate a query object for the model.
     *
     * @return UserQuery
     */
    public static function query()
    {
        return new UserQuery(static::instantiateObject(), get_called_class());
    }

    /**
     * Get a new instance for the current user.
     *
     * @param null $fields
     *
     * @return static
     */
    public static function current($fields = null)
    {
        global $USER;

        $user = new static($USER->getId());

        if (!is_null($fields)) {
            $user->fill($fields);
        }

        return $user;
    }

    /**
     * Fill extra fields when $this->field is called.
     *
     * @return null
     */
    protected function afterFill()
    {
        if (isset($this->fields['GROUP_ID']) && is_array(['GROUP_ID'])) {
            $this->groupsAreFetched = true;
        }
    }

    /**
     * Fill model groups if they are already known.
     * Saves DB queries.
     *
     * @param array $groups
     *
     * @return null
     */
    public function fillGroups($groups)
    {
        $this->fields['GROUP_ID'] = $groups;

        $this->groupsAreFetched = true;
    }

    /**
     * Get all model attributes from cache or database.
     *
     * @return array
     */
    public function get()
    {
        $this->getFields();

        $this->getGroups();

        return $this->fields;
    }

    /**
     * Get user groups from cache or database.
     *
     * @return array
     */
    public function getGroups()
    {
        if ($this->groupsAreFetched) {
            return $this->fields['GROUP_ID'];
        }

        return $this->refreshGroups();
    }

    /**
     * Refresh model from database and place data to $this->fields.
     *
     * @return array
     */
    public function refresh()
    {
        $this->refreshFields();

        $this->refreshGroups();

        return $this->fields;
    }

    /**
     * Refresh user fields and save them to a class field.
     *
     * @return array
     */
    public function refreshFields()
    {
        if ($this->id === null) {
            return  $this->fields = [];
        }

        $groupBackup = isset($this->fields['GROUP_ID']) ? $this->fields['GROUP_ID'] : null;

        $this->fields = static::query()->getById($this->id)->fields;

        if ($groupBackup) {
            $this->fields['GROUP_ID'] = $groupBackup;
        }

        $this->fieldsAreFetched = true;

        return $this->fields;
    }

    /**
     * Refresh user groups and save them to a class field.
     *
     * @return array
     */
    public function refreshGroups()
    {
        if ($this->id === null) {
            return [];
        }

        global $USER;

        $this->fields['GROUP_ID'] = $this->isCurrent()
            ? $USER->getUserGroupArray()
            : static::$bxObject->getUserGroup($this->id);

        $this->groupsAreFetched = true;

        return $this->fields['GROUP_ID'];
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->hasGroupWithId(1);
    }

    /**
     * Check if this user is the operating user.
     */
    public function isCurrent()
    {
        global $USER;

        return $USER->getId() && $this->id == $USER->getId();
    }

    /**
     * Check if user has role with a given ID.
     *
     * @param $role_id
     *
     * @return bool
     */
    public function hasGroupWithId($role_id)
    {
        return in_array($role_id, $this->getGroups());
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
     * Logout user.
     *
     * @return void
     */
    public function logout()
    {
        global $USER;

        $USER->logout();
    }
}
