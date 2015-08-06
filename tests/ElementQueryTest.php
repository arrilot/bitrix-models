<?php

namespace Arrilot\Tests\BitrixModels;


use Arrilot\BitrixModels\Queries\ElementQuery;
use Arrilot\Tests\BitrixModels\Stubs\TestElement;
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
        return new ElementQuery($object, 'Arrilot\Tests\BitrixModels\Stubs\TestElement', 1);
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

    public function testGetListWithSelectAndFilter()
    {
        $object = m::mock('object');
        TestElement::$object = $object;
        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME'])->once()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $object->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' =>'foo'], ['ID' => 2, 'NAME' =>'bar']);

        $query = $this->createQuery($object);
        $items = $query->filter(['ACTIVE'=>'N'])->select('ID', 'NAME')->getList();


        $expected = [
            1 => ['ID' => 1, 'NAME' =>'foo'],
            2 => ['ID' => 2, 'NAME' =>'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->toArray());
        }
    }

    public function testGetListWithKeyBy()
    {
        $object = m::mock('object');
        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, false)->once()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $object->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' =>'foo'], ['ID' => 2, 'NAME' =>'bar']);

        $query = $this->createQuery($object);
        $items = $query->filter(['ACTIVE'=>'N'])->keyBy(false)->select('FIELDS')->getList();

        $expected = [
            0 => ['ID' => 1, 'NAME' =>'foo'],
            1 => ['ID' => 2, 'NAME' =>'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->toArray());
        }


        $object = m::mock('object');
        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME'])->once()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $object->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' =>'foo'], ['ID' => 2, 'NAME' =>'bar']);

        $query = $this->createQuery($object);
        $items = $query->filter(['ACTIVE'=>'N'])->keyBy('NAME')->select(['ID', 'NAME'])->getList();

        $expected = [
            'foo' => ['ID' => 1, 'NAME' =>'foo'],
            'bar' => ['ID' => 2, 'NAME' =>'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->toArray());
        }
    }

    public function testGetById()
    {
        $object = m::mock('object');
        $query = m::mock('Arrilot\BitrixModels\Queries\ElementQuery[getList]',[$object, 'Arrilot\Tests\BitrixModels\Stubs\TestElement', 1]);
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
