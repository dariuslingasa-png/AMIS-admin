<?php

namespace Tests\Unit;

use App\Jobs\SendFinancePaymentNotification;
use App\Mail\FinancePaymentApprovedAdviserMail;
use App\Mail\FinancePaymentApprovedParentMail;
use App\Models\EnrollmentApplicant;
use App\Models\FinanceOfficialReceipt;
use App\Models\FinanceTransaction;
use App\Models\SoaMonthlyBilling;
use App\Models\Student;
use App\Models\StudentAccountPayment;
use App\Models\User;
use App\Services\Finance\FamilyPaymentReceiptService;
use Illuminate\Support\Collection;
use LogicException;
use Tests\TestCase;

class FinancePaymentMailTest extends TestCase
{
    public function test_finance_email_job_uses_the_admin_finance_queue(): void
    {
        $this->assertSame('admin-finance', (new SendFinancePaymentNotification(1))->queue);
    }

    public function test_parent_payment_approval_email_is_brief_and_includes_the_pdf_attachment(): void
    {
        $transaction = $this->transaction();
        $mail = new FinancePaymentApprovedParentMail($transaction);
        $html = view($mail->content()->view, [
            'transaction' => $transaction,
        ])->render();

        $this->assertStringContainsString('AMIS FAMILY PAYMENT RECEIPT', $mail->envelope()->subject);
        $this->assertSame('AMIS Support Staff', $mail->envelope()->from->name);
        $this->assertStringContainsString('PAYMENT APPROVED', $html);
        $this->assertStringNotContainsString('✓', $html);
        $this->assertStringContainsString('AMIS Logo', $html);
        $this->assertStringContainsString('cid:amis-logo@amis.edu.ph', $html);
        $this->assertStringContainsString('8,200.00', $html);
        $this->assertStringContainsString('FPR-20260813-TEST', $html);
        $this->assertStringContainsString('attached as a PDF', $html);
        $this->assertStringNotContainsString('PAYMENT ALLOCATION', $html);
        $this->assertStringNotContainsString('Remaining Due After This Payment', $html);
        $this->assertStringNotContainsString('paperless', strtolower($html));
        $this->assertCount(1, $mail->attachments());
    }

    public function test_family_payment_receipt_pdf_renders_without_the_gd_extension(): void
    {
        $pdf = app(FamilyPaymentReceiptService::class)->render($this->transaction());

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(5000, strlen($pdf));
    }

    public function test_family_receipt_uses_immutable_totals_and_displays_excess_as_credit(): void
    {
        $transaction = $this->transaction();
        $receipt = $transaction->officialReceipt;
        $snapshot = $receipt->snapshot;
        $snapshot['family_receipt_rows'] = [
            ['student_name' => 'JUAN D. LINGASA', 'grade_level' => 'Grade 1', 'billing_month' => 'July 2026', 'amount_due' => 3604.44, 'amount_paid' => 3604.44, 'remaining' => 0, 'status' => 'FULLY PAID'],
            ['student_name' => 'MARIA D. LINGASA', 'grade_level' => 'Grade 3', 'billing_month' => 'July 2026', 'amount_due' => 3720, 'amount_paid' => 3720, 'remaining' => 0, 'status' => 'FULLY PAID'],
            ['student_name' => 'CARLO D. LINGASA', 'grade_level' => 'Grade 5', 'billing_month' => 'July 2026', 'amount_due' => 3862.22, 'amount_paid' => 3862.22, 'remaining' => 0, 'status' => 'FULLY PAID'],
            ['student_name' => 'SOFIA D. LINGASA', 'grade_level' => 'Grade 7', 'billing_month' => 'July 2026', 'amount_due' => 4073.33, 'amount_paid' => 4073.33, 'remaining' => 0, 'status' => 'FULLY PAID'],
        ];
        $snapshot['previous_balance'] = 15259.99;
        $snapshot['amount_applied'] = 15259.99;
        $snapshot['remaining_family_balance'] = 0;
        $snapshot['available_credit_balance'] = 2740.01;
        $snapshot['credit_created'] = 2740.01;
        $receipt->snapshot = $snapshot;
        $transaction->amount = 18000;

        $data = app(FamilyPaymentReceiptService::class)->data($transaction);
        $html = view('admin.finance.receipts.pdf', ['receiptData' => $data])->render();

        $this->assertSame(15259.99, $data['total_amount_due']);
        $this->assertSame(15259.99, $data['total_allocated_to_children']);
        $this->assertSame(15259.99, $data['amount_applied']);
        $this->assertSame(0.0, $data['remaining_balance']);
        $this->assertSame(2740.01, $data['credit_balance']);
        $this->assertSame('FULLY PAID — WITH CREDIT BALANCE', $data['payment_status']);
        $this->assertStringContainsString('<strong>TOTAL</strong>', $html);
        $this->assertStringContainsString('Credit Balance', $html);
        $this->assertStringContainsString('₱2,740.01', $html);
        $this->assertStringNotContainsString('-₱', $html);
        $this->assertStringContainsString('credit balance, or remaining balance', $html);
    }

