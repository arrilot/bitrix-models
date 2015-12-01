<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\BitrixModels\Queries\ElementQuery;
use Arrilot\Tests\BitrixModels\Stubs\TestElement;
use Mockery as m;

class ElementModelTest extends TestCase
{
    public function setUp()
    {
        TestElement::$bxObject = m::mock('object');
        ElementQuery::$cIblockObject = m::mock('cIblockObject');
    }

    public function tearDown()
    {
        TestElement::destroyObject();
        m::close();
    }

    public function testInitialization()
    {
        $element = new TestElement(1);
        $this->assertSame(1, $element->id);

        $fields = [
            'NAME'      => 'John',
            'LAST_NAME' => 'Doe',
        ];
        $element = new TestElement(1, $fields);
        $this->assertSame(1, $element->id);
        $this->assertSame($fields, $element->fields);
    }

    public function testDelete()
    {
        // normal
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('delete')->once()->andReturn(true);

        TestElement::$bxObject = $bxObject;
        $element = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestElement[onAfterDelete, onBeforeDelete]', [1])
            ->shouldAllowMockingProtectedMethods();
        $element->shouldReceive('onBeforeDelete')->once()->andReturn(null);
        $element->shouldReceive('onAfterDelete')->once()->with(true);

        $this->assertTrue($element->delete());

        // cancelled
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('delete')->never();

        TestElement::$bxObject = $bxObject;
        $element = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestElement[onAfterDelete, onBeforeDelete]', [1])
            ->shouldAllowMockingProtectedMethods();
        $element->shouldReceive('onBeforeDelete')->once()->andReturn(false);
        $element->shouldReceive('onAfterDelete')->never();

        $this->assertFalse($element->delete());
    }

    public function testActivate()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('update')->with(1, ['ACTIVE' => 'Y'])->once()->andReturn(true);

        TestElement::$bxObject = $bxObject;
        $element = new TestElement(1);

