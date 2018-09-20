<?php

namespace Arrilot\Tests\BitrixModels;

use Arrilot\Tests\BitrixModels\Stubs\TestSection;
use Mockery as m;

class SectionModelTest extends TestCase
{
    public function setUp()
    {
        TestSection::$bxObject = m::mock('obj');
    }

    public function tearDown()
    {
        TestSection::destroyObject();
        m::close();
    }

    public function testInitialization()
    {
        $section = new TestSection(2, ['NAME' => 'Section one']);

        $this->assertSame(2, $section->id);
        $this->assertSame(['NAME' => 'Section one'], $section->fields);
    }

    public function testDelete()
    {
        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('delete')->once()->andReturn(true);

        TestSection::$bxObject = $bxObject;
        $section = new TestSection(1);

        $this->assertTrue($section->delete());
    }

    public function testActivate()
    {
        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('update')->with(1, ['ACTIVE' => 'Y'], true, true, false)->once()->andReturn(true);

        TestSection::$bxObject = $bxObject;
        $section = new TestSection(1);

        $this->assertTrue($section->activate());
    }

    public function testDeactivate()
    {
        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('update')->with(1, ['ACTIVE' => 'N'], true, true, false)->once()->andReturn(true);

        TestSection::$bxObject = $bxObject;
        $section = new TestSection(1);

        $this->assertTrue($section->deactivate());
    }

    public function testCreate()
    {
        $bxObject = m::mock('obj');
        $bxObject->shouldReceive('add')->with(['NAME' => 'Section 1', 'IBLOCK_ID' => TestSection::iblockId()], true, true, false)->once()->andReturn(3);

        TestSection::$bxObject = $bxObject;

        $newTestSection = TestSection::create(['NAME' => 'Section 1']);

        $this->assertSame(3, $newTestSection->id);
        $this->assertEquals([
            'NAME' => 'Section 1',
            'ID'   => 3,
            'IBLOCK_ID' => TestSection::iblockId(),
        ], $newTestSection->fields);
    }

    public function testUpdate()
    {
        $section = m::mock('Arrilot\Tests\BitrixModels\Stubs\TestSection[save]', [1]);
        $section->shouldReceive('save')->with(['NAME', 'UF_FOO'])->andReturn(true);

        $this->assertTrue($section->update(['NAME' => 'Section 1', 'UF_FOO' => 'bar']));
        $this->assertSame('Section 1', $section->fields['NAME']);
        $this->assertSame('bar', $section->fields['UF_FOO']);
    }

    public function testFill()
    {
        TestSection::$bxObject = m::mock('obj');
        $section = new TestSection(1);

        $fields = ['ID' => 2, 'NAME' => 'Section 1'];
        $section->fill($fields);

        $this->assertSame(2, $section->id);
        $this->assertSame($fields, $section->fields);
        $this->assertSame($fields, $section->get());
    }
}
