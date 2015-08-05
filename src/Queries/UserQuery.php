<?php

namespace Arrilot\BitrixModels\Queries;

class UserQuery extends BaseQuery
{
    /**
     * Query sort.
     *
     * @var array
     */
    protected $sort = ['last_name' => 'asc'];

    /**
     * Do not fetch groups.
     *
     * @var bool
     */
    protected $withoutGroups = false;

    /**
     * Constructor.
     *
     * @param object $object
     */
    public function __construct($object)
    {
        $this->object = $object;
    }

    /**
     * Setter for withoutGroups.
     *
     * @param $value
     *
     * @return $this
     */
    public function withoutGroups($value = true)
    {
        $this->withoutGroups = $value;

        return $this;
    }

    /**
     * CUser::getList substitution.
     *
     * @return array
     */
    public function getList()
    {
        $params = [
            'SELECT' => $this->withoutProps === false ? ['UF_*'] : false,
            'NAV_PARAMS' => $this->navigation,
            'FIELDS' => $this->select,
        ];

        $users = [];
        $rsUsers = $this->object->getList($this->sort, $sortOrder = false, $this->filter, $params);
        while ($arUser = $rsUsers->fetch()) {

            if ($this->withoutGroups === false) {
                $arUser['GROUP_ID'] = $this->object->getUserGroup($arUser['ID']);
            }

            $this->addUsingKeyBy($users, $arUser);
        }

        return $users;
    }

    /**
     * Get count of users that match $filter.
     *
     * @return int
     */
    public function count()
    {
        return $this->object->getList($order = 'ID', $by = 'ASC', $this->filter, [
            'NAV_PARAMS' => [
                "nTopCount" => 0
            ]
        ])->NavRecordCount;
    }
}
