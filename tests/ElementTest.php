<?php

namespace Arrilot\BitrixModels\Test;

use Arrilot\BitrixModels\Element;
use Mockery as m;

class ElementTest extends TestCase
{
    public function tearDown()
    {
        Element::destroyObject();
        m::close();
    }

    public function testInitialization()
    {
        $element = new Element(1, null, m::mock('object'));
        $this->assertEquals(1, $element->id);

        $fields = [
            'NAME' => 'John',
            'LAST_NAME' => 'Doe',
        ];
        $element = new Element(1, $fields, m::mock('object'));
        $this->assertEquals(1, $element->id);
        $this->assertEquals($fields, $element->fields);
    }

    public function testDelete()
    {
        $object = m::mock('object');
        $object->shouldReceive('delete')->once()->andReturn(true);

        $element = new Element(1, null, $object);

        $this->assertTrue($element->delete());
    }

    public function testActivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'Y'])->once()->andReturn(true);

        $element = new Element(1, null, $object);

        $this->assertTrue($element->activate());
    }

    public function testDeactivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'N'])->once()->andReturn(true);

        $element = new Element(1, null, $object);

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

        $element = new Element(1, null, $object);

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

        $element = new Element(1, null, $object);

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

        $element = m::mock('Arrilot\BitrixModels\Element[get]',[1, null, $object]);
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
        $object = m::mock('object');

        $element = m::mock('Arrilot\BitrixModels\Element[save]',[1, null, $object]);
        $element->shouldReceive('save')->with(['NAME'])->andReturn(true);

        $this->assertTrue($element->update(['NAME'=>'John Doe']));
        $this->assertEquals('John Doe', $element->fields['NAME']);
    }

    public function testCreate()
    {
        $object = m::mock('object');
        $object->shouldReceive('add')->with(['NAME' => 'John Doe'])->once()->andReturn(2);

        // create an instance to setup mock object instead of CIblockElement object
        $element = new Element(1, null , $object);

        $newElement = Element::create(['NAME' => 'John Doe']);

        $this->assertEquals(2, $newElement->id);
        $this->assertEquals(['NAME' => 'John Doe', 'ID' => 2], $newElement->fields);
    }
}
