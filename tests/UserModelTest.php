<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\Tests\BitrixModels\Stubs\BxUserWithAuth;
use Arrilot\Tests\BitrixModels\Stubs\BxUserWithoutAuth;
use Arrilot\Tests\BitrixModels\Stubs\TestUser;

use Mockery as m;

class TestUserModelTest extends TestCase
{
    public function tearDown()
    {
        TestUser::destroyObject();
        m::close();
    }

    public function testInitialization()
    {
        TestUser::$object = m::mock('object');
        $user = new TestUser(2, ['NAME' => 'John Doe']);

        $this->assertSame(2, $user->id);
        $this->assertSame(['NAME' => 'John Doe'], $user->fields);

        $user = new TestUser(2, ['NAME' => 'John Doe', 'GROUP_ID' => [1, 2]]);

        $this->assertSame(2, $user->id);
        $this->assertSame(['NAME' => 'John Doe', 'GROUP_ID' => [1, 2]], $user->get());
        $this->assertSame([1, 2], $user->getGroups());
    }

    public function testInitializationWithCurrent()
    {
        $GLOBALS['USER'] = new BxUserWithAuth;
        global $USER;

        TestUser::$object = m::mock('object');

        $user = TestUser::current();
        $this->assertSame($USER->getId(), $user->id);
        $this->assertSame(null, $user->fields);

        $user = TestUser::current(['NAME' => 'John Doe', 'GROUP_ID' => [1,2]]);
        $this->assertSame($USER->getId(), $user->id);
        $this->assertSame(['NAME' => 'John Doe', 'GROUP_ID' => [1,2]], $user->fields);
        $this->assertSame(['NAME' => 'John Doe', 'GROUP_ID' => [1,2]], $user->get());
        $this->assertSame([1,2], $user->getGroups());
    }

    public function testInitializationWithCurrentNotAuth()
    {
        $GLOBALS['USER'] = new BxUserWithoutAuth;

        TestUser::$object = m::mock('object');

        $user = TestUser::current();
        $this->assertSame(null, $user->id);
        $this->assertSame(null, $user->fields);
    }

    public function testHasRoleWithId()
    {
        $GLOBALS['USER'] = new BxUserWithoutAuth;
        TestUser::$object = m::mock('object');

        $user = TestUser::current();
        $this->assertFalse($user->hasRoleWithId(1));

        $user = new TestUser(2, ['NAME' => 'John Doe', 'GROUP_ID' => [1, 2]]);

        $this->assertTrue($user->hasRoleWithId(1));
        $this->assertTrue($user->hasRoleWithId(2));
        $this->assertFalse($user->hasRoleWithId(3));
    }

    public function testIsCurrent()
    {
        $GLOBALS['USER'] = new BxUserWithAuth;
        TestUser::$object = m::mock('object');

        $user = TestUser::current();
        $this->assertTrue($user->isCurrent());

        $user = new TestUser(1);
        $this->assertTrue($user->isCurrent());

        $user = new TestUser(263);
        $this->assertFalse($user->isCurrent());
    }

    public function testIsAuthorized()
    {
        TestUser::$object = m::mock('object');

        $GLOBALS['USER'] = new BxUserWithAuth;
        $user = TestUser::current();
        $this->assertTrue($user->isAuthorized());

        $GLOBALS['USER'] = new BxUserWithoutAuth;
        $user = TestUser::current();
        $this->assertFalse($user->isAuthorized());
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

    public function testCreate()
    {
        $object = m::mock('object');
        $object->shouldReceive('add')->with(['NAME' => 'John Doe'])->once()->andReturn(3);

        TestUser::$object = $object;

        $newTestUser = TestUser::create(['NAME' => 'John Doe']);

        $this->assertSame(3, $newTestUser->id);
        $this->assertSame([
            'NAME' => 'John Doe',
            'ID' => 3,
        ], $newTestUser->fields);
    }

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

        $this->assertSame(2, TestUser::count(['ACTIVE' => 'Y']));

        $object = m::mock('object');
        $object->shouldReceive('getList')->with("ID", "ASC", [],[
            'NAV_PARAMS' => [
                "nTopCount" => 0
            ]
        ])->once()->andReturn(m::self());
        $object->NavRecordCount = 3;

        TestUser::$object = $object;

        $this->assertSame(3, TestUser::count());
    }

    public function testFill()
    {
        TestUser::$object = m::mock('object');
        $user = new TestUser(1);

        $fields = ['ID' => 2, 'NAME' => 'John Doe','GROUP_ID' => [1,2]];
        $user->fill($fields);

        $this->assertSame(2, $user->id);
        $this->assertSame($fields, $user->fields);
        $this->assertSame($fields, $user->get());

        $object = m::mock('object');
        $object->shouldReceive('getUserGroup')->once()->andReturn([1]);
        TestUser::$object = $object;
        $user = new TestUser(1);

        $fields = ['ID' => 2, 'NAME' => 'John Doe'];
        $user->fill($fields);

        $this->assertSame(2, $user->id);
        $this->assertSame($fields, $user->fields);
        $this->assertSame($fields + ['GROUP_ID' => [1]], $user->get());
    }

    public function testFillGroups()
    {
        TestUser::$object = m::mock('object');
        $user = new TestUser(1);

        $user->fillGroups([1,2]);

        $this->assertSame(1, $user->id);
        $this->assertSame(['GROUP_ID' => [1,2]], $user->fields);
        $this->assertSame([1,2], $user->getGroups());
    }
}
