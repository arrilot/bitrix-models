<?php

namespace Arrilot\BitrixModels\Queries;

use Arrilot\BitrixModels\Models\UserModel;

class UserQuery extends BaseQuery
{
    /**
     * Query sort.
     *
     * @var array
     */
    protected $sort = ['last_name' => 'asc'];

    /**
     * Get item by its id.
     *
     * @param int $id
     *
     * @return UserModel|false
     */
    public function getById($id)
    {
        return parent::getById($id);
    }

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
            'FIELDS' => $this->prepareSelectForGetList(),
        ];

        $users = [];
        $rsUsers = $this->object->getList($this->sort, $sortOrder = false, $this->filter, $params);
        while ($arUser = $rsUsers->fetch()) {

            if ($this->groupsMustBeSelected()) {
                $arUser['GROUP_ID'] = $this->object->getUserGroup($arUser['ID']);
            }

            /** @var UserModel $user */
            $user = new $this->modelName;
            $user->fill($arUser);

            $this->addUsingKeyBy($users, $user);
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
}