        $this->assertTrue($element->activate());
    }

    public function testDeactivate()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('update')->with(1, ['ACTIVE' => 'N'])->once()->andReturn(true);

        TestElement::$bxObject = $bxObject;
        $element = new TestElement(1);

        $this->assertTrue($element->deactivate());
    }

    public function testGet()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->withAnyArgs()->once()->andReturn(m::self());
        $bxObject->shouldReceive('getNextElement')->twice()->andReturn(m::self(), false);
        $bxObject->shouldReceive('getFields')->once()->andReturn([
            'ID'   => 1,
            'NAME' => 'John Doe',
        ]);
        $bxObject->shouldReceive('getProperties')->once()->andReturn([
            'FOO' => [
                'VALUE'             => 'bar',
                '~VALUE'            => '~bar',
                'DESCRIPTION'       => 'baz',
                '~DESCRIPTION'      => '~baz',
                'PROPERTY_VALUE_ID' => 'bar_id',
            ],
        ]);

        TestElement::$bxObject = $bxObject;
        $element = new TestElement(1);

        $expected = [
            'ID'         => 1,
            'NAME'       => 'John Doe',
            'PROPERTIES' => [
                'FOO' => [
                    'VALUE'             => 'bar',
                    '~VALUE'            => '~bar',
                    'DESCRIPTION'       => 'baz',
                    '~DESCRIPTION'      => '~baz',
                    'PROPERTY_VALUE_ID' => 'bar_id',
                ],
            ],
            'PROPERTY_FOO_VALUE'        => 'bar',
            '~PROPERTY_FOO_VALUE'       => '~bar',
            'PROPERTY_FOO_DESCRIPTION'  => 'baz',
            '~PROPERTY_FOO_DESCRIPTION' => '~baz',
            'PROPERTY_FOO_VALUE_ID'     => 'bar_id',
        ];
        $this->assertEquals($expected, $element->get());
        $this->assertEquals($expected, $element->fields);

        // second call to make sure we do not query database twice.
        $this->assertSame($expected, $element->get());
        $this->assertSame($expected, $element->fields);
    }

    public function testRefresh()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->withAnyArgs()->twice()->andReturn(m::self());
        $bxObject->shouldReceive('getNextElement')->times(4)->andReturn(m::self(), false, m::self(), false);
        $bxObject->shouldReceive('getFields')->twice()->andReturn([
            'ID'   => 1,
            'NAME' => 'John Doe',
        ]);
        $bxObject->shouldReceive('getProperties')->twice()->andReturn([
            'FOO' => [
                'VALUE'             => 'bar',
                '~VALUE'            => '~bar',
                'DESCRIPTION'       => 'baz',
                '~DESCRIPTION'      => '~baz',
                'PROPERTY_VALUE_ID' => 'bar_id',
            ],
        ]);

        TestElement::$bxObject = $bxObject;
        $element = new TestElement(1);

        $expected = [
            'ID'         => 1,
            'NAME'       => 'John Doe',
            'PROPERTIES' => [
                'FOO' => [
                    'VALUE'             => 'bar',
                    '~VALUE'            => '~bar',
                    'DESCRIPTION'       => 'baz',
                    '~DESCRIPTION'      => '~baz',
                    'PROPERTY_VALUE_ID' => 'bar_id',
                ],
            ],
            'PROPERTY_FOO_VALUE'        => 'bar',
            '~PROPERTY_FOO_VALUE'       => '~bar',
            'PROPERTY_FOO_DESCRIPTION'  => 'baz',
            '~PROPERTY_FOO_DESCRIPTION' => '~baz',
            'PROPERTY_FOO_VALUE_ID'     => 'bar_id',
        ];

        $element->refresh();
        $this->assertEquals($expected, $element->fields);

        $element->fields = 'Jane Doe';

        $element->refresh();
        $this->assertSame($expected, $element->fields);
    }

    public function testSave()
    {
        $bxObject = m::mock('object');

        TestElement::$bxObject = $bxObject;
        $element = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestElement[get,onBeforeSave,onAfterSave,onBeforeUpdate,onAfterUpdate]', [1])
            ->shouldAllowMockingProtectedMethods();

        $element->shouldReceive('onBeforeSave')->times(3)->andReturn(true);
        $element->shouldReceive('onAfterSave')->times(3);
        $element->shouldReceive('onBeforeUpdate')->times(3)->andReturn(true);
        $element->shouldReceive('onAfterUpdate')->times(3);

        $fields = [
            'ID'                        => 1,
            'IBLOCK_ID'                 => 1,
            'NAME'                      => 'John Doe',
            'PROPERTY_FOO_VALUE'        => 'bar',
            '~PROPERTY_FOO_VALUE'       => '~bar',
            'PROPERTY_FOO_DESCRIPTION'  => 'baz',
            '~PROPERTY_FOO_DESCRIPTION' => '~baz',
            'PROPERTY_FOO_VALUE_ID'     => 'bar_id',
        ];
        $element->shouldReceive('get')->andReturn($fields);
        $element->fields = $fields;

        // 1
        $bxObject->shouldReceive('update')->with(1, ['NAME' => 'John Doe'])->once()->andReturn(true);
        $this->assertTrue($element->save(['NAME']));

        // 2
        $bxObject->shouldReceive('setPropertyValues')
            ->with(1, TestElement::iblockId(), ['FOO' => 'bar'])
            ->once()
            ->andReturn(true);

        $bxObject->shouldReceive('update')->with(1, ['NAME' => 'John Doe'])->once()->andReturn(true);
        $this->assertTrue($element->save());

        // 3
        $bxObject->shouldReceive('setPropertyValuesEx')
            ->with(1, TestElement::iblockId(), ['FOO' => 'bar'])
            ->once()
            ->andReturn(true);
        $this->assertTrue($element->save(['PROPERTY_FOO_VALUE']));
    }

    public function testUpdate()
    {
        $element = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestElement[save]', [1]);
        $element->shouldReceive('save')->with(['NAME', 'PROPERTY_FOO_VALUE', 'PROPERTY_FOO2_VALUE'])->andReturn(true);

        $this->assertTrue($element->update(['NAME' => 'John Doe', 'PROPERTY_FOO_VALUE' => 'bar', 'PROPERTY_FOO2_VALUE' => 'baz']));
        $this->assertSame('John Doe', $element->fields['NAME']);
        $this->assertSame('bar', $element->fields['PROPERTY_FOO_VALUE']);
        $this->assertSame('baz', $element->fields['PROPERTY_FOO2_VALUE']);
    }

    public function testCreate()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('add')->with(['NAME' => 'John Doe'])->once()->andReturn(2);

        TestElement::$bxObject = $bxObject;

        $newElement = TestElement::create(['NAME' => 'John Doe']);

        $this->assertSame(2, $newElement->id);
        $this->assertSame(['NAME' => 'John Doe', 'ID' => 2], $newElement->fields);
    }

    public function testGetList()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->with(['SORT' => 'ASC'], ['ACTIVE' => 'Y', 'IBLOCK_ID' => 1], false, false, ['ID', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $bxObject->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $bxObject->shouldReceive('getFields')->andReturn(['ID' => 1], ['ID' => 2]);

        TestElement::$bxObject = $bxObject;
        $elements = TestElement::getlist([
            'select'       => ['ID', 'IBLOCK_ID'],
            'filter'       => ['ACTIVE' => 'Y'],
        ]);

        $expected = [
            ['ID' => 1],
            ['ID' => 2],
        ];
        foreach ($elements as $k => $item) {
            $this->assertSame($expected[$k], $item->fields);
        }
    }

    public function testCount()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->with(false, ['ACTIVE' => 'Y', 'IBLOCK_ID' => 1], [])->once()->andReturn(2);

        TestElement::$bxObject = $bxObject;

        $this->assertEquals(2, TestElement::count(['ACTIVE' => 'Y', 'IBLOCK_ID' => 1]));

        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->with(false, ['IBLOCK_ID' => 1], [])->once()->andReturn(3);

        TestElement::$bxObject = $bxObject;

        $this->assertSame(3, TestElement::count());
    }

    public function testToArray()
    {
        $element = new TestElement(1, ['ID' => 1, 'NAME' => 'John Doe']);

        $this->assertSame(['ID' => 1, 'NAME' => 'John Doe', 'ACCESSOR_THREE' => []], $element->toArray());
    }

    public function testToJson()
    {
        $element = new TestElement(1, ['ID' => 1, 'NAME' => 'John Doe']);

        $this->assertSame(json_encode(['ID' => 1, 'NAME' => 'John Doe', 'ACCESSOR_THREE' => []]), $element->toJson());
    }

    public function testFill()
    {
        $element = new TestElement(1);
        $element->fill(['ID' => 2, 'NAME' => 'John Doe']);

        $this->assertSame(2, $element->id);
        $this->assertSame(['ID' => 2, 'NAME' => 'John Doe'], $element->getFields());
        $this->assertSame(['ID' => 2, 'NAME' => 'John Doe'], $element->fields);

        $element = new TestElement(1);
        $fields = [
            'ID'              => 2,
            'NAME'            => 'John Doe',
            'PROPERTY_VALUES' => [
                'GUID' => 'foo',
            ],
        ];
        $element->fill($fields);

        $this->assertSame(2, $element->id);
        $this->assertSame($fields, $element->get());
        $this->assertSame($fields, $element->fields);
    }

    public function testArrayAccess()
    {
        $element = new TestElement(1);
        $element->fill(['ID' => 2, 'NAME' => 'John Doe', 'GROUP_ID' => [1, 2]]);
        $values = [];
        foreach ($element as $value) {
            $values[] = $value;
        }

        $this->assertSame(2, $element['ID']);
        $this->assertSame('John Doe', $element['NAME']);
        $this->assertSame([1, 2], $element['GROUP_ID']);
        $this->assertTrue(in_array(1, $element['GROUP_ID']));
        $this->assertTrue(!empty($element['GROUP_ID'][0]));
        $this->assertTrue(empty($element['GROUP_ID'][2]));
        $this->assertSame([2, 'John Doe', [1, 2]], $values);
    }

    public function testAccessors()
    {
        $element = new TestElement(1);
        $element->fill(['ID' => 2, 'NAME' => 'John', 'ACCESSOR_ONE' => 'foo']);

        $this->assertSame('!foo!', $element['ACCESSOR_ONE']);
        $this->assertTrue(isset($element['ACCESSOR_ONE']));
        $this->assertTrue(!empty($element['ACCESSOR_ONE']));
        $this->assertSame('2:John', $element['ACCESSOR_TWO']);
        $this->assertTrue(isset($element['ACCESSOR_TWO']));
        $this->assertTrue(!empty($element['ACCESSOR_TWO']));
        $this->assertSame([], $element['ACCESSOR_THREE']);
        $this->assertTrue(isset($element['ACCESSOR_THREE']));
        $this->assertFalse(!empty($element['ACCESSOR_THREE']));
    }
}
