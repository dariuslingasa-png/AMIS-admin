<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionOccupancyTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing()
    {
        return [
            '--path' => 'database/migrations/testing',
            '--realpath' => false,
            '--drop-views' => false,
            '--drop-types' => false,
        ];
    }

    /** @test */
    public function guests_and_non_admins_cannot_access_occupancy()
    {
        $response = $this->get(route('admin.students.occupancy'));
        $response->assertRedirect(route('admin.login'));

        $nonAdmin = User::factory()->create([
            'role' => 'student',
            'account_status' => 'verified',
        ]);

        $response = $this->actingAs($nonAdmin)->get(route('admin.students.occupancy'));
        $response->assertStatus(403);
    }

    /** @test */
    public function admins_can_access_occupancy_and_see_sections_and_rosters()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        // 1. F2F Section
        $f2fSection = Section::create([
            'name' => 'A',
            'grade_level' => 'Grade 1',
            'learning_mode' => 'face-to-face',
            'shift' => '1st Shift',
            'gender' => 'male',
        ]);

        // 2. Flexible Section
        $flexibleSection = Section::create([
            'name' => 'B',
            'grade_level' => 'Grade 1',
            'learning_mode' => 'flexible',
            'shift' => '1st Shift',
            'gender' => 'male',
        ]);

        $parent = User::factory()->create(['role' => 'applicant']);
        $applicant = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Abubakr',
            'last_name' => 'Ibn Affan',
            'student_type' => 'New',
            'grade_level' => 'Grade 1',
            'school_year' => '2026-2027',
            'status' => 'approved',
            'learning_mode' => 'face-to-face',
        ]);

        $studentUser = User::factory()->create(['role' => 'student']);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'enrollment_applicant_id' => $applicant->id,
            'student_number' => 'STU20260001',
            'school_email' => 'abubakr@amis.edu.ph',
            'grade_level' => 'Grade 1',
            'school_year' => '2026-2027',
        ]);

        // Enroll in the F2F section
        StudentSection::create([
            'student_id' => $student->id,
            'section_id' => $f2fSection->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.students.occupancy'));

        $response->assertOk();
        $response->assertSeeText('Section Occupancy');
        $response->assertSeeText('Grade 1');
        $response->assertSeeText('F2F - Boys'); // F2F section representation
        $response->assertSeeText('HUDHAYFAH IBN AL-YAMAN'); // Flexible section official name representation
        $response->assertSeeText('ABUBAKR IBN AFFAN');
        $response->assertSeeText('STU20260001');
    }
}
