<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintNoPaymentTest extends TestCase
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
    public function guest_cannot_access_print_no_payment()
    {
        $response = $this->get(route('admin.applications.print-no-payment'));
        $response->assertRedirect(route('admin.login'));
    }

    /** @test */
    public function admin_can_view_print_no_payment_list_showing_only_unpaid_families()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        // Family 1: Unpaid (No Payment proof)
        $user1 = User::factory()->create(['email' => 'unpaid@example.com']);
        $applicant1 = EnrollmentApplicant::create([
            'user_id' => $user1->id,
            'first_name' => 'Unpaid',
            'last_name' => 'Doe',
            'status' => 'submitted',
            'student_type' => 'new',
            'grade_level' => 'Grade 1',
            'mother_first_name' => 'Jane',
            'mother_last_name' => 'Doe',
            'parent_email' => 'unpaid@example.com',
        ]);

        // Family 2: Paid
        $user2 = User::factory()->create(['email' => 'paid@example.com']);
        $applicant2 = EnrollmentApplicant::create([
            'user_id' => $user2->id,
            'first_name' => 'Paid',
            'last_name' => 'Smith',
            'status' => 'submitted',
            'student_type' => 'new',
            'grade_level' => 'Grade 2',
            'mother_first_name' => 'Alice',
            'mother_last_name' => 'Smith',
            'parent_email' => 'paid@example.com',
        ]);
        Payment::create([
            'user_id' => $user2->id,
            'enrollment_applicant_id' => $applicant2->id,
            'amount' => 5000.00,
            'status' => 'verified',
            'receipt_url' => 'receipts/test.jpg',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.applications.print-no-payment'));

        $response->assertOk();
        $response->assertSeeText('UNPAID');
        $response->assertDontSeeText('PAID SMITH');
    }
}
