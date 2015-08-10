<?php

namespace Arrilot\Tests\BitrixModels\Stubs;

use Arrilot\BitrixModels\Models\ElementModel;

class TestElement extends ElementModel
{
    protected $appends = ['ACCESSOR_THREE'];

    public static function iblockId()
    {
        return 1;
    }

    public function getAccessorOneField($value)
    {
        return '!'.$value.'!';
    }

    public function getAccessorTwoField()
    {
        return $this['ID'].':'.$this['NAME'];
    }

    public function getAccessorThreeField()
    {
        return [];
    }
}
