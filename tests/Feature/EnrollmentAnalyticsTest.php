<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentAnalyticsTest extends TestCase
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
    public function analytics_shows_new_and_old_student_counts()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        $parent = User::factory()->create([
            'role' => 'applicant',
        ]);

        EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Aisha',
            'last_name' => 'New',
            'student_type' => 'New',
            'grade_level' => 'Grade 1',
            'school_year' => '2026-2027',
            'status' => 'submitted',
        ]);

        EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Omar',
            'last_name' => 'New',
            'student_type' => 'new student',
            'grade_level' => 'Grade 2',
            'school_year' => '2026-2027',
            'status' => 'under_review',
        ]);

        EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Yusuf',
            'last_name' => 'Old',
            'student_type' => 'Old',
            'grade_level' => 'Grade 3',
            'school_year' => '2026-2027',
            'status' => 'approved',
        ]);

        EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Draft',
            'last_name' => 'Student',
            'student_type' => 'Old',
            'grade_level' => 'Grade 4',
            'school_year' => '2026-2027',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.enrollment.analytics'));

        $response->assertOk();
        $response->assertSeeText('New Students');
        $response->assertSeeText('Old Students');
        $response->assertSee('"NEW STUDENT":2', false);
        $response->assertSee('"OLD STUDENT":1', false);
    }
}
