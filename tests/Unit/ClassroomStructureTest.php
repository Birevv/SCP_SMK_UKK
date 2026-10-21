<?php

namespace Tests\Unit;

use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Rombel;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_classroom_auto_syncs_relations_from_name(): void
    {
        $classroom = Classroom::create(['name' => 'XII RPL 2']);

        $this->assertNotNull($classroom->class_level_id);
        $this->assertNotNull($classroom->major_id);
        $this->assertNotNull($classroom->rombel_id);

        $this->assertEquals('XII', $classroom->classLevel->name);
        $this->assertEquals('RPL', $classroom->major->code);
        $this->assertEquals('2', $classroom->rombel->name);
    }

    public function test_classroom_auto_generates_name_from_relations(): void
    {
        $level = ClassLevel::create(['name' => 'X', 'level' => 10]);
        $major = Major::create(['code' => 'TKJ', 'name' => 'Teknik Komputer Jaringan']);
        $rombel = Rombel::create(['name' => '1', 'number' => 1]);

        $classroom = Classroom::create([
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);

        $this->assertEquals('X TKJ 1', $classroom->name);
    }

    public function test_scopes_for_grade_and_major_and_rombel(): void
    {
        $c1 = Classroom::create(['name' => 'X RPL 1']);
        $c2 = Classroom::create(['name' => 'XI AKL 1']);
        $c3 = Classroom::create(['name' => 'XII RPL 2']);

        $this->assertCount(1, Classroom::forGrade('10')->get());
        $this->assertCount(1, Classroom::forGrade('X')->get());
        $this->assertCount(1, Classroom::forGrade('11')->get());
        $this->assertCount(1, Classroom::forGrade('XII')->get());

        $this->assertCount(2, Classroom::forMajor('RPL')->get());
        $this->assertCount(1, Classroom::forMajor('AKL')->get());

        $this->assertCount(2, Classroom::forRombel('1')->get());
        $this->assertCount(1, Classroom::forRombel('2')->get());
    }
}
