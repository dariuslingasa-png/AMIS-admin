<?php

namespace Tests\Unit;

use App\Models\AcademicRoom;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Services\Admin\Academic\AcademicScheduleConflictService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicScheduleConflictServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_sqlite extension is not installed in this environment.');
        }

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('grade_level');
            $table->string('learning_mode')->default('Face-to-Face');
            $table->string('shift')->nullable();
            $table->string('gender')->default('merge');
            $table->string('academic_status')->default('active');
            $table->timestamps();
        });

        Schema::create('academic_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id');
            $table->foreignId('subject_id')->nullable();
            $table->foreignId('room_id')->nullable();
            $table->string('subject_name');
            $table->boolean('spans_all_days')->default(false);
            $table->boolean('is_special')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->string('teacher_key')->nullable();
            $table->string('teacher_display')->nullable();
            $table->string('teacher_status')->default('matched');
            $table->string('day');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('mode')->default('f2f');
            $table->string('school_year')->default('2026-2027');
            $table->timestamps();
        });
    }

    public function test_it_blocks_section_teacher_and_room_overlaps_but_allows_adjacent_slots(): void
    {
        $first = Section::create(['name' => 'A', 'grade_level' => 'Grade 7']);
        $second = Section::create(['name' => 'B', 'grade_level' => 'Grade 8']);
        $room = AcademicRoom::create(['name' => 'Room 101']);

        ClassSchedule::create($this->schedule([
            'section_id' => $first->id,
            'room_id' => $room->id,
            'teacher_key' => 'teacher-a',
        ]));

        $service = app(AcademicScheduleConflictService::class);

        $this->expectConflict(fn () => $service->assertCanSave($this->schedule([
            'section_id' => $first->id,
            'room_id' => null,
            'teacher_key' => 'teacher-b',
        ])));

        $this->expectConflict(fn () => $service->assertCanSave($this->schedule([
            'section_id' => $second->id,
            'room_id' => null,
            'teacher_key' => 'teacher-a',
        ])));

        $this->expectConflict(fn () => $service->assertCanSave($this->schedule([
            'section_id' => $second->id,
            'room_id' => $room->id,
            'teacher_key' => 'teacher-b',
        ])));

        $service->assertCanSave($this->schedule([
            'section_id' => $second->id,
            'room_id' => $room->id,
            'teacher_key' => 'teacher-a',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]));

        $this->assertTrue(true);
    }

    public function test_locked_rows_are_protected_without_being_reported_as_conflicts_by_the_audit(): void
    {
        $section = Section::create(['name' => 'A', 'grade_level' => 'Grade 7']);
        $locked = ClassSchedule::create($this->schedule([
            'section_id' => $section->id,
            'is_locked' => true,
        ]));
        $service = app(AcademicScheduleConflictService::class);

        $this->expectConflict(fn () => $service->assertCanSave($locked->toArray(), $locked));
        $this->assertTrue($service->conflictsFor(collect([$locked]))->isEmpty());
    }

    private function schedule(array $overrides = []): array
    {
        return array_replace([
            'section_id' => 1,
            'subject_id' => null,
            'room_id' => null,
            'subject_name' => 'Mathematics',
            'teacher_key' => 'teacher-a',
            'teacher_display' => 'Teacher A',
            'teacher_status' => 'matched',
            'day' => 'Sunday',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'spans_all_days' => false,
            'is_special' => false,
            'is_locked' => false,
            'mode' => 'f2f',
            'school_year' => '2026-2027',
        ], $overrides);
    }

    private function expectConflict(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Expected a schedule conflict.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }
    }
}
