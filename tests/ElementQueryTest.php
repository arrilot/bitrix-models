<?php

namespace Arrilot\Tests\BitrixModels;


use Arrilot\BitrixModels\Queries\ElementQuery;
use Mockery as m;

class ElementsQueryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * Create testing object with fixed ibId.
     *
     * @param $object
     *
     * @return ElementQuery
     */
    protected function createQuery($object)
    {
        return new ElementQuery($object, 1);
    }

    public function testCount()
    {
        $object = m::mock('object');
        $object->shouldReceive('getList')->with([], ['IBLOCK_ID' => 1], [])->once()->andReturn(6);

        $query = $this->createQuery($object);
        $count = $query->count();

        $this->assertSame(6, $count);


        $object = m::mock('object');
        $object->shouldReceive('getList')->with([], ['ACTIVE'=>'Y', 'IBLOCK_ID' => 1], [])->once()->andReturn(3);

        $query = $this->createQuery($object);
        $count = $query->filter(['ACTIVE' => 'Y'])->count();

        $this->assertSame(3, $count);
    }
}
