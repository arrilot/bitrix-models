<?php
namespace Arrilot\Tests\BitrixModels\Stubs;

use Arrilot\BitrixModels\Models\HLModel;

class TestHL extends HLModel
{
    protected $appends = ['ACCESSOR_THREE'];

    static public $tableName = "test";

    public function getAccessorOneAttribute($value)
    {
        return '!'.$value.'!';
    }

    public function getAccessorTwoAttribute()
    {
        return $this['ID'].':'.$this['UF_NAME'];
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