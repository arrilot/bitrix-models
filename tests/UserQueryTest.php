<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\BitrixModels\Queries\UserQuery;
use Arrilot\Tests\BitrixModels\Stubs\TestUser;
use Mockery as m;

class UserQueryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * Create testing object.
     *
     * @param $bxObject
     *
     * @return UserQuery
     */
    protected function createQuery($bxObject)
    {
        TestUser::$bxObject = m::mock('obj');

        return new UserQuery($bxObject, 'Arrilot\Tests\BitrixModels\Stubs\TestUser');
    }

    public function testCount()
    {
        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('getList')->with('ID', 'ASC', [], [
            'NAV_PARAMS' => [
                'nTopCount' => 0,
            ],
        ])->once()->andReturn(m::self());
        $bxObject->NavRecordCount = 6;

        $query = $this->createQuery($bxObject);
        $count = $query->count();

        $this->assertSame(6, $count);

        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('getList')->with('ID', 'ASC', ['ACTIVE' => 'Y'], [
            'NAV_PARAMS' => [
                'nTopCount' => 0,
            ],
        ])->once()->andReturn(m::self());
        $bxObject->NavRecordCount = 3;

        $query = $this->createQuery($bxObject);
        $count = $query->filter(['ACTIVE' => 'Y'])->count();

        $this->assertSame(3, $count);
    }

    public function testGetListWithScopes()
    {
        $bxObject = m::mock('obj');
        TestUser::$bxObject = $bxObject;
        $bxObject->shouldReceive('getList')->with(
            ['SORT' => 'ASC'],
            false,
            ['NAME' => 'John', 'ACTIVE' => 'Y'],
            [
                'SELECT'     => false,
                'NAV_PARAMS' => false,
                'FIELDS'     => ['ID', 'NAME'],
            ]
        )->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->andReturn(['ID' => 1, 'NAME' => 'foo'], ['ID' => 2, 'NAME' => 'bar'], false);

        $query = $this->createQuery($bxObject);
        $items = $query->sort(['SORT' => 'ASC'])->filter(['NAME' => 'John'])->active()->select('ID', 'NAME')->getList();

        $expected = [
            1 => ['ID' => 1, 'NAME' => 'foo'],
            2 => ['ID' => 2, 'NAME' => 'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->toArray());
        }
    }

    public function testGetByLogin()
    {
        $bxObject = m::mock('obj');
        TestUser::$bxObject = $bxObject;
        $bxObject->shouldReceive('getList')->with(
            ['SORT' => 'ASC'],
            false,
            ['LOGIN_EQUAL_EXACT' => 'JohnDoe' ],
            [
                'SELECT'     => false,
                'NAV_PARAMS' => ['nPageSize' => 1 ],
                'FIELDS'     => ['ID', 'NAME'],
            ]
        )->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->times(2)->andReturn(['ID' => 1, 'NAME' => 'foo'], false);

        $query = $this->createQuery($bxObject);
        $item = $query->sort(['SORT' => 'ASC'])->select('ID', 'NAME')->getByLogin('JohnDoe');

        $this->assertSame(['ID' => 1, 'NAME' => 'foo'], $item->toArray());
    }

    public function testGetByEmail()
    {
        $bxObject = m::mock('obj');
        TestUser::$bxObject = $bxObject;
        $bxObject->shouldReceive('getList')->with(
            ['SORT' => 'ASC'],
            false,
            ['EMAIL' => 'john@example.com' ],
            [
                'SELECT'     => false,
                'NAV_PARAMS' => ['nPageSize' => 1 ],
                'FIELDS'     => ['ID', 'NAME'],
            ]
        )->once()->andReturn(m::self());
        $bxObject->shouldReceive('Fetch')->times(2)->andReturn(['ID' => 1, 'NAME' => 'foo'], false);

        $query = $this->createQuery($bxObject);
        $item = $query->sort(['SORT' => 'ASC'])->select('ID', 'NAME')->getByEmail('john@example.com');

        $this->assertSame(['ID' => 1, 'NAME' => 'foo'], $item->toArray());
    }
}
