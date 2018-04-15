<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\BitrixModels\Queries\ElementQuery;
use Arrilot\Tests\BitrixModels\Stubs\TestElement;
use Arrilot\Tests\BitrixModels\Stubs\TestElement2;
use Illuminate\Support\Collection;
use Mockery as m;

class RelationTest extends TestCase
{
    public function testMany()
    {
        $cIblockObject = m::mock('cIblockObject');
        $cIblockObject->shouldReceive('GetProperties')->withAnyArgs()->andReturn(m::self());
        $cIblockObject->shouldReceive('Fetch')->times(3)->andReturn(
            [
                'ID'        => '1',
                'IBLOCK_ID' => TestElement::IBLOCK_ID,
                'CODE'      => 'ELEMENT',
            ],
            false,
            false
        );
        ElementQuery::$cIblockObject = $cIblockObject;

        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('GetList')->withAnyArgs()->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->times(2)->andReturn(
            [
                'ID'   => 1,
                'NAME' => 'Название',
                'PROPERTY_ELEMENT_VALUE'        => ['1', '2'],
                'PROPERTY_ELEMENT_DESCRIPTION'  => ['', ''],
                'PROPERTY_ELEMENT_VALUE_ID'     => ['element_prop_id_1', 'element_prop_id_2'],
            ],
            false
        );
        TestElement::$bxObject = $bxObject;

        $product = TestElement::getById(1);

        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('GetList')->with(m::any(), ['IBLOCK_ID' => TestElement2::IBLOCK_ID, 'ID' => [1, 2]], m::any(), false, m::any())->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->times(3)->andReturn(
            [
                'ID'   => 1,
                'NAME' => 'Название',
            ],
            [
                'ID'   => 2,
                'NAME' => 'Название 2',
            ],
            false
        );
        TestElement::$bxObject = $bxObject;


        $this->assertInstanceOf(Collection::class, $product->elements);
        $this->assertCount(2, $product->elements);
        $this->assertEquals(['ID' => 1, 'NAME' => 'Название'], $product->elements[1]->fields);
        $this->assertEquals(['ID' => 2, 'NAME' => 'Название 2'], $product->elements[2]->fields);

    }

    public function testWith()
    {
        $cIblockObject = m::mock('cIblockObject');
        $cIblockObject->shouldReceive('GetProperties')->withAnyArgs()->andReturn(m::self());
        $cIblockObject->shouldReceive('Fetch')->times(3)->andReturn(
            false,
            [
                'ID'        => '1',
                'IBLOCK_ID' => TestElement::IBLOCK_ID,
                'CODE'      => 'ELEMENT',
            ],
            false
        );
        ElementQuery::$cIblockObject = $cIblockObject;

        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('GetList')->withAnyArgs()->twice()->andReturn(m::self());
        $brandField = [
            'ID' => 1,
            'NAME' => 'Название',
            'PROPERTY_ELEMENT_VALUE' => '1',
            'PROPERTY_ELEMENT_DESCRIPTION' => '',
            'PROPERTY_ELEMENT_VALUE_ID' => 'element_prop_id',
        ];

        $bxObject->shouldReceive('Fetch')->times(4)->andReturn(
            [
                'ID'   => 1,
                'NAME' => 'Название',
            ],
            false,
            $brandField,
            false
        );
        TestElement::$bxObject = $bxObject;

        $product = TestElement::query()->with('element')->getById(1);

        // Проверяем что все запросы были выполнены до текущего момента
        $cIblockObject->mockery_verify();
        $bxObject->mockery_verify();

        $this->assertEquals($brandField, $product->element->fields);
    }

    public function testOne()
    {
        $cIblockObject = m::mock('cIblockObject');
        $cIblockObject->shouldReceive('GetProperties')->withAnyArgs()->andReturn(m::self());
        $cIblockObject->shouldReceive('Fetch')->times(3)->andReturn(
            false,
            [
                'ID'        => '1',
                'IBLOCK_ID' => TestElement::IBLOCK_ID,
                'CODE'      => 'ELEMENT',
            ],
            false
        );
        ElementQuery::$cIblockObject = $cIblockObject;

        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('GetList')->withAnyArgs()->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->times(2)->andReturn(
            [
                'ID' => 1,
                'NAME' => 'Название',
            ],
            false
        );
        TestElement::$bxObject = $bxObject;

        $product = TestElement::getById(1);


        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('GetList')->with(m::any(), ['IBLOCK_ID' => TestElement2::IBLOCK_ID, 'PROPERTY_ELEMENT' => [1]], m::any(), ['nPageSize' => 1], m::any())->once()->andReturn(m::self());
        $brandField = [
            'ID' => 1,
            'NAME' => 'Название',
        ];
        $bxObject->shouldReceive('Fetch')->times(2)->andReturn(
            $brandField,
            false
        );
        TestElement::$bxObject = $bxObject;

        $this->assertEquals($brandField, $product->element->fields);

        // Проверка, что не выполняются дополнительные запросы
        $product->element;
    }
}