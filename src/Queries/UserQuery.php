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
     * Are groups needed in results?
     *
     * @var bool
     */
    protected $withGroups = false;

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
     * Setter for withGroups.
     *
     * @param $value
     *
     * @return $this
     */
    public function withGroups($value = true)
    {
        $this->withGroups = $value;

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
            'SELECT' => $this->withProps === true ? ['UF_*'] : $this->withProps,
            'NAV_PARAMS' => $this->navigation,
            'FIELDS' => $this->select,
        ];

        $users = [];
        $rsUsers = $this->object->getList($this->sort, $sortOrder = false, $this->filter, $params);
        while ($arUser = $rsUsers->fetch()) {

            if ($this->withGroups) {
                $arUser['GROUP_ID'] = $this->object->getUserGroup($arUser['ID']);
            }

            $listByValue = ($this->listBy && isset($arUser[$this->listBy])) ? $arUser[$this->listBy] : false;

            if ($listByValue) {
                $users[$listByValue] = $arUser;
            } else {
                $users[] = $arUser;
            }
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