    public function test_legacy_receipt_separates_previous_state_from_current_payment(): void
    {
        $transaction = $this->transaction();
        $receipt = $transaction->officialReceipt;
        $receipt->snapshot = [
            'family_receipt_number' => 'FPR-20260813-LEGACY',
            'amount' => 5000,
            'amount_applied' => 5000,
            'previous_balance' => 6819.98,
            'remaining_family_balance' => 1819.98,
            'advance_credit' => 0,
            'allocation' => [
                ['applied_amount' => 2746.65],
                ['applied_amount' => 2253.35],
            ],
            'family_receipt_rows' => [
                ['student_name' => 'JUAN D. LINGASA', 'grade_level' => 'Grade 1', 'billing_month' => 'August 2026', 'amount_due' => 3604.44, 'amount_paid' => 3604.44, 'remaining' => 0, 'status' => 'FULLY PAID'],
                ['student_name' => 'MARIA D. LINGASA', 'grade_level' => 'Grade 3', 'billing_month' => 'August 2026', 'amount_due' => 3720, 'amount_paid' => 3720, 'remaining' => 0, 'status' => 'FULLY PAID'],
                ['student_name' => 'CARLO D. LINGASA', 'grade_level' => 'Grade 5', 'billing_month' => 'August 2026', 'amount_due' => 3862.22, 'amount_paid' => 3862.22, 'remaining' => 0, 'status' => 'FULLY PAID'],
                ['student_name' => 'SOFIA D. LINGASA', 'grade_level' => 'Grade 7', 'billing_month' => 'August 2026', 'amount_due' => 4073.33, 'amount_paid' => 2253.35, 'remaining' => 1819.98, 'status' => 'PARTIALLY PAID'],
            ],
        ];
        $transaction->amount = 5000;

        $data = app(FamilyPaymentReceiptService::class)->data($transaction);
        $html = view('admin.finance.receipts.pdf', ['receiptData' => $data])->render();

        $this->assertSame(8440.01, $data['previous_total_paid']);
        $this->assertSame(6819.98, $data['previous_remaining_balance']);
        $this->assertSame(13440.01, $data['total_paid_to_date']);
        $this->assertSame(5000.0, $data['amount_applied']);
        $this->assertSame(1819.98, $data['remaining_balance']);
        $this->assertSame(0.0, $data['credit_balance']);
        $this->assertStringContainsString('Total Paid to Date', $html);
        $this->assertStringContainsString('Previous Balance', $html);
        $this->assertStringContainsString('₱6,819.98', $html);
        $this->assertStringNotContainsString('Credit Balance', $html);
    }

    public function test_receipt_shows_existing_credit_applied_before_current_payment(): void
    {
        $transaction = $this->transaction();
        $receipt = $transaction->officialReceipt;
        $receipt->snapshot = [
            'family_receipt_number' => 'FPR-20260813-CREDIT',
            'family_receipt_rows' => [
                ['student_name' => 'CHILD ONE', 'grade_level' => 'Grade 1', 'billing_month' => 'August 2026', 'amount_due' => 3222.22, 'amount_paid' => 3222.22, 'remaining' => 0, 'status' => 'FULLY PAID'],
                ['student_name' => 'CHILD TWO', 'grade_level' => 'Grade 4', 'billing_month' => 'August 2026', 'amount_due' => 5444.44, 'amount_paid' => 2111.12, 'remaining' => 3333.32, 'status' => 'PARTIALLY PAID'],
            ],
            'total_family_due' => 8666.66,
            'previous_total_paid' => 0,
            'existing_credit_balance_before' => 333.34,
            'existing_credit_applied' => 333.34,
            'existing_credit_remaining' => 0,
            'balance_before_credit' => 8666.66,
            'previous_remaining_balance' => 8333.32,
            'current_amount_received' => 5000,
            'current_amount_applied' => 5000,
            'credit_created' => 0,
            'new_total_paid' => 5333.34,
            'new_remaining_balance' => 3333.32,
            'new_credit_balance' => 0,
            'allocation' => [
                ['applied_amount' => 2888.88],
                ['applied_amount' => 2111.12],
            ],
        ];
        $transaction->amount = 5000;

        $data = app(FamilyPaymentReceiptService::class)->data($transaction);
        $html = view('admin.finance.receipts.pdf', ['receiptData' => $data])->render();

        $this->assertSame(333.34, $data['credit_applied']);
        $this->assertSame(8666.66, $data['balance_before_credit']);
        $this->assertSame(8333.32, $data['previous_remaining_balance']);
        $this->assertSame(5000.0, $data['amount_applied']);
        $this->assertSame(3333.32, $data['remaining_balance']);
        $this->assertSame(0.0, $data['credit_balance']);
        $this->assertStringContainsString('Credit Applied', $html);
        $this->assertStringContainsString('−₱333.34', $html);
        $this->assertStringContainsString('Balance After Credit', $html);
        $this->assertStringContainsString('Current Payment Received', $html);
        $this->assertStringContainsString('Current Payment Applied', $html);
    }

