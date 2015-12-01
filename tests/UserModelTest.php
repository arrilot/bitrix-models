<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\Tests\BitrixModels\Stubs\BxUserWithAuth;
use Arrilot\Tests\BitrixModels\Stubs\BxUserWithoutAuth;
use Arrilot\Tests\BitrixModels\Stubs\TestUser;
use Mockery as m;

class UserModelTest extends TestCase
{
    public function setUp()
    {
        TestUser::$bxObject = m::mock('object');
    }

    public function tearDown()
    {
        TestUser::destroyObject();
        m::close();
    }

    public function testInitialization()
    {
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
        $GLOBALS['USER'] = new BxUserWithAuth();
        global $USER;

        $user = TestUser::current();
        $this->assertSame($USER->getId(), $user->id);
        $this->assertSame(null, $user->fields);

        $user = TestUser::current(['NAME' => 'John Doe', 'GROUP_ID' => [1, 2]]);
        $this->assertSame($USER->getId(), $user->id);
        $this->assertSame(['NAME' => 'John Doe', 'GROUP_ID' => [1, 2]], $user->fields);
        $this->assertSame(['NAME' => 'John Doe', 'GROUP_ID' => [1, 2]], $user->get());
        $this->assertSame([1, 2], $user->getGroups());
    }

    public function testInitializationWithCurrentNotAuth()
    {
        $GLOBALS['USER'] = new BxUserWithoutAuth();

        $user = TestUser::current();
        $this->assertSame(null, $user->id);
        $this->assertSame(null, $user->fields);
    }

    public function testHasRoleWithId()
    {
        $GLOBALS['USER'] = new BxUserWithoutAuth();

        $user = TestUser::current();
        $this->assertFalse($user->hasGroupWithId(1));

        $user = new TestUser(2, ['NAME' => 'John Doe', 'GROUP_ID' => [1, 2]]);

        $this->assertTrue($user->hasGroupWithId(1));
        $this->assertTrue($user->hasGroupWithId(2));
        $this->assertFalse($user->hasGroupWithId(3));
    }

    public function testIsCurrent()
    {
        $GLOBALS['USER'] = new BxUserWithAuth();

        $user = TestUser::current();
        $this->assertTrue($user->isCurrent());

        $user = new TestUser(1);
        $this->assertTrue($user->isCurrent());

        $user = new TestUser(263);
        $this->assertFalse($user->isCurrent());
    }

    public function testIsAuthorized()
    {
        $GLOBALS['USER'] = new BxUserWithAuth();
        $user = TestUser::current();
        $this->assertTrue($user->isAuthorized());

        $GLOBALS['USER'] = new BxUserWithoutAuth();
        $user = TestUser::current();
        $this->assertFalse($user->isAuthorized());
    }

    public function testDelete()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('delete')->once()->andReturn(true);

        TestUser::$bxObject = $bxObject;
        $user = new TestUser(1);

        $this->assertTrue($user->delete());
    }

    public function testActivate()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('update')->with(1, ['ACTIVE' => 'Y'])->once()->andReturn(true);

        TestUser::$bxObject = $bxObject;
        $user = new TestUser(1);

        $this->assertTrue($user->activate());
    }

    public function testDeactivate()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('update')->with(1, ['ACTIVE' => 'N'])->once()->andReturn(true);

        TestUser::$bxObject = $bxObject;
        $user = new TestUser(1);

        $this->assertTrue($user->deactivate());
    }

    public function testCreate()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('add')->with(['NAME' => 'John Doe'])->once()->andReturn(3);

        TestUser::$bxObject = $bxObject;

        $newTestUser = TestUser::create(['NAME' => 'John Doe']);

        $this->assertSame(3, $newTestUser->id);
        $this->assertSame([
            'NAME' => 'John Doe',
            'ID'   => 3,
        ], $newTestUser->fields);
    }

    public function testCount()
    {
        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->with('ID', 'ASC', ['ACTIVE' => 'Y'], [
            'NAV_PARAMS' => [
                'nTopCount' => 0,
            ],
        ])->once()->andReturn(m::self());
        $bxObject->NavRecordCount = 2;

        TestUser::$bxObject = $bxObject;

        $this->assertSame(2, TestUser::count(['ACTIVE' => 'Y']));

        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getList')->with('ID', 'ASC', [], [
            'NAV_PARAMS' => [
                'nTopCount' => 0,
            ],
        ])->once()->andReturn(m::self());
        $bxObject->NavRecordCount = 3;

        TestUser::$bxObject = $bxObject;

        $this->assertSame(3, TestUser::count());
    }

    public function testUpdate()
    {
        $user = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestUser[save]', [1]);
        $user->shouldReceive('save')->with(['NAME', 'UF_FOO'])->andReturn(true);

        $this->assertTrue($user->update(['NAME' => 'John', 'UF_FOO' => 'bar']));
        $this->assertSame('John', $user->fields['NAME']);
        $this->assertSame('bar', $user->fields['UF_FOO']);
    }

    public function testFill()
    {
        $user = new TestUser(1);

        $fields = ['ID' => 2, 'NAME' => 'John Doe', 'GROUP_ID' => [1, 2]];
        $user->fill($fields);

        $this->assertSame(2, $user->id);
        $this->assertSame($fields, $user->fields);
        $this->assertSame($fields, $user->get());

        $bxObject = m::mock('object');
        $bxObject->shouldReceive('getUserGroup')->once()->andReturn([1]);
        TestUser::$bxObject = $bxObject;
        $user = new TestUser(1);

        $fields = ['ID' => 2, 'NAME' => 'John Doe'];
        $user->fill($fields);

        $this->assertSame(2, $user->id);
        $this->assertSame($fields, $user->fields);
        $this->assertSame($fields + ['GROUP_ID' => [1]], $user->get());
    }

    public function testFillGroups()
    {
        $user = new TestUser(1);

        $user->fillGroups([1, 2]);

        $this->assertSame(1, $user->id);
        $this->assertSame(['GROUP_ID' => [1, 2]], $user->fields);
        $this->assertSame([1, 2], $user->getGroups());
    }
}
