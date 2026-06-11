<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\EnrollmentSetting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnrollmentApprovalTest extends TestCase
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
    public function family_approval_generates_students_without_auto_sending_welcome_email()
    {
        Mail::fake();

        EnrollmentSetting::create([
            'send_onboarding_email' => false,
            'generate_amis_id' => true,
            'generate_microsoft_account' => false,
            'generate_soa' => false,
            'require_documents_approved' => false,
            'require_payment_verified' => false,
            'require_complete_fields' => false,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        $parent = User::factory()->create([
            'role' => 'applicant',
        ]);

        $firstChild = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'family_application_id' => 2468,
            'first_name' => 'Aminah',
            'last_name' => 'Family',
            'gender' => 'female',
            'student_type' => 'Old',
            'grade_level' => 'Grade 3',
            'learning_mode' => 'F2F',
            'school_year' => '2026-2027',
            'parent_email' => 'parent@example.com',
            'email' => 'aminah@example.com',
            'status' => 'submitted',
        ]);

        $secondChild = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'family_application_id' => 2468,
            'first_name' => 'Yusuf',
            'last_name' => 'Family',
            'gender' => 'male',
            'student_type' => 'Old',
            'grade_level' => 'Grade 4',
            'learning_mode' => 'F2F',
            'school_year' => '2026-2027',
            'parent_email' => 'parent@example.com',
            'email' => 'yusuf@example.com',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.applicants.approve-family', $firstChild));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('approved', $firstChild->fresh()->status);
        $this->assertSame('approved', $secondChild->fresh()->status);
        $this->assertSame(2, Student::count());
        $this->assertTrue(Student::whereNotNull('student_number')->count() === 2);
        $this->assertTrue(Student::whereNotNull('school_email')->count() === 2);
        $this->assertSame(0, Student::whereNotNull('credentials_sent_at')->count());

        Mail::assertNothingSent();
    }
}
