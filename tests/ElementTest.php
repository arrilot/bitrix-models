<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\Tests\BitrixModels\Models\Element;

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
        Element::$object = m::mock('object');

        $Element = new Element(1);
        $this->assertEquals(1, $Element->id);

        $fields = [
            'NAME' => 'John',
            'LAST_NAME' => 'Doe',
        ];
        $Element = new Element(1, $fields);
        $this->assertEquals(1, $Element->id);
        $this->assertEquals($fields, $Element->fields);
    }

    public function testDelete()
    {
        $object = m::mock('object');
        $object->shouldReceive('delete')->once()->andReturn(true);

        Element::$object = $object;
        $Element = new Element(1);

        $this->assertTrue($Element->delete());
    }

    public function testActivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'Y'])->once()->andReturn(true);

        Element::$object = $object;
        $Element = new Element(1);

        $this->assertTrue($Element->activate());
    }

    public function testDeactivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'N'])->once()->andReturn(true);

        Element::$object = $object;
        $Element = new Element(1);

        $this->assertTrue($Element->deactivate());
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

        Element::$object = $object;
        $Element = new Element(1);

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

        $this->assertEquals($expected, $Element->get());
        $this->assertEquals($expected, $Element->fields);

        // second call to make sure we do not query database twice.
        $this->assertEquals($expected, $Element->get());
        $this->assertEquals($expected, $Element->fields);
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

        Element::$object = $object;
        $Element = new Element(1);

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

        $Element->refresh();
        $this->assertEquals($expected, $Element->fields);

        $Element->fields = 'Jane Doe';

        $Element->refresh();
        $this->assertEquals($expected, $Element->fields);
    }

    public function testSave()
    {
        $object = m::mock('object');

        Element::$object = $object;
        $Element = m::mock('Arrilot\BitrixModels\Element[get]',[1]);
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
        $Element->shouldReceive('get')->andReturn($fields);
        $Element->fields = $fields;

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

        $this->assertTrue($Element->save());
        $this->assertTrue($Element->save(['NAME']));
    }

    public function testUpdate()
    {
        Element::$object = m::mock('object');
        $Element = m::mock('Arrilot\BitrixModels\Element[save]',[1]);
        $Element->shouldReceive('save')->with(['NAME'])->andReturn(true);

        $this->assertTrue($Element->update(['NAME'=>'John Doe']));
        $this->assertEquals('John Doe', $Element->fields['NAME']);
    }

    public function testCreate()
    {
        $object = m::mock('object');
        $object->shouldReceive('add')->with(['NAME' => 'John Doe'])->once()->andReturn(2);

        Element::$object = $object;

        $newElement = Element::create(['NAME' => 'John Doe']);

        $this->assertEquals(2, $newElement->id);
        $this->assertEquals(['NAME' => 'John Doe', 'ID' => 2], $newElement->fields);
    }

    public function testGetList()
    {
        $object = m::mock('object');
        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'Y', 'IBLOCK_ID' => 1], false, false, ['ID', 'IBLOCK_ID'])->once()->andReturn(m::self());
        $object->shouldReceive('getNextElement')->andReturn(m::self(), m::self(), false);
        $object->shouldReceive('getFields')->andReturn(['ID' => 1], ['ID' => 2]);

        Element::$object = $object;
        $elements = Element::getlist([
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

        Element::$object = $object;

        $this->assertEquals(2, Element::count(['ACTIVE' => 'Y']));

        $object = m::mock('object');
        $object->shouldReceive('getList')->with(false, [], [])->once()->andReturn(3);

        Element::$object = $object;

        $this->assertEquals(3, Element::count());
    }
}
