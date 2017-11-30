<?php

namespace Arrilot\BitrixModels\Queries;

use Illuminate\Support\Collection;

class D7Query extends BaseQuery
{
    /**
     * Get count of users that match $filter.
     *
     * @return int
     */
    public function count()
    {
        // TODO: Implement count() method.
    }
    
    /**
     * Get list of items.
     *
     * @return Collection
     */
    public function getList()
    {
        if ($this->queryShouldBeStopped) {
            return new Collection();
        }
    
        $rows = [];
//        $rsSections = $this->bxObject->getList(
//            $this->sort,
//            $this->normalizeFilter(),
//            $this->countElements,
//            $this->normalizeSelect(),
//            $this->navigation
//        );
//        while ($arSection = $rsSections->Fetch()) {
//            $this->addItemToResultsUsingKeyBy($sections, new $this->modelName($arSection['ID'], $arSection));
//        }
    
        return new Collection($rows);
    }
}
