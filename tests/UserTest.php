<?php

namespace Arrilot\BitrixModels\Test;

use Arrilot\BitrixModels\Test\Stubs\BxUserStub;
use Arrilot\BitrixModels\Test\Models\User;

use Mockery as m;
use Mockery\MockInterface;

class UserTest extends TestCase
{
    public function setUp()
    {
        $GLOBALS['USER'] = new BxUserStub;
    }

    public function tearDown()
    {
        User::destroyObject();
        m::close();
    }

    public function testInitialization()
    {
        $object = m::mock('object');

        User::$object = $object;
        $user = new User(2, ['NAME' => 'John Doe']);

        $this->assertEquals(2, $user->id);
        $this->assertEquals(['NAME' => 'John Doe'], $user->fields);
    }

    public function testInitializationWithCurrent()
    {
        global $USER;

        User::$object = m::mock('object');

        $user = User::current();

        $this->assertEquals($USER->getId(), $user->id);
        $this->assertEquals(null, $user->fields);
    }

    public function testDelete()
    {
        $object = m::mock('object');
        $object->shouldReceive('delete')->once()->andReturn(true);

        User::$object = $object;
        $user = new User(1);

        $this->assertTrue($user->delete());
    }

    public function testActivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'Y'])->once()->andReturn(true);

        User::$object = $object;
        $user = new User(1);

        $this->assertTrue($user->activate());
    }

    public function testDeactivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'N'])->once()->andReturn(true);

        User::$object = $object;
        $user = new User(1);

        $this->assertTrue($user->deactivate());
    }
//
//    public function testGet()
//    {
//        $object = m::mock('object');
//        $object->shouldReceive('getByID')->with(1)->once()->andReturn(m::self());
//        $object->shouldReceive('getNextUser')->once()->andReturn(m::self());
//        $object->shouldReceive('getFields')->once()->andReturn([
//            'NAME' => 'John Doe'
//        ]);
//        $object->shouldReceive('getProperties')->once()->andReturn([
//            'FOO_PROPERTY' => [
//                'VALUE' => 'bar',
//                'DESCRIPTION' => 'baz',
//            ]
//        ]);
//
//        User::$object = $object;
//        $user = new User(1);
//
//        $expected = [
//            'NAME' => 'John Doe',
//            'PROPERTIES' => [
//                'FOO_PROPERTY' => [
//                    'VALUE' => 'bar',
//                    'DESCRIPTION' => 'baz',
//                ],
//            ],
//            'PROPERTY_VALUES' => [
//                'FOO_PROPERTY' => 'bar',
//            ],
//        ];
//
//        $this->assertEquals($expected, $user->get());
//        $this->assertEquals($expected, $user->fields);
//
//        // second call to make sure we do not query database twice.
//        $this->assertEquals($expected, $user->get());
//        $this->assertEquals($expected, $user->fields);
//    }
//
//    public function testRefresh()
//    {
//        $object = m::mock('object');
//        $object->shouldReceive('getByID')->with(1)->twice()->andReturn(m::self());
//        $object->shouldReceive('getNextUser')->twice()->andReturn(m::self());
//        $object->shouldReceive('getFields')->twice()->andReturn([
//            'NAME' => 'John Doe'
//        ]);
//        $object->shouldReceive('getProperties')->twice()->andReturn([
//            'FOO_PROPERTY' => [
//                'VALUE' => 'bar',
//                'DESCRIPTION' => 'baz',
//            ]
//        ]);
//
//        User::$object = $object;
//        $user = new User(1);
//
//        $expected = [
//            'NAME' => 'John Doe',
//            'PROPERTIES' => [
//                'FOO_PROPERTY' => [
//                    'VALUE' => 'bar',
//                    'DESCRIPTION' => 'baz',
//                ],
//            ],
//            'PROPERTY_VALUES' => [
//                'FOO_PROPERTY' => 'bar',
//            ],
//        ];
//
//        $user->refresh();
//        $this->assertEquals($expected, $user->fields);
//
//        $user->fields = 'Jane Doe';
//
//        $user->refresh();
//        $this->assertEquals($expected, $user->fields);
//    }
//
//    public function testSave()
//    {
//        $object = m::mock('object');
//
//        User::$object = $object;
//        $user = m::mock('Arrilot\BitrixModels\User[get]',[1]);
//        $fields = [
//            'ID' => 1,
//            'IBLOCK_ID' => 1,
//            'NAME'            => 'John Doe',
//            'PROPERTIES'      => [
//                'FOO_PROPERTY' => [
//                    'VALUE'       => 'bar',
//                    'DESCRIPTION' => 'baz',
//                ],
//            ],
//            'PROPERTY_VALUES' => [
//                'FOO_PROPERTY' => 'bar',
//            ],
//        ];
//        $user->shouldReceive('get')->andReturn($fields);
//        $user->fields = $fields;
//
//        $expected1 = [
//            'NAME' => 'John Doe',
//            'PROPERTY_VALUES' => [
//                'FOO_PROPERTY' => 'bar',
//            ],
//        ];
//        $expected2 = [
//            'NAME' => 'John Doe',
//        ];
//        $object->shouldReceive('update')->with(1, $expected1)->once()->andReturn(true);
//        $object->shouldReceive('update')->with(1, $expected2)->once()->andReturn(true);
//
//        $this->assertTrue($user->save());
//        $this->assertTrue($user->save(['NAME']));
//    }
//
//    public function testUpdate()
//    {
//        User::$object = m::mock('object');
//        $user = m::mock('Arrilot\BitrixModels\User[save]',[1]);
//        $user->shouldReceive('save')->with(['NAME'])->andReturn(true);
//
//        $this->assertTrue($user->update(['NAME'=>'John Doe']));
//        $this->assertEquals('John Doe', $user->fields['NAME']);
//    }
//
    public function testCreate()
    {
        $object = m::mock('object');
        $object->shouldReceive('add')->with(['NAME' => 'John Doe'])->once()->andReturn(3);

        User::$object = $object;

        $newUser = User::create(['NAME' => 'John Doe']);

        $this->assertEquals(3, $newUser->id);
        $this->assertEquals([
            'NAME' => 'John Doe',
            'ID' => 3,
        ], $newUser->fields);
    }
