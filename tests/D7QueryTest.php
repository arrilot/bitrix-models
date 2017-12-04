<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\BitrixModels\Queries\D7Query;
use Arrilot\Tests\BitrixModels\Stubs\TestUser;
use Mockery as m;

class D7QueryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * Create testing object
     * @param $adapter
     * @return D7Query
     */
    protected function createQuery($adapter)
    {
        $query = new D7Query('Arrilot\Tests\BitrixModels\Stubs\TestD7ElementClass', 'Arrilot\Tests\BitrixModels\Stubs\TestD7Element');

        return $query->setAdapter($adapter);
    }

    public function testCount()
    {
        $adapter = m::mock('D7Adapter');
        $adapter->shouldReceive('getClassName')->once()->andReturn('TestD7ClassName');
        $adapter->shouldReceive('getCount')->once()->andReturn(6);

        $query = $this->createQuery($adapter);
        $count = $query->count();
        $this->assertSame(6, $count);

        $adapter = m::mock('D7Adapter');
        $adapter->shouldReceive('getClassName')->once()->andReturn('TestD7ClassName');
        $adapter->shouldReceive('getCount')->with(['>ID' => 5])->once()->andReturn(3);

        $query = $this->createQuery($adapter);
        $count = $query->filter(['>ID' => 5])->count();
        $this->assertSame(3, $count);
    }
    
    public function testGetList()
    {
        $adapter = m::mock('D7Adapter');
        $params = [
            'select' => ['ID', 'UF_NAME'],
            'filter' => ['UF_NAME' => 'John'],
            'group' => [],
            'order' => ['ID' => 'ASC'],
            'limit' => null,
            'offset' => null,
            'runtime' => [],
        ];
        $adapter->shouldReceive('getClassName')->once()->andReturn('TestD7ClassName');
        $adapter->shouldReceive('getList')->with($params)->once()->andReturn(m::self());
        $adapter->shouldReceive('fetch')->andReturn(['ID' => 1, 'UF_NAME' => 'John Doe'], ['ID' => 2, 'UF_NAME' => 'John Doe 2'], false);

        $query = $this->createQuery($adapter);
        $items = $query->sort(['ID' => 'ASC'])->filter(['UF_NAME' => 'John'])->select(['ID', 'UF_NAME'])->getList();
        
        $expected = [
            1 => ['ID' => 1, 'UF_NAME' => 'John Doe'],
            2 => ['ID' => 2, 'UF_NAME' => 'John Doe 2'],
        ];
        foreach ($items as $k => $item) {
            $this->assertSame($expected[$k], $item->toArray());
        }
    }
}
