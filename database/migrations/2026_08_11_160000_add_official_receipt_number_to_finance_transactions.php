<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->string('official_receipt_number', 40)->nullable()->unique()->after('transaction_number');
        });

        $reservedNumbers = DB::table('finance_official_receipts')
            ->pluck('official_receipt_number')
            ->filter()
            ->flip();

        DB::table('finance_transactions')
            ->whereIn('status', ['APPROVED', 'REVERSED'])
            ->orderBy('id')
            ->get()
            ->each(function ($transaction) use (&$reservedNumbers) {
                $receipt = DB::table('finance_official_receipts')
                    ->where('finance_transaction_id', $transaction->id)
                    ->first();
                $submission = $transaction->payment_submission_id
                    ? DB::table('payment_submissions')->where('id', $transaction->payment_submission_id)->first()
                    : null;

                $issuedAt = Carbon::parse($receipt?->issued_at ?? $transaction->created_at ?? $transaction->transaction_at ?? now());
                $number = $this->numberForExistingTransaction($submission?->submission_number, $issuedAt, $reservedNumbers);
                $reservedNumbers->put($number, true);

                if ($receipt) {
                    DB::table('finance_official_receipts')->where('id', $receipt->id)->update([
                        'official_receipt_number' => $number,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('finance_official_receipts')->insert([
                        'official_receipt_number' => $number,
                        'finance_transaction_id' => $transaction->id,
                        'status' => $transaction->status === 'REVERSED' ? 'REVERSED' : 'ISSUED',
                        'snapshot' => json_encode([
                            'transaction_number' => $transaction->transaction_number,
                            'family_id' => $transaction->user_id,
                            'amount' => (float) $transaction->amount,
                            'payment_method' => $transaction->payment_method,
                            'reference_number' => $transaction->reference_number,
                            'transaction_at' => Carbon::parse($transaction->transaction_at)->toIso8601String(),
                            'allocation' => json_decode((string) $transaction->allocation_snapshot, true) ?: [],
                            'advance_credit' => (float) $transaction->advance_credit,
                        ]),
                        'issued_by' => $transaction->approved_by ?: $transaction->received_by ?: $transaction->created_by,
                        'issued_at' => $issuedAt,
                        'reversed_by' => $transaction->reversed_by,
                        'reversed_at' => $transaction->reversed_at,
                        'reversal_reason' => $transaction->reversal_reason,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('finance_transactions')->where('id', $transaction->id)->update([
                    'official_receipt_number' => $number,
                ]);
                DB::table('student_account_payments')->where('finance_transaction_id', $transaction->id)->update([
                    'or_number' => $number,
                ]);
            });

        DB::table('payment_submissions')
            ->where('submission_number', 'like', 'PAY-%')
            ->orderBy('id')
            ->get(['id', 'submission_number'])
            ->each(function ($submission) {
                $trackingNumber = preg_replace('/^PAY-/', 'SUB-', $submission->submission_number);
                DB::table('payment_submissions')->where('id', $submission->id)->update([
                    'submission_number' => $trackingNumber,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('finance_transactions', function (Blueprint $table) {
            $table->dropUnique(['official_receipt_number']);
            $table->dropColumn('official_receipt_number');
        });
    }

    private function numberForExistingTransaction(?string $submissionNumber, Carbon $issuedAt, $reservedNumbers): string
    {
        if ($submissionNumber && preg_match('/^(?:PAY|SUB)-(\d{8})-([A-Z0-9]{6})$/', strtoupper($submissionNumber), $matches)) {
            $candidate = 'OR-'.$matches[1].'-'.$matches[2];
            if (! $reservedNumbers->has($candidate)) {
                return $candidate;
            }
        }

        do {
            $candidate = 'OR-'.$issuedAt->format('Ymd').'-'.Str::upper(Str::random(6));
        } while ($reservedNumbers->has($candidate));

        return $candidate;
    }
};
