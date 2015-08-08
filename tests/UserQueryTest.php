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
     * @param $object
     *
     * @return UserQuery
     */
    protected function createQuery($object)
    {
        TestUser::$object = m::mock('object');
        return new UserQuery($object, 'Arrilot\Tests\BitrixModels\Stubs\TestUser');
    }

    public function testCount()
    {
        $object = m::mock('object');
        $object->shouldReceive('getList')->with("ID", "ASC", [],[
            'NAV_PARAMS' => [
                "nTopCount" => 0
            ]
        ])->once()->andReturn(m::self());
        $object->NavRecordCount = 6;

        $query = $this->createQuery($object);
        $count = $query->count();

        $this->assertSame(6, $count);


        $object = m::mock('object');
        $object->shouldReceive('getList')->with("ID", "ASC", ['ACTIVE'=>'Y'],[
            'NAV_PARAMS' => [
                "nTopCount" => 0
            ]
        ])->once()->andReturn(m::self());
        $object->NavRecordCount = 3;

        $query = $this->createQuery($object);
        $count = $query->filter(['ACTIVE' => 'Y'])->count();

        $this->assertSame(3, $count);
    }

    public function testGetListWithScopes()
    {
        $object = m::mock('object');
        TestUser::$object = $object;
        $object->shouldReceive('getList')->with(
            ["SORT" => "ASC"],
            false,
            ['NAME'=> 'John', 'ACTIVE' => 'Y', 'GROUPS_ID'=>1],
            [
                'SELECT'=>false,
                'NAV_PARAMS'=>false,
                'FIELDS'=>['ID', 'NAME']
            ]
        )->once()->andReturn(m::self());
        $object->shouldReceive('fetch')->andReturn(['ID' => 1, 'NAME' =>'foo'], ['ID' => 2, 'NAME' =>'bar'], false);

        $query = $this->createQuery($object);
        $items = $query->sort(["SORT" => "ASC"])->filter(['NAME'=>'John'])->active()->fromGroup(1)->select('ID', 'NAME')->getList();

        $expected = [
            1 => ['ID' => 1, 'NAME' =>'foo'],
            2 => ['ID' => 2, 'NAME' =>'bar'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->toArray());
        }
    }
}
