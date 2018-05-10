<?php

namespace Arrilot\BitrixModels\Models\Traits;

use Arrilot\BitrixModels\Models\D7Model;
use Bitrix\Main\Type\DateTime;

trait DeactivationTrait
{
    /**
     * Active element.
     */
    public function activate()
    {
        $this->markForActivation()->save();
    }

    /**
     * Deactivate element.
     */
    public function deactivate()
    {
        $this->markForDeactivation()->save();
    }

    /**
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $this instanceof D7Model
            ? $query->filter(['==UF_DEACTIVATED_AT' => null])
            : $query->whereNull('UF_DEACTIVATED_AT');
    }
    
    /**
     * @param $query
     * @return mixed
     */
    public function scopeDeactivated($query)
    {
        return $this instanceof D7Model
            ? $query->filter(['!==UF_DEACTIVATED_AT' => null])
            : $query->whereNotNull('UF_DEACTIVATED_AT');
    }
    
    /**
     * @return $this
     */
    public function markForActivation()
    {
        $this['UF_DEACTIVATED_AT'] = null;

        return $this;
    }
    
    /**
     * @return $this
     */
    public function markForDeactivation()
    {
        $this['UF_DEACTIVATED_AT'] = $this instanceof D7Model ? new DateTime() : date('Y-m-d H:i:s');

        return $this;
    }
}
