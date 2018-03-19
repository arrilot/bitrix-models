<?php

namespace Arrilot\Tests\BitrixModels\Stubs;

use Arrilot\BitrixModels\Models\D7Model;

class TestD7Element2 extends D7Model
{
    public static function tableClass()
    {
        return TestD7ElementClass2::class;
    }
}
