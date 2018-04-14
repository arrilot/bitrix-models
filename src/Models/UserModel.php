<?php

namespace Arrilot\BitrixModels\Models;

use Arrilot\BitrixModels\Queries\UserQuery;
use Illuminate\Support\Collection;

/**
 * UserQuery methods
 * @method static static getByLogin(string $login)
 * @method static static getByEmail(string $email)
 *
 * Base Query methods
 * @method static Collection|static[] getList()
 * @method static static first()
 * @method static static getById(int $id)
 * @method static UserQuery sort(string|array $by, string $order='ASC')
 * @method static UserQuery order(string|array $by, string $order='ASC') // same as sort()
 * @method static UserQuery filter(array $filter)
 * @method static UserQuery addFilter(array $filters)
 * @method static UserQuery resetFilter()
 * @method static UserQuery navigation(array $filter)
 * @method static UserQuery select($value)
 * @method static UserQuery keyBy(string $value)
 * @method static UserQuery limit(int $value)
 * @method static UserQuery offset(int $value)
 * @method static UserQuery page(int $num)
 * @method static UserQuery take(int $value) // same as limit()
 * @method static UserQuery forPage(int $page, int $perPage=15)
 * @method static \Illuminate\Pagination\LengthAwarePaginator paginate(int $perPage = 15, string $pageName = 'page')
 * @method static \Illuminate\Pagination\Paginator simplePaginate(int $perPage = 15, string $pageName = 'page')
 * @method static UserQuery stopQuery()
 * @method static UserQuery cache(float|int $minutes)
 *
 * Scopes
 * @method static UserQuery active()
 * @method UserQuery fromGroup(int $groupId)
 */
class UserModel extends BitrixModel
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
     * Current user cache.
     *
     * @var static
     */
    protected static $currentUser = null;

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
     * Get a new instance for the current user
     *
     * @return static
     */
    public static function current()
    {
        return is_null(static::$currentUser)
            ? static::freshCurrent()
            : static::$currentUser;
    }

    /**
     * Get a fresh instance for the current user and save it to local cache.
     *
     * @return static
     */
    public static function freshCurrent()
    {
        global $USER;

        return static::$currentUser = (new static($USER->getId()))->load();
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
     * Load model fields from database if they are not loaded yet.
     *
     * @return $this
     */
    public function load()
    {
        $this->getFields();
        $this->getGroups();

        return $this;
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
     * Check if user is guest.
     *
     * @return bool
     */
    public function isGuest()
    {
        return ! $this->isAuthorized();
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

    /**
     * Scope to get only users from a given group / groups.
     *
     * @param UserQuery $query
     * @param int|array $id
     *
     * @return UserQuery
     */
    public function scopeFromGroup($query, $id)
    {
        $query->filter['GROUPS_ID'] = $id;

        return $query;
    }

    /**
     * Substitute old group with the new one.
     *
     * @param int $old
     * @param int $new
     *
     * @return void
     */
    public function substituteGroup($old, $new)
    {
        $groups = $this->getGroups();

        if(($key = array_search($old, $groups)) !== false) {
            unset($groups[$key]);
        }

        if (!in_array($new, $groups)) {
            $groups[] = $new;
        }

        $this->fields['GROUP_ID'] = $groups;
    }
}
