<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\BitrixModels\Queries\ElementQuery;
use Arrilot\Tests\BitrixModels\Stubs\TestElement;
use Mockery as m;

class ElementQueryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * Create testing object with fixed ibId.
     *
     * @param $bxObject
     *
     * @return ElementQuery
     */
    protected function createQuery($bxObject)
    {
        return new ElementQuery($bxObject, 'Arrilot\Tests\BitrixModels\Stubs\TestElement');
    }

    public function testCount()
    {
        $bxObject = m::mock('object');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('getList')->with([], ['IBLOCK_ID' => 1], [])->once()->andReturn(6);

        $query = $this->createQuery($bxObject);
        $count = $query->count();

        $this->assertSame(6, $count);

        $bxObject = m::mock('object');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('getList')->with([], ['ACTIVE' => 'Y', 'IBLOCK_ID' => 1], [])->once()->andReturn(3);

        $query = $this->createQuery($bxObject);
        $count = $query->filter(['ACTIVE' => 'Y'])->count();

        $this->assertSame(3, $count);
    }

    public function testGetListWithSelectAndFilter()
    {
        $bxObject = m::mock('object');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('getList')->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $bxObject->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar']);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['ACTIVE' => 'N'])->select('ID', 'NAME')->getList();

        $expected = [
            ['ID' => 1, 'NAME' => 'foo'],
            ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testGetListWithKeyBy()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID','NAME'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $bxObject->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar']);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['ACTIVE' => 'N'])->keyBy(false)->select('ID', 'NAME')->getList();

        $expected = [
            ['ID' => 1, 'NAME' => 'foo', 'ACCESSOR_THREE' => []],
            ['ID' => 2, 'NAME' => 'bar', 'ACCESSOR_THREE' => []],
        ];

        $this->assertSame($expected, $items->toArray());
        $this->assertSame(json_encode($expected), $items->toJson());

        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $bxObject->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar']);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['ACTIVE' => 'N'])->keyBy('NAME')->select(['ID', 'NAME'])->getList();

        $expected = [
            'foo' => ['ID' => 1, 'NAME' => 'foo'],
            'bar' => ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testScopeActive()
    {
        $bxObject = m::mock('object');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('getList')->with(['SORT' => 'ASC'], ['NAME' => 'John', 'ACTIVE' => 'Y', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $bxObject->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar']);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['NAME' => 'John'])->active()->select('ID', 'NAME')->getList();

        $expected = [
            ['ID' => 1, 'NAME' => 'foo'],
            ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testGetById()
    {
        $bxObject = m::mock('object');
        $query = m::mock('Arrilot\BitrixModels\Queries\ElementQuery[getList]', [$bxObject, 'Arrilot\Tests\BitrixModels\Stubs\TestElement', 1]);
        $query->shouldReceive('getList')->once()->andReturn([
            [
                'ID'   => 1,
                'NAME' => 2,
            ],
        ]);

        $this->assertSame(['ID' => 1, 'NAME' => 2], $query->getById(1));
        $this->assertSame(false, $query->getById(0));
    }

    public function testGetListWithFetchUsing()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')
            ->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'PROPERTY_GUID'])
            ->once()
            ->andReturn(m::self());
        $bxObject->shouldReceive('getNext')->andReturn(
            ['ID' => 1, 'NAME' => 'foo', 'PROPERTY_GUID_VALUE' => 'foo'],
            ['ID' => 2, 'NAME' => 'bar', 'PROPERTY_GUID_VALUE' => ''],
            false
        );

        TestElement::$bxObject = $bxObject;
        $query = $this->createQuery($bxObject);
        $items = $query->filter(['ACTIVE' => 'N'])->select('ID', 'NAME', 'PROPERTY_GUID')->fetchUsing('getNext')->getList();

        $expected = [
            ['ID' => 1, 'NAME' => 'foo', 'PROPERTY_VALUES' => ['GUID' => 'foo']],
            ['ID' => 2, 'NAME' => 'bar', 'PROPERTY_VALUES' => ['GUID' => '']],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testGetListWithFetchUsingAndNoProps()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->with(['SORT'    => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('getNext')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        TestElement::$bxObject = $bxObject;
        $query = $this->createQuery($bxObject);
        $items = $query->filter(['ACTIVE' => 'N'])->select('ID', 'NAME')->fetchUsing('getNext')->getList();

        $expected = [
            ['ID' => 1, 'NAME' => 'foo'],
            ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testLimitAndPage()
    {
        $bxObject = m::mock('object');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('getList')->with(['SORT' => 'ASC'], ['NAME' => 'John','IBLOCK_ID' => 1], false, ['iNumPage' => 3,'nPageSize' => 2], ['ID', 'NAME'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $bxObject->shouldReceive('getFields')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar']);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['NAME' => 'John'])->page(3)->limit(2)->select('ID', 'NAME')->getList();

        $expected = [
            ['ID' => 1, 'NAME' => 'foo'],
            ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }
}
