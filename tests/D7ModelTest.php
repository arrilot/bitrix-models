<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\Tests\BitrixModels\Stubs\TestD7Element;
use Arrilot\Tests\BitrixModels\Stubs\TestD7Element2;
use Arrilot\Tests\BitrixModels\Stubs\TestD7ResultObject;
use Mockery as m;

class D7ModelTest extends TestCase
{
    public function testInitialization()
    {
        $element = new TestD7Element(1);
        $this->assertSame(1, $element->id);

        $fields = [
            'UF_EMAIL' => 'John',
            'UF_IMAGE_ID' => '1',
        ];
        $element = new TestD7Element(1, $fields);
        $this->assertSame(1, $element->id);
        $this->assertSame($fields, $element->fields);
    }
    
    public function testMultipleInitialization()
    {
        // 1
        $element = new TestD7Element(1);
        $this->assertSame(1, $element->id);
    
        $fields = [
            'UF_EMAIL' => 'John',
            'UF_IMAGE_ID' => '1',
        ];
        $element = new TestD7Element(1, $fields);
        $this->assertSame(1, $element->id);
        $this->assertSame($fields, $element->fields);
        
        // 2
        $element2 = new TestD7Element2(1);
        $this->assertSame(1, $element2->id);
    
        $fields = [
            'UF_EMAIL' => 'John',
            'UF_IMAGE_ID' => '1',
        ];
        $element2 = new TestD7Element2(1, $fields);
        $this->assertSame(1, $element2->id);
        $this->assertSame($fields, $element2->fields);

//        dd([TestD7Element::cachedTableClass(), TestD7Element2::cachedTableClass()]);
        $this->assertTrue(TestD7Element::cachedTableClass() !== TestD7Element2::cachedTableClass());
        $this->assertTrue(TestD7Element::instantiateAdapter() !== TestD7Element2::instantiateAdapter());
    }

    public function testAdd()
    {
        $resultObject = new TestD7ResultObject();
        $adapter = m::mock('adapter');
        $adapter->shouldReceive('add')->once()->with(['UF_NAME' => 'Jane', 'UF_AGE' => '18'])->andReturn($resultObject);
        
        TestD7Element::setAdapter($adapter);
        $element = TestD7Element::create(['UF_NAME' => 'Jane', 'UF_AGE' => '18']);
        $this->assertEquals($element->id, 1);
        $this->assertEquals($element->fields, ['UF_NAME' => 'Jane', 'UF_AGE' => '18', 'ID' => '1']);
    }

    public function testUpdate()
    {
        $resultObject = new TestD7ResultObject();
        $adapter = m::mock('adapter');
        $adapter->shouldReceive('update')->once()->with(1, ['UF_NAME' => 'Jane'])->andReturn($resultObject);
    
        $element = new TestD7Element(1);
        TestD7Element::setAdapter($adapter);

    
        $this->assertTrue($element->update(['UF_NAME' => 'Jane']));
    }
    
    public function testDelete()
    {
        // normal
        $resultObject = new TestD7ResultObject();
        $adapter = m::mock('adapter');
        $adapter->shouldReceive('delete')->once()->with(1)->andReturn($resultObject);

        $element = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestD7Element[onAfterDelete, onBeforeDelete]', [1])
            ->shouldAllowMockingProtectedMethods();
        $element::setAdapter($adapter);
        $element->shouldReceive('onBeforeDelete')->once()->andReturn(null);
        $element->shouldReceive('onAfterDelete')->once()->with(true);
        
        $this->assertTrue($element->delete());
        
        // cancelled
        $element = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestD7Element[onAfterDelete, onBeforeDelete]', [1])
            ->shouldAllowMockingProtectedMethods();
        $element->shouldReceive('onBeforeDelete')->once()->andReturn(false);
        $element->shouldReceive('onAfterDelete')->never();
        $this->assertFalse($element->delete());
    }
}
