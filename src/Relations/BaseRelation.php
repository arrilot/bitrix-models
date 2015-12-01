<?php

namespace Arrilot\BitrixModels\Relations;

abstract class BaseRelation
{
    /**
     * Fetch the results of the relationship from DB.
     *
     * @return mixed
     */
    abstract public function fetch();
}
