<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\Tests\BitrixModels\Stubs\TestElement;

use Mockery as m;

class ElementsModelTest extends TestCase
{
    public function tearDown()
    {
        TestElement::destroyObject();
        m::close();
    }

    public function testInitialization()
    {
        TestElement::$object = m::mock('object');

        $element = new TestElement(1);
        $this->assertEquals(1, $element->id);

        $fields = [
            'NAME' => 'John',
            'LAST_NAME' => 'Doe',
        ];
        $element = new TestElement(1, $fields);
        $this->assertEquals(1, $element->id);
        $this->assertEquals($fields, $element->fields);
    }

    public function testDelete()
    {
        $object = m::mock('object');
        $object->shouldReceive('delete')->once()->andReturn(true);

        TestElement::$object = $object;
        $element = new TestElement(1);

        $this->assertTrue($element->delete());
    }

    public function testActivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'Y'])->once()->andReturn(true);

        TestElement::$object = $object;
        $element = new TestElement(1);

        $this->assertTrue($element->activate());
    }

    public function testDeactivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'N'])->once()->andReturn(true);

        TestElement::$object = $object;
        $element = new TestElement(1);

        $this->assertTrue($element->deactivate());
    }

    public function testGet()
    {
        $object = m::mock('object');
        $object->shouldReceive('getByID')->with(1)->once()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->once()->andReturn(m::self());
        $object->shouldReceive('getFields')->once()->andReturn([
            'NAME' => 'John Doe'
        ]);
        $object->shouldReceive('getProperties')->once()->andReturn([
            'FOO_PROPERTY' => [
                'VALUE' => 'bar',
                'DESCRIPTION' => 'baz',
            ]
        ]);

        TestElement::$object = $object;
        $element = new TestElement(1);

        $expected = [
            'NAME' => 'John Doe',
            'PROPERTIES' => [
                'FOO_PROPERTY' => [
                    'VALUE' => 'bar',
                    'DESCRIPTION' => 'baz',
                ],
            ],
            'PROPERTY_VALUES' => [
                'FOO_PROPERTY' => 'bar',
            ],
        ];

        $this->assertEquals($expected, $element->get());
        $this->assertEquals($expected, $element->fields);

        // second call to make sure we do not query database twice.
        $this->assertEquals($expected, $element->get());
        $this->assertEquals($expected, $element->fields);
    }

    public function testRefresh()
    {
        $object = m::mock('object');
        $object->shouldReceive('getByID')->with(1)->twice()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->twice()->andReturn(m::self());
        $object->shouldReceive('getFields')->twice()->andReturn([
            'NAME' => 'John Doe'
        ]);
        $object->shouldReceive('getProperties')->twice()->andReturn([
            'FOO_PROPERTY' => [
                'VALUE' => 'bar',
                'DESCRIPTION' => 'baz',
            ]
        ]);

        TestElement::$object = $object;
        $element = new TestElement(1);

        $expected = [
            'NAME' => 'John Doe',
            'PROPERTIES' => [
                'FOO_PROPERTY' => [
                    'VALUE' => 'bar',
                    'DESCRIPTION' => 'baz',
                ],
            ],
            'PROPERTY_VALUES' => [
                'FOO_PROPERTY' => 'bar',
            ],
        ];

        $element->refresh();
        $this->assertEquals($expected, $element->fields);

        $element->fields = 'Jane Doe';

        $element->refresh();
        $this->assertEquals($expected, $element->fields);
    }

    public function testSave()
    {
        $object = m::mock('object');

        TestElement::$object = $object;
        $element = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestElement[get]',[1]);
        $fields = [
            'ID' => 1,
            'IBLOCK_ID' => 1,
            'NAME'            => 'John Doe',
            'PROPERTIES'      => [
                'FOO_PROPERTY' => [
                    'VALUE'       => 'bar',
                    'DESCRIPTION' => 'baz',
                ],
            ],
            'PROPERTY_VALUES' => [
                'FOO_PROPERTY' => 'bar',
            ],
        ];
        $element->shouldReceive('get')->andReturn($fields);
        $element->fields = $fields;

        $expected1 = [
            'NAME' => 'John Doe',
            'PROPERTY_VALUES' => [
                'FOO_PROPERTY' => 'bar',
            ],
        ];
        $expected2 = [
            'NAME' => 'John Doe',
        ];
        $object->shouldReceive('update')->with(1, $expected1)->once()->andReturn(true);
        $object->shouldReceive('update')->with(1, $expected2)->once()->andReturn(true);

        $this->assertTrue($element->save());
        $this->assertTrue($element->save(['NAME']));
    }

    public function testUpdate()
    {
        TestElement::$object = m::mock('object');
        $element = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestElement[save]',[1]);
        $element->shouldReceive('save')->with(['NAME'])->andReturn(true);

        $this->assertTrue($element->update(['NAME'=>'John Doe']));
        $this->assertEquals('John Doe', $element->fields['NAME']);
    }

    public function testCreate()
    {
        $object = m::mock('object');
        $object->shouldReceive('add')->with(['NAME' => 'John Doe'])->once()->andReturn(2);

        TestElement::$object = $object;

        $newElement = TestElement::create(['NAME' => 'John Doe']);

        $this->assertEquals(2, $newElement->id);
        $this->assertEquals(['NAME' => 'John Doe', 'ID' => 2], $newElement->fields);
    }

    public function testGetList()
    {
        $object = m::mock('object');
        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'Y', 'IBLOCK_ID' => 1], false, false, ['ID', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $object->shouldReceive('getFields')->andReturn(['ID' => 1], ['ID' => 2]);

        TestElement::$object = $object;
        $elements = TestElement::getlist([
            'select' => ['ID', 'IBLOCK_ID'],
            'filter' => ['ACTIVE' => 'Y'],
        ]);

        $expected = [
            1 => ['ID' => 1],
            2 => ['ID' => 2],
        ];

        $this->assertEquals($expected, $elements);
    }

    public function testCount()
    {
        $object = m::mock('object');
        $object->shouldReceive('getList')->with(false, ['ACTIVE' => 'Y'], [])->once()->andReturn(2);

        TestElement::$object = $object;

        $this->assertEquals(2, TestElement::count(['ACTIVE' => 'Y']));

        $object = m::mock('object');
        $object->shouldReceive('getList')->with(false, [], [])->once()->andReturn(3);

        TestElement::$object = $object;

        $this->assertEquals(3, TestElement::count());
    }
}
