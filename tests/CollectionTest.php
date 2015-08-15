<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\BitrixModels\Collection;
use Mockery as m;

class CollectionTest extends TestCase
{
    public function testEven()
    {
        $data = new Collection(['a', 'b', 'c', 'd', 'e', 'f']);

        $this->assertEquals(['a', 'c', 'e'], $data->even()->all());
    }

    public function testOdd()
    {
        $data = new Collection(['a', 'b', 'c', 'd', 'e', 'f']);

        $this->assertEquals(['b', 'd', 'f'], $data->odd()->all());
    }

    public function testEvery()
    {
        $data = new Collection([
            6 => 'a',
            4 => 'b',
            7 => 'c',
            1 => 'd',
            5 => 'e',
            3 => 'f',
        ]);

        $this->assertEquals(['a', 'e'], $data->every(4)->all());
        $this->assertEquals(['b', 'f'], $data->every(4, 1)->all());
        $this->assertEquals(['c'], $data->every(4, 2)->all());
        $this->assertEquals(['d'], $data->every(4, 3)->all());
    }
}