//
//    public function testGetList()
//    {
//        $object = m::mock('object');
//        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'Y', 'IBLOCK_ID' => 1], false, false, ['ID', 'IBLOCK_ID'])->once()->andReturn(m::self());
//        $object->shouldReceive('getNextUser')->andReturn(m::self(), m::self(), false);
//        $object->shouldReceive('getFields')->andReturn(['ID' => 1], ['ID' => 2]);
//
//        User::$object = $object;
//        $users = User::getlist([
//            'select' => ['ID', 'IBLOCK_ID'],
//            'filter' => ['ACTIVE' => 'Y'],
//        ]);
//
//        $expected = [
//            1 => ['ID' => 1],
//            2 => ['ID' => 2],
//        ];
//
//        $this->assertEquals($expected, $users);
//    }
//
    public function testCount()
    {
        $object = m::mock('object');
        $object->shouldReceive('getList')->with("ID", "ASC", ['ACTIVE'=>'Y'],[
            'NAV_PARAMS' => [
                "nTopCount" => 0
            ]
        ])->once()->andReturn(m::self());
        $object->NavRecordCount = 2;

        User::$object = $object;

        $this->assertEquals(2, User::count(['ACTIVE' => 'Y']));

        $object = m::mock('object');
        $object->shouldReceive('getList')->with("ID", "ASC", [],[
            'NAV_PARAMS' => [
                "nTopCount" => 0
            ]
        ])->once()->andReturn(m::self());
        $object->NavRecordCount = 3;

        User::$object = $object;

        $this->assertEquals(3, User::count());
    }

    protected function mockGetUserGroups(MockInterface $object)
    {
        $object->shouldReceive('getUserGroup')->andReturn([1,2]);

        return [1,2];
    }
}
