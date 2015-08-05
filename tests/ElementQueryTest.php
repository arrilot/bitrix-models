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

    public function testGetListBasic()
    {
        $object = m::mock('object');
        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, false)->once()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $object->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' =>'foo'], ['ID' => 2, 'NAME' =>'bar']);

        $query = $this->createQuery($object);
        $items = $query->filter(['ACTIVE'=>'N'])->withoutProps()->getList();

        $expected = [
            1 => ['ID' => 1, 'NAME' =>'foo'],
            2 => ['ID' => 2, 'NAME' =>'bar'],
        ];

        $this->assertSame($expected, $items);
    }

    public function testGetListBasicWithKeyBy()
    {
        $object = m::mock('object');
        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, false)->once()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $object->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' =>'foo'], ['ID' => 2, 'NAME' =>'bar']);

        $query = $this->createQuery($object);
        $items = $query->filter(['ACTIVE'=>'N'])->keyBy(false)->withoutProps()->getList();

        $expected = [
            0 => ['ID' => 1, 'NAME' =>'foo'],
            1 => ['ID' => 2, 'NAME' =>'bar'],
        ];

        $this->assertSame($expected, $items);


        $object = m::mock('object');
        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, false)->once()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $object->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' =>'foo'], ['ID' => 2, 'NAME' =>'bar']);

        $query = $this->createQuery($object);
        $items = $query->filter(['ACTIVE'=>'N'])->keyBy('NAME')->withoutProps()->getList();

        $expected = [
            'foo' => ['ID' => 1, 'NAME' =>'foo'],
            'bar' => ['ID' => 2, 'NAME' =>'bar'],
        ];

        $this->assertSame($expected, $items);
    }

    public function testGetById()
    {
        $object = m::mock('object');
        $query = m::mock('Arrilot\BitrixModels\Queries\ElementQuery[getList]',[$object, 1]);
        $query->shouldReceive('getList')->once()->andReturn([
            [
                'ID' => 1,
                'NAME' => 2,
            ]
        ]);

        $this->assertSame(['ID' => 1, 'NAME' => 2], $query->getById(1));
        $this->assertSame(false, $query->getById(0));
    }
}
