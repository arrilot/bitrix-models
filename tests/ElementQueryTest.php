<?php

namespace Arrilot\Tests\BitrixModels;

use Illuminate\Support\Collection;
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
        ElementQuery::$cIblockObject =  m::mock('ciblockObject');
        return new ElementQuery($bxObject, 'Arrilot\Tests\BitrixModels\Stubs\TestElement');
    }

    public function testCount()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with([], ['IBLOCK_ID' => 1], [])->once()->andReturn(6);

        $query = $this->createQuery($bxObject);
        $count = $query->count();

        $this->assertSame(6, $count);

        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with([], ['ACTIVE' => 'Y', 'IBLOCK_ID' => 1], [])->once()->andReturn(3);

        $query = $this->createQuery($bxObject);
        $count = $query->filter(['ACTIVE' => 'Y'])->count();

        $this->assertSame(3, $count);
    }

    public function testGetListWithSelectAndFilter()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', '!CODE' => false, 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['ACTIVE' => 'N'])->addFilter(['!CODE' => false])->select('ID', 'NAME')->getList();

        $expected = [
            1 => ['ID' => 1, 'NAME' => 'foo'],
            2 => ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testGetListWithKeyBy()
    {
        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['ACTIVE' => 'N'])->select('ID', 'NAME')->getList();

        $expected = [
            1 => ['ID' => 1, 'NAME' => 'foo', 'ACCESSOR_THREE' => [], 'PROPERTY_LANG_ACCESSOR_ONE' => null],
            2 => ['ID' => 2, 'NAME' => 'bar', 'ACCESSOR_THREE' => [], 'PROPERTY_LANG_ACCESSOR_ONE' => null],
        ];

        $this->assertSame($expected, $items->toArray());
        $this->assertSame(json_encode($expected), $items->toJson());

        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

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

    public function testGetListWithKeyByAndMissingKey()
    {
        $this->setExpectedException('LogicException');

        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['ACTIVE' => 'N'])->keyBy('GUID')->select(['ID', 'NAME'])->getList();

        $expected = [
            'foo' => ['ID' => 1, 'NAME' => 'foo'],
            'bar' => ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testGetListGroupsItemsByKeyBy()
    {
        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'PROPERTY_FOO', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'PROPERTY_FOO_VALUE' => 'foo'], ['ID' => 2, 'PROPERTY_FOO_VALUE' => 'bar'], ['ID' => 2, 'PROPERTY_FOO_VALUE' => 'bar2'], ['ID' => 2, 'PROPERTY_FOO_VALUE' => 'bar3'], false);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['ACTIVE' => 'N'])->select(['ID', 'PROPERTY_FOO'])->getList();

        $expected = [
            1 => ['ID' => 1, 'PROPERTY_FOO_VALUE' => 'foo'],
            2 => ['ID' => 2, 'PROPERTY_FOO_VALUE' => ['bar', 'bar2', 'bar3'], '_were_multiplied' => ['PROPERTY_FOO_VALUE' => true]],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testResetFilter()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['NAME' => 'John'])->resetFilter()->select('ID', 'NAME')->getList();

        $expected = [
            1 => ['ID' => 1, 'NAME' => 'foo'],
            2 => ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testScopeActive()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['NAME' => 'John', 'ACTIVE' => 'Y', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $items = $query->active()->filter(['NAME' => 'John'])->select('ID', 'NAME')->getList();

        $expected = [
            1 => ['ID' => 1, 'NAME' => 'foo'],
            2 => ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testFromSection()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['SECTION_ID' => 15, 'SECTION_CODE' => 'articles', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $items = $query
            ->fromSectionWithId(15)
            ->fromSectionWithCode('articles')
            ->select('ID', 'NAME')
            ->getList();

        $expected = [
            1 => ['ID' => 1, 'NAME' => 'foo'],
            2 => ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testGetById()
    {
        $bxObject = m::mock('obj');
        $query = m::mock('Arrilot\BitrixModels\Queries\ElementQuery[getList]', [$bxObject, 'Arrilot\Tests\BitrixModels\Stubs\TestElement', 1]);
        $query->shouldReceive('getList')->once()->andReturn(new Collection([
            1 => [
                'ID'   => 1,
                'NAME' => 2,
            ],
        ]));

        $this->assertSame(['ID' => 1, 'NAME' => 2], $query->getById(1));
        $this->assertSame(false, $query->getById(0));
    }

//    public function testGetListWithFetchUsing()
//    {
//        $bxObject = m::mock('obj');
//        $bxObject->shouldReceive('getList')
//            ->with(['SORT' => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'PROPERTY_GUID', 'IBLOCK_ID'])
//            ->once()
//            ->andReturn(m::self());
//        $bxObject->shouldReceive('Fetch')->andReturn(
//            ['ID' => 1, 'NAME' => 'foo', 'PROPERTY_GUID_VALUE' => 'foo'],
//            ['ID' => 2, 'NAME' => 'bar', 'PROPERTY_GUID_VALUE' => ''],
//            false
//        );
//
//        TestElement::$bxObject = $bxObject;
//        $query = $this->createQuery($bxObject);
//        $items = $query->filter(['ACTIVE' => 'N'])->select('ID', 'NAME', 'PROPERTY_GUID')->fetchUsing('Fetch')->getList();
//
//        $expected = [
//            1 => ['ID' => 1, 'NAME' => 'foo', 'PROPERTY_GUID_VALUE' => 'foo'],
//            2 => ['ID' => 2, 'NAME' => 'bar', 'PROPERTY_GUID_VALUE' => ''],
//        ];
//        foreach ($items as $k => $item) {
//            $this->assertSame($expected[$k], $item->fields);
//        }
//    }
//
//    public function testGetListWithFetchUsingAndNoProps()
//    {
//        $bxObject = m::mock('obj');
//        $bxObject->shouldReceive('getList')->with(['SORT'    => 'ASC'], ['ACTIVE' => 'N', 'IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
//        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);
//
//        TestElement::$bxObject = $bxObject;
//        $query = $this->createQuery($bxObject);
//        $items = $query->filter(['ACTIVE' => 'N'])->select('ID', 'NAME')->fetchUsing('Fetch')->getList();
//
//        $expected = [
//            1 => ['ID' => 1, 'NAME' => 'foo'],
//            2 => ['ID' => 2, 'NAME' => 'bar'],
//        ];
//        foreach ($items as $k => $item) {
//            $this->assertSame($expected[$k], $item->fields);
//        }
//    }

    public function testLimitAndPage()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['NAME' => 'John', 'IBLOCK_ID' => 1], false, ['iNumPage' => 3, 'nPageSize' => 2], ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['NAME' => 'John'])->page(3)->limit(2)->select('ID', 'NAME')->getList();

        $expected = [
            1 => ['ID' => 1, 'NAME' => 'foo'],
            2 => ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testSort()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with(['NAME' => 'DESC'], ['IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $query->sort(['NAME' => 'DESC'])
            ->select('ID', 'NAME')
            ->getList();

        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with(['NAME' => 'ASC'], ['IBLOCK_ID' => 1], false, false, ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $query->sort('NAME')
            ->select('ID', 'NAME')
            ->getList();
    }

    public function testFirst()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;
        $bxObject->shouldReceive('GetList')->with(['SORT' => 'ASC'], ['NAME' => 'John', 'IBLOCK_ID' => 1], false, ['nPageSize' => 1], ['ID', 'NAME', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], false);

        $query = $this->createQuery($bxObject);
        $item = $query->filter(['NAME' => 'John'])->select('ID', 'NAME')->first();

        $this->assertSame(['ID' => 1, 'NAME' => 'foo'], $item->fields);
    }

    public function testStopAction()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['NAME' => 'John'])->stopQuery()->getList();
        $this->assertSame((new Collection())->all(), $items->all());

        $query = $this->createQuery($bxObject);
        $item = $query->filter(['NAME' => 'John'])->stopQuery()->getById(1);
        $this->assertSame(false, $item);

        $query = $this->createQuery($bxObject);
        $count = $query->filter(['NAME' => 'John'])->stopQuery()->count();
        $this->assertSame(0, $count);
    }

    public function testStopActionFromScope()
    {
        $bxObject = m::mock('obj');
        TestElement::$bxObject = $bxObject;

        $query = $this->createQuery($bxObject);
        $items = $query->filter(['NAME' => 'John'])->stopActionScope()->getList();
        $this->assertSame((new Collection())->all(), $items->all());

        $query = $this->createQuery($bxObject);
        $item = $query->filter(['NAME' => 'John'])->stopActionScope()->getById(1);
        $this->assertSame(false, $item);

        $query = $this->createQuery($bxObject);
        $count = $query->filter(['NAME' => 'John'])->stopActionScope()->count();
        $this->assertSame(0, $count);
    }

    public function testPaginate()
    {
        if (!class_exists('Illuminate\Pagination\LengthAwarePaginator')) {
            $this->markTestSkipped();
        }
        $query = m::mock('Arrilot\BitrixModels\Queries\ElementQuery[getList, count]', [null, 'Arrilot\Tests\BitrixModels\Stubs\TestElement'])
            ->shouldAllowMockingProtectedMethods();

        $query->shouldReceive('count')->once()->andReturn(100);
        $query->shouldReceive('getList')->once()->andReturn(collect(range(1, 15)));

        $items = $query->paginate();

        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $items);
    }

    public function testSimplePaginate()
    {
        if (!class_exists('Illuminate\Pagination\Paginator')) {
            $this->markTestSkipped();
        }

        $query = m::mock('Arrilot\BitrixModels\Queries\ElementQuery[getList, count]', [null, 'Arrilot\Tests\BitrixModels\Stubs\TestElement'])
            ->shouldAllowMockingProtectedMethods();

        $query->shouldReceive('count')->never();
        $query->shouldReceive('getList')->once()->andReturn(collect(range(1, 15)));

        $items = $query->simplePaginate();

        $this->assertInstanceOf('Illuminate\Pagination\Paginator', $items);
    }
}
