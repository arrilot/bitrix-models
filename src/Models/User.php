<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\NotSetModelIdException;
use Arrilot\BitrixModels\Queries\UserQuery;
use Exception;

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
        'withProps',
        'withGroups',
        'listBy',
    ];

    /**
     * Have groups been already fetched from DB?
     *
     * @var bool
     */
    protected $groupsHaveBeenFetched = false;

    /**
     * Constructor.
     *
     * @param $id
     * @param $fields
     *
     * @throws Exception
     */
    public function __construct($id = null, $fields = null)
    {
        global $USER;
        $currentUserId = $USER->getID();

        $id = is_null($id) ? $currentUserId : $id;

        parent::__construct($id, $fields);
    }

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

        return new static($USER->getId(), $fields);
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

        $this->getGroups();

        return $this->fields;
    }

    /**
     * Fetch model fields from database and place them to $this->fields.
     *
     * @return array
     * @throws NotSetModelIdException
     */
    protected function fetch()
    {
        if (!$this->id) {
            throw new NotSetModelIdException();
        }

        $this->fields = static::$object->getByID($this->id)->fetch();

        $this->fetchGroups();

        $this->hasBeenFetched = true;

        return $this->fields;
    }

    /**
     * Get user groups from cache or database.
     *
     * @return array
     */
    public function getGroups()
    {
        if ($this->groupsHaveBeenFetched) {
            return $this->fields['GROUP_ID'];
        }

        return $this->fetchGroups();
    }

    /**
     * Fetch user groups and save them to a class field.
     *
     * @return array
     * @throws NotSetModelIdException
     */
    protected function fetchGroups()
    {
        if (!$this->id) {
            throw new NotSetModelIdException();
        }

        global $USER;

        $this->fields['GROUP_ID'] = $this->isCurrent()
            ? $USER->getUserGroupArray()
            : static::$object->getUserGroup($this->id);

        $this->fields['GROUPS'] = $this->fields['GROUP_ID']; // for backward compatibility

        $this->groupsHaveBeenFetched = true;

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

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $field = $key === 'groups' ? 'GROUP_ID' : $key;

        return isset($this->fields[$field]) ? $this->fields[$field] : null;
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $field = $key === 'groups' ? 'GROUP_ID' : $key;

        $this->fields[$field] = $value;
    }
}
