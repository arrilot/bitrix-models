<?php

namespace Arrilot\BitrixModels\Queries;

use Arrilot\BitrixModels\Models\UserModel;

/**
 * @method UserQuery fromGroup($groupId)
 * @method UserQuery active()
 */
class UserQuery extends BaseQuery
{
    /**
     * Query sort.
     *
     * @var array
     */
    public $sort = ['last_name' => 'asc'];

    /**
     * CUser::getList substitution.
     *
     * @return UserModel[]
     */
    public function getList()
    {
        $params = [
            'SELECT' => $this->propsMustBeSelected() ? ['UF_*'] : false,
            'NAV_PARAMS' => $this->navigation,
            'FIELDS' => $this->normalizeSelect(),
        ];

        $users = [];
        $rsUsers = $this->object->getList($this->sort, $sortOrder = false, $this->normalizeFilter(), $params);
        while ($arUser = $rsUsers->fetch()) {

            if ($this->groupsMustBeSelected()) {
                $arUser['GROUP_ID'] = $this->object->getUserGroup($arUser['ID']);
            }

            $this->addItemToResultsUsingKeyBy($users, new $this->modelName($arUser['ID'], $arUser));
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
        return $this->object->getList($order = 'ID', $by = 'ASC', $this->normalizeFilter(), [
            'NAV_PARAMS' => [
                'nTopCount' => 0
            ]
        ])->NavRecordCount;
    }

    /**
     * Determine if groups must be selected.
     *
     * @return bool
     */
    protected function groupsMustBeSelected()
    {
        return in_array('GROUPS', $this->select) || in_array('GROUP_ID', $this->select);
    }


    /**
     * Normalize filter before sending it to getList.
     * This prevents some inconsistency.
     *
     * @return array
     */
    protected function normalizeFilter()
    {
        $this->substituteField($this->filter, 'GROUPS', 'GROUPS_ID');
        $this->substituteField($this->filter, 'GROUP_ID', 'GROUPS_ID');

        return $this->filter;
    }

    /**
     * Normalize select before sending it to getList.
     * This prevents some inconsistency.
     *
     * @return array
     */
    protected function normalizeSelect()
    {
        $strip = ['FIELDS', 'PROPS', 'GROUPS', 'GROUP_ID'];

        return array_diff($this->select, $strip);
    }
}
