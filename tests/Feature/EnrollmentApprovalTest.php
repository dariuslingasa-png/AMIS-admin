<?php

namespace Tests\Feature;

use App\Mail\EnrollmentOnboardingMail;
use App\Models\EnrollmentApplicant;
use App\Models\EnrollmentSetting;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
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

    #[Test]
    public function family_review_approved_status_approves_remaining_children_in_the_family()
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

        $zainab = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'family_application_id' => 396,
            'first_name' => 'Zainab',
            'middle_name' => 'Mustafa',
            'last_name' => 'Islam',
            'gender' => 'female',
            'student_type' => 'Old',
            'grade_level' => 'Grade 11',
            'learning_mode' => 'Flexible Online Learning - 1st Shift',
            'school_year' => '2026-2027',
            'parent_email' => 'parent@example.com',
            'email' => 'zainab@example.com',
            'status' => 'approved',
        ]);

        $sophia = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'family_application_id' => 396,
            'first_name' => 'Sophia',
            'middle_name' => 'Mustafa',
            'last_name' => 'Islam',
            'gender' => 'female',
            'student_type' => 'Old',
            'grade_level' => 'Grade 8',
            'learning_mode' => 'Flexible Online Learning - 1st Shift',
            'school_year' => '2026-2027',
            'parent_email' => 'parent@example.com',
            'email' => 'sophia@example.com',
            'status' => 'submitted',
        ]);

        $suzana = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'family_application_id' => 396,
            'first_name' => 'Suzana',
            'middle_name' => 'Mustafa',
            'last_name' => 'Islam',
            'gender' => 'female',
            'student_type' => 'Old',
            'grade_level' => 'Grade 5',
            'learning_mode' => 'Flexible Online Learning - 1st Shift',
            'school_year' => '2026-2027',
            'parent_email' => 'parent@example.com',
            'email' => 'suzana@example.com',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.applicants.status', $zainab), [
            'status' => 'approved',
            'approval_scope' => 'family',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSame('approved', $zainab->fresh()->status);
        $this->assertSame('approved', $sophia->fresh()->status);
        $this->assertSame('approved', $suzana->fresh()->status);
        $this->assertTrue(Student::where('enrollment_applicant_id', $sophia->id)->exists());
        $this->assertTrue(Student::where('enrollment_applicant_id', $suzana->id)->exists());

        Mail::assertNothingSent();
    }

    #[Test]
    public function school_email_uses_only_the_first_given_name_token()
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

        $applicant = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Mohammed Ilias',
            'last_name' => 'Al-Shaud',
            'gender' => 'male',
            'student_type' => 'Old',
            'grade_level' => 'Grade 5',
            'learning_mode' => 'F2F',
            'school_year' => '2026-2027',
            'parent_email' => 'parent@example.com',
            'email' => 'mohammed@example.com',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.applicants.approve', $applicant));

        $response->assertRedirect();

        $schoolEmail = Student::where('enrollment_applicant_id', $applicant->id)->value('school_email');

        $this->assertNotNull($schoolEmail);
        $this->assertStringEndsWith('amohammed@amis.edu.ph', $schoolEmail);
        $this->assertStringNotContainsString('ilias', $schoolEmail);
    }

    #[Test]
    public function approval_sends_welcome_email_when_onboarding_email_is_enabled()
    {
        Mail::fake();

        EnrollmentSetting::create([
            'send_onboarding_email' => true,
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

        $applicant = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Layla',
            'last_name' => 'Welcome',
            'gender' => 'female',
            'student_type' => 'Old',
            'grade_level' => 'Grade 2',
            'learning_mode' => 'F2F',
            'school_year' => '2026-2027',
            'parent_email' => 'parent@example.com',
            'email' => 'layla@example.com',
            'status' => 'submitted',
        ]);

        Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant->id,
            'amount' => 4000.00,
            'method' => 'gcash',
            'receipt_url' => 'receipts/payment-proof.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.applicants.approve', $applicant));

        $response->assertRedirect();
        $response->assertSessionHas('success', fn ($message) => str_contains($message, 'sent to the parent'));

        $student = Student::where('enrollment_applicant_id', $applicant->id)->first();

        $this->assertNotNull($student);
        $this->assertNotNull($student->credentials_sent_at);

        Mail::assertSent(EnrollmentOnboardingMail::class, function (EnrollmentOnboardingMail $mail) use ($student) {
            return $mail->hasTo('parent@example.com')
                && $mail->hasTo('layla@example.com')
                && $mail->student->is($student);
        });
    }

    #[Test]
    public function approval_does_not_send_welcome_email_without_uploaded_payment_proof()
    {
        Mail::fake();

        EnrollmentSetting::create([
            'send_onboarding_email' => true,
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

        $applicant = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'first_name' => 'Noor',
            'last_name' => 'Pending',
            'gender' => 'female',
            'student_type' => 'Old',
            'grade_level' => 'Grade 2',
            'learning_mode' => 'F2F',
            'school_year' => '2026-2027',
            'parent_email' => 'parent@example.com',
            'email' => 'noor@example.com',
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.applicants.approve', $applicant));

        $response->assertRedirect();
        $response->assertSessionHas('success', fn ($message) => str_contains($message, 'no payment proof is uploaded'));

        $student = Student::where('enrollment_applicant_id', $applicant->id)->first();

        $this->assertNotNull($student);
        $this->assertNull($student->credentials_sent_at);

        Mail::assertNothingSent();
    }
}
