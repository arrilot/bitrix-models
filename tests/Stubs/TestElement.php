<?php

namespace Arrilot\Tests\BitrixModels\Stubs;

use Arrilot\BitrixModels\Models\ElementModel;

class TestElement extends ElementModel
{
    protected $appends = ['ACCESSOR_THREE'];

    const IBLOCK_ID = 1;

    public function getAccessorOneAttribute($value)
    {
        return '!'.$value.'!';
    }

    public function getAccessorTwoAttribute()
    {
        return $this['ID'].':'.$this['NAME'];
    }

    public function getAccessorThreeAttribute()
    {
        return [];
    }

    public function scopeStopActionScope($query)
    {
        return false;
    }
}
