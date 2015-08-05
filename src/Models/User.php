<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\UserQuery;

class User extends Base
{
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
    protected static $objectClass = 'CUser';

    /**
     * List of params that can modify query.
     *
     * @var array
     */
    protected static $queryModifiers = [
        'sort',
        'filter',
        'navigation',
        'select',
        'withoutProps',
        'withoutGroups',
        'keyBy',
    ];

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
        return new UserQuery(static::instantiateObject());
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
     * @param $fields
     *
     * @return null
     */
    protected function afterFill($fields)
    {
        if (isset($fields['GROUP_ID']) && is_array(['GROUP_ID'])) {
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
        if (!$this->id) {
            return  $this->fields = [];
        }

        $groupBackup = isset($this->fields['GROUP_ID']) ? $this->fields['GROUP_ID'] : null;

        $this->fields = static::$object->getByID($this->id)->fetch();

        if ($groupBackup) {
            $this->fields['GROUP_ID'] = $groupBackup;
        }

        $this->$fieldsAreFetched = true;

        return $this->fields;
    }

    /**
     * Refresh user groups and save them to a class field.
     *
     * @return array
     */
    public function refreshGroups()
    {
        if (!$this->id) {
            return [];
        }

        global $USER;

        $this->fields['GROUP_ID'] = $this->isCurrent()
            ? $USER->getUserGroupArray()
            : static::$object->getUserGroup($this->id);

        $this->groupsAreFetched = true;

        return $this->fields['GROUP_ID'];
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin()
    {
        return $this->hasRoleWithId(1);
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
    public function hasRoleWithId($role_id)
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
