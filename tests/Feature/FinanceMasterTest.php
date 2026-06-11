<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\FinanceMasterEntry;
use App\Models\FinanceMasterEntryStudent;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceMasterTest extends TestCase
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
    public function guest_cannot_access_finance_masters_list()
    {
        $response = $this->get(route('admin.finance.masters-list'));

        $response->assertRedirect(route('admin.login'));
    }

    /** @test */
    public function non_admin_cannot_access_finance_masters_list()
    {
        $nonAdmin = User::factory()->create([
            'role' => 'student',
            'account_status' => 'verified',
        ]);

        $response = $this->actingAs($nonAdmin)->get(route('admin.finance.masters-list'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_finance_masters_list()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.finance.masters-list'));

        $response->assertStatus(200);
    }

    /** @test */
    public function verifying_payment_proof_auto_populates_finance_masters_list()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        $parent = User::factory()->create([
            'role' => 'applicant',
        ]);

        $applicant = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'family_application_id' => 1234,
            'first_name' => 'Abdullah',
            'last_name' => 'Sace',
            'student_type' => 'Old',
            'grade_level' => 'Grade 8',
            'learning_mode' => 'Flexible Online Learning – 1st Shift',
            'status' => 'submitted',
        ]);

        $payment = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant->id,
            'amount' => 71385.00,
            'method' => 'remittance',
            'reference_no' => '105251011098847',
            'receipt_url' => 'receipts/test_proof.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.payments.verify', $payment), [
            'finance_method' => 'remittance',
            'remittance_source' => 'AL GHURAIR EXCHANGE',
            'finance_payment_date' => '2025-07-12',
            'finance_reference_no' => '105251011098847',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Check the database for entry
        $this->assertDatabaseHas('finance_master_entries', [
            'payment_id' => $payment->id,
            'family_name' => 'FAMILY OF SACE',
            'remittance_source' => 'AL GHURAIR EXCHANGE',
            'reference_no' => '105251011098847',
            'method' => 'remittance',
            'payment_date' => '2025-07-12',
            'amount' => 71385.00,
            'verified_by' => $admin->id,
        ]);

        // Check database for student details
        $this->assertDatabaseHas('finance_master_entry_students', [
            'student_name' => 'Abdullah Sace',
            'grade_level' => 'Grade 8',
            'learning_mode' => 'FOL - 1ST SHIFT',
            'student_type' => 'OLD',
        ]);
    }
}