    public function test_receipt_refuses_to_render_an_inconsistent_snapshot(): void
    {
        $transaction = $this->transaction();
        $receipt = $transaction->officialReceipt;
        $snapshot = $receipt->snapshot;
        $snapshot['family_receipt_rows'][1]['remaining'] = 3900;
        $receipt->snapshot = $snapshot;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('student balance equation');

        app(FamilyPaymentReceiptService::class)->data($transaction);
    }

    public function test_adviser_email_shows_grade_but_not_section_or_family_finances(): void
    {
        $transaction = $this->transaction();
        $mail = new FinancePaymentApprovedAdviserMail($transaction);
        $html = view($mail->content()->view, ['transaction' => $transaction])->render();

        $this->assertStringContainsString('AMIS STUDENT PAYMENT UPDATE', $mail->envelope()->subject);
        $this->assertSame('AMIS Support Staff', $mail->envelope()->from->name);
        $this->assertStringContainsString('GRADE 3', $html);
        $this->assertStringContainsString('MARIA D. LINGASA', $html);
        $this->assertStringContainsString('SETTLED', $html);
        $this->assertStringNotContainsString('JUAN D. LINGASA', $html);
        $this->assertStringNotContainsString('4 LINKED CHILDREN', $html);
        $this->assertStringNotContainsString('NO PAYMENT APPLIED', $html);
        $this->assertStringNotContainsString('PARTIAL PAYMENT', $html);
        $this->assertStringNotContainsString('✓', $html);
        $this->assertStringContainsString('AMIS Logo', $html);
        $this->assertStringContainsString('cid:amis-logo@amis.edu.ph', $html);
        $this->assertStringNotContainsString('Section:', $html);
        $this->assertStringNotContainsString('Family balance', $html);
        $this->assertStringNotContainsString('FT26222662202746', $html);
    }

    private function transaction(): FinanceTransaction
    {
        $family = new User(['name' => 'LINGASA', 'email' => 'parent@example.com']);
        $makeStudent = function (int $id, string $firstName, string $grade): Student {
            $applicant = new EnrollmentApplicant([
                'first_name' => $firstName,
                'middle_name' => 'D',
                'last_name' => 'LINGASA',
            ]);
            $student = new Student(['grade_level' => $grade]);
            $student->id = $id;
            $student->setRelation('applicant', $applicant);

            return $student;
        };
        $juan = $makeStudent(9, 'JUAN', 'Grade 1');
        $maria = $makeStudent(10, 'MARIA', 'Grade 3');
        $carlo = $makeStudent(11, 'CARLO', 'Grade 5');
        $sofia = $makeStudent(12, 'SOFIA', 'Grade 7');
        $family->setRelation('students', new Collection([$juan, $maria, $carlo, $sofia]));
        $billing = new SoaMonthlyBilling(['month_name' => 'July']);
        $payment = new StudentAccountPayment(['student_id' => 10]);
        $payment->setRelation('student', $maria);
        $payment->setRelation('monthlyBilling', $billing);

        $transaction = new FinanceTransaction([
            'transaction_number' => 'FTX-20260813-TEST',
            'official_receipt_number' => 'OR-20260813-TEST',
            'reference_number' => 'FT26222662202746',
            'payment_method' => 'REMITTANCE',
            'amount' => 8200,
            'family_balance_after' => 122640,
            'transaction_at' => '2026-08-10 23:15:00',
            'allocation_snapshot' => [[
                'student_id' => 10,
                'student_name' => 'MARIA D. LINGASA',
                'billing_month' => 'July',
                'applied_amount' => 8200,
                'remaining_after' => 0,
            ]],
        ]);
        $transaction->setRelation('family', $family);
        $transaction->setRelation('allocations', new Collection([$payment]));
        $transaction->setRelation('officialReceipt', new FinanceOfficialReceipt([
            'official_receipt_number' => 'OR-20260813-TEST',
            'status' => 'ISSUED',
            'issued_at' => '2026-08-13 16:00:00',
            'snapshot' => [
                'family_receipt_number' => 'FPR-20260813-TEST',
                'family_receipt_rows' => [
                    ['student_name' => 'AHMAD LINGASA', 'grade_level' => 'Grade 2', 'billing_month' => 'August 2026', 'amount_due' => 5000, 'amount_paid' => 5000, 'remaining' => 0, 'status' => 'FULLY PAID'],
                    ['student_name' => 'AISHA LINGASA', 'grade_level' => 'Grade 5', 'billing_month' => 'August 2026', 'amount_due' => 7000, 'amount_paid' => 3200, 'remaining' => 3800, 'status' => 'PARTIALLY PAID'],
                ],
            ],
        ]));

        return $transaction;
    }
}
