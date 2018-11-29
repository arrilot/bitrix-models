<?php

namespace Arrilot\Tests\BitrixModels\Stubs;

use Arrilot\BitrixModels\Models\ElementModel;
use Illuminate\Support\Collection;

/**
 * Class TestElement
 * @package Arrilot\Tests\BitrixModels\Stubs
 *
 * @property Collection|TestElement2[] $elements
 * @property TestElement2 $element
 */
class TestElement extends ElementModel
{
    protected $appends = ['ACCESSOR_THREE', 'PROPERTY_LANG_ACCESSOR_ONE'];
    
    protected $languageAccessors = ['PROPERTY_LANG_ACCESSOR_ONE'];

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

    public function elements()
    {
        return $this->hasMany(TestElement2::class, 'ID', 'PROPERTY_ELEMENT_VALUE');
    }

    public function element()
    {
        return $this->hasOne(TestElement2::class, 'PROPERTY_ELEMENT_VALUE', 'ID');
    }
}
