<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplicant;
use App\Models\FinanceMasterEntry;
use App\Models\FinanceMasterEntryStudent;
use App\Models\Invoice;
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
    public function finance_masters_list_displays_gender_for_old_rows_without_stored_gender()
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
            'family_application_id' => 9090,
            'first_name' => 'Hana',
            'last_name' => 'Saleh',
            'gender' => 'female',
            'student_type' => 'New',
            'grade_level' => 'Grade 4',
            'learning_mode' => 'F2F',
            'status' => 'submitted',
        ]);

        $payment = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant->id,
            'amount' => 500.00,
            'method' => 'bdo',
            'reference_no' => 'OLD-GENDER-ROW',
            'receipt_url' => 'receipts/old_gender.jpg',
            'status' => 'verified',
        ]);

        $entry = FinanceMasterEntry::create([
            'payment_id' => $payment->id,
            'family_name' => 'FAMILY OF SALEH',
            'method' => 'bdo',
            'reference_no' => 'OLD-GENDER-ROW',
            'payment_date' => '2026-06-11',
            'amount' => 500.00,
            'or_number' => 'OR-GENDER-1',
            'verified_by' => $admin->id,
        ]);

        FinanceMasterEntryStudent::create([
            'finance_master_entry_id' => $entry->id,
            'student_name' => 'Hana Saleh',
            'gender' => null,
            'grade_level' => 'Grade 4',
            'learning_mode' => 'F2F',
            'student_type' => 'NEW',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.finance.masters-list'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['HANA SALEH', 'F']);
        $response->assertSee(route('admin.applicants.show', $applicant), false);
        $response->assertSee('Enrollment');
    }

    /** @test */
    public function finance_masters_print_mode_includes_all_filtered_pages()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        foreach (['Print One' => 'male', 'Print Two' => 'female'] as $name => $gender) {
            $entry = FinanceMasterEntry::create([
                'family_name' => 'FAMILY OF PRINT',
                'method' => 'remittance',
                'reference_no' => strtoupper(str_replace(' ', '-', $name)),
                'payment_date' => '2026-06-11',
                'amount' => 500.00,
                'or_number' => 'OR-'.strtoupper(str_replace(' ', '-', $name)),
                'verified_by' => $admin->id,
            ]);

            FinanceMasterEntryStudent::create([
                'finance_master_entry_id' => $entry->id,
                'student_name' => $name,
                'gender' => $gender,
                'grade_level' => 'Grade 1',
                'learning_mode' => 'F2F',
                'student_type' => 'NEW',
            ]);
        }

        $response = $this->actingAs($admin)->get(route('admin.finance.masters-list', [
            'print' => 1,
            'per_page' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('A4 portrait');
        $response->assertSee('PRINT ONE');
        $response->assertSee('PRINT TWO');
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
            'gender' => 'male',
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
            'gender' => 'male',
            'grade_level' => 'Grade 8',
            'learning_mode' => 'ODL',
            'student_type' => 'OLD',
        ]);
    }

    /** @test */
    public function admin_can_open_payment_review_for_small_payment_amount()
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
            'family_application_id' => 4321,
            'first_name' => 'Aisha',
            'last_name' => 'Rahman',
            'gender' => 'female',
            'student_type' => 'New',
            'grade_level' => 'Grade 1',
            'learning_mode' => 'F2F',
            'status' => 'submitted',
        ]);

        $payment = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant->id,
            'amount' => 500.00,
            'method' => 'bdo',
            'reference_no' => 'BDO-500',
            'receipt_url' => 'receipts/small_bdo.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.payments.show', $payment));

        $response->assertStatus(200);
        $response->assertSee('500.00');
    }

    /** @test */
    public function verifying_payment_proof_with_custom_amount_auto_populates_finance_masters_list()
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
            'finance_amount' => 4500.00,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Check payment amount updated
        $this->assertEquals(4500.00, $payment->fresh()->amount);

        // Check the database for entry
        $this->assertDatabaseHas('finance_master_entries', [
            'payment_id' => $payment->id,
            'amount' => 4500.00,
        ]);
    }

    /** @test */
    public function verifying_payment_proof_auto_clears_duplicate_pending_payments()
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

        $payment1 = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant->id,
            'amount' => 4000.00,
            'method' => 'gcash',
            'reference_no' => '190372528',
            'receipt_url' => 'receipts/test_proof.jpg',
            'status' => 'pending',
        ]);

        $payment2 = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant->id,
            'amount' => 4000.00,
            'method' => 'gcash',
            'reference_no' => '190372528',
            'receipt_url' => 'receipts/test_proof.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.payments.verify', $payment1), [
            'finance_method' => 'gcash',
            'finance_payment_date' => '2026-05-26',
            'finance_reference_no' => '190372528',
            'finance_amount' => 4000.00,
        ]);

        $response->assertSessionHasNoErrors();

        // Assert payment 1 is verified for 4000.00
        $this->assertEquals('verified', $payment1->fresh()->status);
        $this->assertEquals(4000.00, (float) $payment1->fresh()->amount);

        // Assert duplicate payment 2 is auto-verified for 0.00
        $this->assertEquals('verified', $payment2->fresh()->status);
        $this->assertEquals(0.00, (float) $payment2->fresh()->amount);
    }

    /** @test */
    public function verifying_rejection_auto_rejects_duplicates_and_invoice_groups_or_numbering_correctly()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'account_status' => 'verified',
        ]);

        $parent = User::factory()->create([
            'role' => 'applicant',
        ]);

        $applicant1 = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'family_application_id' => 5678,
            'first_name' => 'ChildOne',
            'last_name' => 'Test',
            'student_type' => 'New',
            'grade_level' => 'Grade 1',
            'learning_mode' => 'F2F',
            'status' => 'submitted',
        ]);

        $applicant2 = EnrollmentApplicant::create([
            'user_id' => $parent->id,
            'family_application_id' => 5678,
            'first_name' => 'ChildTwo',
            'last_name' => 'Test',
            'student_type' => 'New',
            'grade_level' => 'Grade 2',
            'learning_mode' => 'F2F',
            'status' => 'submitted',
        ]);

        // Scenario 1: Rejection auto-rejects duplicate pending payments
        $paymentPending1 = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant1->id,
            'amount' => 4000.00,
            'method' => 'gcash',
            'reference_no' => '999888',
            'receipt_url' => 'receipts/proof_reject.jpg',
            'status' => 'pending',
        ]);

        $paymentPending2 = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant2->id,
            'amount' => 4000.00,
            'method' => 'gcash',
            'reference_no' => '999888',
            'receipt_url' => 'receipts/proof_reject.jpg',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.payments.reject', $paymentPending1), [
            'remarks' => 'Wrong receipt',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertEquals('rejected', $paymentPending1->fresh()->status);
        $this->assertEquals('rejected', $paymentPending2->fresh()->status);

        // Scenario 2: Verification of duplicates groups them under same OR number without incrementing suffixes
        $paymentVerify1 = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant1->id,
            'amount' => 4000.00,
            'method' => 'gcash',
            'reference_no' => '111222',
            'receipt_url' => 'receipts/proof_verify.jpg',
            'status' => 'pending',
        ]);

        $paymentVerify2 = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant2->id,
            'amount' => 4000.00,
            'method' => 'gcash',
            'reference_no' => '111222',
            'receipt_url' => 'receipts/proof_verify.jpg',
            'status' => 'pending',
        ]);

        $responseVerify = $this->actingAs($admin)->patch(route('admin.payments.verify', $paymentVerify1), [
            'finance_method' => 'gcash',
            'finance_payment_date' => '2026-06-11',
            'finance_reference_no' => '111222',
            'finance_amount' => 8000.00,
        ]);

        $responseVerify->assertSessionHasNoErrors();
        $invoice = Invoice::getOrCreateForFamily($applicant1);

        $this->assertEquals('verified', $paymentVerify1->fresh()->status);
        $this->assertEquals('verified', $paymentVerify2->fresh()->status);

        // They must share the exact same OR number
        $or1 = $paymentVerify1->fresh()->or_number;
        $or2 = $paymentVerify2->fresh()->or_number;
        $this->assertEquals($or1, $or2);

        // Since it's a single unique transaction of 8000 (total due is 8000), it should be the base OR (e.g. OR-XXXXXX)
        $expectedBaseOr = str_replace('INV-', config('services.school.or_prefix', 'OR-'), $invoice->invoice_no);
        $this->assertEquals($expectedBaseOr, $or1);
    }

    /** @test */
    public function admin_can_edit_finance_master_entry_and_syncs_with_payment_and_invoice()
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
            'family_application_id' => 7788,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'student_type' => 'New',
            'grade_level' => 'Grade 10',
            'learning_mode' => 'F2F',
            'status' => 'submitted',
        ]);

        $payment = Payment::create([
            'user_id' => $parent->id,
            'enrollment_applicant_id' => $applicant->id,
            'amount' => 4000.00,
            'method' => 'gcash',
            'reference_no' => '555666',
            'receipt_url' => 'receipts/test_edit.jpg',
            'status' => 'verified',
            'or_number' => 'OR-TEST-123',
        ]);

        $entry = FinanceMasterEntry::create([
            'payment_id' => $payment->id,
            'family_name' => 'FAMILY OF DOE',
            'method' => 'gcash',
            'reference_no' => '555666',
            'payment_date' => '2026-06-11',
            'amount' => 4000.00,
            'or_number' => 'OR-TEST-123',
            'verified_by' => $admin->id,
        ]);

        $invoice = Invoice::getOrCreateForFamily($applicant);
        $payment->update(['invoice_id' => $invoice->id]);
        $invoice->recalculate();

        $response = $this->actingAs($admin)->patch(route('admin.finance.masters-list.update', $entry), [
            'payment_date' => '2026-06-12',
            'method' => 'bdo',
            'reference_no' => '777888',
            'amount' => 5000.00,
            'or_number' => 'OR-UPDATED-456',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Assert FinanceMasterEntry is updated
        $this->assertEquals('2026-06-12', $entry->fresh()->payment_date->format('Y-m-d'));
        $this->assertEquals('bdo', $entry->fresh()->method);
        $this->assertEquals('777888', $entry->fresh()->reference_no);
        $this->assertEquals(5000.00, (float) $entry->fresh()->amount);
        $this->assertEquals('OR-UPDATED-456', $entry->fresh()->or_number);

        // Assert associated Payment is synced
        $this->assertEquals('2026-06-12', $payment->fresh()->paid_at->format('Y-m-d'));
        $this->assertEquals('bdo', $payment->fresh()->method);
        $this->assertEquals('777888', $payment->fresh()->reference_no);
        $this->assertEquals(5000.00, (float) $payment->fresh()->amount);
        $this->assertEquals('OR-UPDATED-456', $payment->fresh()->or_number);

        // Assert Invoice is recalculated with the new amount
        $this->assertEquals(5000.00, (float) $invoice->fresh()->amount_paid);
    }
}
