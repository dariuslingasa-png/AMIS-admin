<?php

namespace Tests\Feature;

use App\Jobs\SendMonthlyPaymentReminderJob;
use App\Mail\PaymentReminderMail;
use App\Models\EnrollmentApplicant;
use App\Models\MonthlyPaymentReminder;
use App\Models\Role;
use App\Models\User;
use App\Services\Finance\MonthlyPaymentReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonthlyPaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return [
            '--path'       => 'database/migrations/testing',
            '--realpath'   => false,
            '--drop-views' => false,
            '--drop-types' => false,
        ];
    }

    private function createAdmin(): User
    {
        $admin = User::factory()->create([
            'role'           => 'finance',
            'account_status' => 'verified',
        ]);
        $roleId = Role::where('slug', 'finance')->value('id');
        if ($roleId) {
            $admin->roles()->sync([$roleId]);
        }
        return $admin;
    }

    #[Test]
    public function admin_can_view_monthly_payment_reminders_page(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.finance.monthly-reminders.index'));

        $response->assertStatus(200);
        $response->assertSeeText('Monthly Payment Reminders');
        $response->assertSeeText('Eligible Families');
        $response->assertSeeText('Already Sent');
    }

    #[Test]
    public function service_groups_multiple_children_into_one_family_email(): void
    {
        $parentEmail = 'family.test@example.com';

        // Child 1
        EnrollmentApplicant::create([
            'user_id'           => 1,
            'student_type'      => 'Old',
            'first_name'        => 'ChildOne',
            'last_name'         => 'FamilyTest',
            'grade_level'       => 'Grade 2',
            'gender'            => 'Male',
            'date_of_birth'     => '2018-05-10',
            'place_of_birth'    => 'Doha',
            'religion'          => 'Islam',
            'country'           => 'Qatar',
            'address'           => 'Doha, Qatar',
            'mobile_number'     => '12345678',
            'parent_mobile'     => '12345678',
            'parent_email'      => $parentEmail,
            'status'            => 'approved',
            'school_year'       => '2026-2027',
        ]);

        // Child 2 (Same parent email)
        EnrollmentApplicant::create([
            'user_id'           => 1,
            'student_type'      => 'Old',
            'first_name'        => 'ChildTwo',
            'last_name'         => 'FamilyTest',
            'grade_level'       => 'Grade 5',
            'gender'            => 'Female',
            'date_of_birth'     => '2015-09-20',
            'place_of_birth'    => 'Doha',
            'religion'          => 'Islam',
            'country'           => 'Qatar',
            'address'           => 'Doha, Qatar',
            'mobile_number'     => '12345678',
            'parent_mobile'     => '12345678',
            'parent_email'      => $parentEmail,
            'status'            => 'approved',
            'school_year'       => '2026-2027',
        ]);

        $service = app(MonthlyPaymentReminderService::class);
        $families = $service->getFamiliesCollection('2026-08');

        $family = $families->firstWhere('email', $parentEmail);

        $this->assertNotNull($family);
        $this->assertEquals(2, $family->student_count);
        $this->assertStringContainsString('CHILDONE FAMILYTEST', $family->student_names);
        $this->assertStringContainsString('CHILDTWO FAMILYTEST', $family->student_names);
    }

    #[Test]
    public function admin_can_send_test_email(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post(route('admin.finance.monthly-reminders.send-test'), [
            'test_email' => 'test.parent@gmail.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(PaymentReminderMail::class, function ($mail) {
            return $mail->hasTo('test.parent@gmail.com');
        });
    }

    #[Test]
    public function batch_dispatch_queues_jobs_and_is_idempotent(): void
    {
        Queue::fake();
        $admin = $this->createAdmin();

        EnrollmentApplicant::create([
            'user_id'           => 1,
            'student_type'      => 'Old',
            'first_name'        => 'TestStudent',
            'last_name'         => 'Recipient',
            'grade_level'       => 'Grade 3',
            'gender'            => 'Male',
            'date_of_birth'     => '2017-01-01',
            'place_of_birth'    => 'Doha',
            'religion'          => 'Islam',
            'country'           => 'Qatar',
            'address'           => 'Doha, Qatar',
            'mobile_number'     => '12345678',
            'parent_mobile'     => '12345678',
            'parent_email'      => 'unique.parent.batch@example.com',
            'status'            => 'approved',
            'school_year'       => '2026-2027',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.finance.monthly-reminders.send'), [
            'billing_month' => '2026-08',
        ]);

        $response->assertRedirect();

        Queue::assertPushed(SendMonthlyPaymentReminderJob::class);

        // Verify reminder record exists with PENDING status
        $this->assertDatabaseHas('monthly_payment_reminders', [
            'billing_month' => '2026-08',
            'parent_email'  => 'unique.parent.batch@example.com',
        ]);
    }

    #[Test]
    public function mailable_generates_unique_subject_and_unthreaded_headers(): void
    {
        $mailable1 = new PaymentReminderMail(
            recipientName: 'ABDULRAHEEM BAULO',
            billingMonth: '2026-08'
        );

        $envelope1 = $mailable1->envelope();
        $this->assertEquals(
            'AMIS Payment Reminder – Monthly School Fees – ABDULRAHEEM BAULO – August 2026',
            $envelope1->subject
        );

        $headers1 = $mailable1->headers();
        $this->assertNotEmpty($headers1->messageId);
        $this->assertEmpty($headers1->references);
        $this->assertArrayHasKey('X-Entity-Ref-ID', $headers1->text);

        // Verify another recipient has a different subject and unique messageId
        $mailable2 = new PaymentReminderMail(
            recipientName: 'FATIMA ZAHRA',
            billingMonth: '2026-08'
        );

        $envelope2 = $mailable2->envelope();
        $this->assertEquals(
            'AMIS Payment Reminder – Monthly School Fees – FATIMA ZAHRA – August 2026',
            $envelope2->subject
        );

        $this->assertNotEquals($envelope1->subject, $envelope2->subject);

        $headers2 = $mailable2->headers();
        $this->assertNotEquals($headers1->messageId, $headers2->messageId);
    }
}
