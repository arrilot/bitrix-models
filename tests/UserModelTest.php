<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\Tests\BitrixModels\Stubs\BxUser;
use Arrilot\Tests\BitrixModels\Stubs\TestUser;

use Mockery as m;
use Mockery\MockInterface;

class TestUserModelTest extends TestCase
{
    public function setUp()
    {
        $GLOBALS['USER'] = new BxUser;
    }

    public function tearDown()
    {
        TestUser::destroyObject();
        m::close();
    }

    public function testInitialization()
    {
        $object = m::mock('object');

        TestUser::$object = $object;
        $user = new TestUser(2, ['NAME' => 'John Doe']);

        $this->assertEquals(2, $user->id);
        $this->assertEquals(['NAME' => 'John Doe'], $user->fields);
    }

    public function testInitializationWithCurrent()
    {
        global $USER;

        TestUser::$object = m::mock('object');

        $user = TestUser::current();

        $this->assertEquals($USER->getId(), $user->id);
        $this->assertEquals(null, $user->fields);
    }

    public function testDelete()
    {
        $object = m::mock('object');
        $object->shouldReceive('delete')->once()->andReturn(true);

        TestUser::$object = $object;
        $user = new TestUser(1);

        $this->assertTrue($user->delete());
    }

    public function testActivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'Y'])->once()->andReturn(true);

        TestUser::$object = $object;
        $user = new TestUser(1);

        $this->assertTrue($user->activate());
    }

    public function testDeactivate()
    {
        $object = m::mock('object');
        $object->shouldReceive('update')->with(1, ['ACTIVE'=>'N'])->once()->andReturn(true);

        TestUser::$object = $object;
        $user = new TestUser(1);

        $this->assertTrue($user->deactivate());
    }
//
//    public function testGet()
//    {
//        $object = m::mock('object');
//        $object->shouldReceive('getByID')->with(1)->once()->andReturn(m::self());
//        $object->shouldReceive('getNextTestUser')->once()->andReturn(m::self());
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
//        TestUser::$object = $object;
//        $user = new TestUser(1);
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
//        $object->shouldReceive('getNextTestUser')->twice()->andReturn(m::self());
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
//        TestUser::$object = $object;
//        $user = new TestUser(1);
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
//        TestUser::$object = $object;
//        $user = m::mock('Arrilot\BitrixModels\TestUser[get]',[1]);
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
//        TestUser::$object = m::mock('object');
//        $user = m::mock('Arrilot\BitrixModels\TestUser[save]',[1]);
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

        TestUser::$object = $object;

        $newTestUser = TestUser::create(['NAME' => 'John Doe']);

        $this->assertEquals(3, $newTestUser->id);
        $this->assertEquals([
            'NAME' => 'John Doe',
            'ID' => 3,
        ], $newTestUser->fields);
    }
//
//    public function testGetList()
//    {
//        $object = m::mock('object');
//        $object->shouldReceive('getList')->with(["SORT" => "ASC"], ['ACTIVE' => 'Y', 'IBLOCK_ID' => 1], false, false, ['ID', 'IBLOCK_ID'])->once()->andReturn(m::self());
//        $object->shouldReceive('getNextTestUser')->andReturn(m::self(), m::self(), false);
//        $object->shouldReceive('getFields')->andReturn(['ID' => 1], ['ID' => 2]);
//
//        TestUser::$object = $object;
//        $users = TestUser::getlist([
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

        TestUser::$object = $object;

        $this->assertEquals(2, TestUser::count(['ACTIVE' => 'Y']));

        $object = m::mock('object');
        $object->shouldReceive('getList')->with("ID", "ASC", [],[
            'NAV_PARAMS' => [
                "nTopCount" => 0
            ]
        ])->once()->andReturn(m::self());
        $object->NavRecordCount = 3;

        TestUser::$object = $object;

        $this->assertEquals(3, TestUser::count());
    }

    protected function mockGetTestUserGroups(MockInterface $object)
    {
        $object->shouldReceive('getTestUserGroup')->andReturn([1,2]);

        return [1,2];
    }
}
