<?php

namespace App\Services\Payment;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UnifiedPaymentDuplicateService
{
    /**
     * Check every payment source stored in the shared AMIS database.
     * References and receipt hashes remain reserved even when an old record
     * is later rejected, cancelled, or reversed.
     */
    public function check(
        ?string $referenceNumber,
        ?string $receiptHash = null,
        array $exclude = []
    ): array {
        $reference = $this->normalizeReference($referenceNumber);
        $hash = Str::lower(trim((string) $receiptHash));

        if ($hash !== '') {
            $hashMatch = $this->findHashMatch($hash, $exclude);
            if ($hashMatch) {
                return $this->duplicate('DUPLICATE_RECEIPT_IMAGE', $hashMatch);
            }
        }

        if ($reference !== '') {
            $referenceMatch = $this->findReferenceMatch($reference, $exclude);
            if ($referenceMatch) {
                return $this->duplicate('DUPLICATE_TRANSACTION_REFERENCE', $referenceMatch);
            }
        }

        return [
            'duplicate' => false,
            'code' => null,
            'message' => 'No duplicate payment found.',
            'match' => null,
        ];
    }

    public function normalizeReference(?string $referenceNumber): string
    {
        return Str::of((string) $referenceNumber)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->value();
    }

    private function findHashMatch(string $hash, array $exclude): ?array
    {
        if (Schema::hasTable('payment_submissions') && Schema::hasColumn('payment_submissions', 'receipt_hash')) {
            $match = DB::table('payment_submissions')
                ->when($exclude['payment_submission_id'] ?? null, fn (Builder $query, $id) => $query->where('id', '!=', $id))
                ->whereRaw('LOWER(receipt_hash) = ?', [$hash])
                ->first(['id', 'status']);
            if ($match) return $this->match('Family Payment submission', $match);
        }

        if (Schema::hasTable('receipt_submissions') && Schema::hasColumn('receipt_submissions', 'receipt_hash')) {
            $match = $this->committedReceiptQuery()
                ->when($exclude['receipt_submission_id'] ?? null, fn (Builder $query, $id) => $query->where('receipt_submissions.id', '!=', $id))
                ->whereRaw('LOWER(receipt_submissions.receipt_hash) = ?', [$hash])
                ->first(['receipt_submissions.id', 'receipt_submissions.status']);
            if ($match) return $this->match('Payment proof', $match);
        }

        foreach ([
            ['payments', 'Payment record'],
            ['student_account_payments', 'Student account payment'],
        ] as [$table, $label]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'receipt_hash')) continue;
            $match = DB::table($table)->whereRaw('LOWER(receipt_hash) = ?', [$hash])->first(['id', 'status']);
            if ($match) return $this->match($label, $match);
        }

        return null;
    }

    private function findReferenceMatch(string $reference, array $exclude): ?array
    {
        if (Schema::hasTable('finance_transactions') && Schema::hasColumn('finance_transactions', 'reference_number')) {
            $query = DB::table('finance_transactions')
                ->when($exclude['finance_transaction_id'] ?? null, fn (Builder $query, $id) => $query->where('id', '!=', $id));
            $match = $this->whereNormalizedReference($query, 'reference_number', $reference)
                ->first(['id', 'status']);
            if ($match) return $this->match('Finance transaction', $match);
        }

        if (Schema::hasTable('payment_submissions') && Schema::hasColumn('payment_submissions', 'reference_normalized')) {
            $match = DB::table('payment_submissions')
                ->when($exclude['payment_submission_id'] ?? null, fn (Builder $query, $id) => $query->where('id', '!=', $id))
                ->whereRaw('LOWER(reference_normalized) = ?', [$reference])
                ->first(['id', 'status']);
            if ($match) return $this->match('Family Payment submission', $match);
        }

        if (Schema::hasTable('receipt_submissions') && Schema::hasColumn('receipt_submissions', 'normalized_reference')) {
            $query = $this->committedReceiptQuery()
                ->when($exclude['receipt_submission_id'] ?? null, fn (Builder $query, $id) => $query->where('receipt_submissions.id', '!=', $id));
            $match = $this->whereNormalizedReference($query, 'receipt_submissions.normalized_reference', $reference)
                ->first(['receipt_submissions.id', 'receipt_submissions.status']);
            if ($match) return $this->match('Payment proof', $match);
        }

        foreach ([
            ['payments', 'reference_no', 'Enrollment payment'],
            ['student_account_payments', 'reference_no', 'Student account payment'],
        ] as [$table, $column, $label]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) continue;
            $match = $this->whereNormalizedReference(DB::table($table), $column, $reference)
                ->first(['id', 'status']);
            if ($match) return $this->match($label, $match);
        }

        return null;
    }

    private function committedReceiptQuery(): Builder
    {
        $hasPaymentLinks = Schema::hasTable('payment_submissions')
            && Schema::hasColumn('payment_submissions', 'receipt_submission_id');
        $hasFinanceLinks = Schema::hasTable('finance_transactions')
            && Schema::hasColumn('finance_transactions', 'receipt_submission_id');

        return DB::table('receipt_submissions')
            ->where(function (Builder $query) use ($hasPaymentLinks, $hasFinanceLinks) {
                if ($hasPaymentLinks) {
                    $query->whereExists(function (Builder $linked) {
                        $linked->selectRaw('1')
                            ->from('payment_submissions')
                            ->whereColumn('payment_submissions.receipt_submission_id', 'receipt_submissions.id');
                    });
                }
                if ($hasFinanceLinks) {
                    $method = $hasPaymentLinks ? 'orWhereExists' : 'whereExists';
                    $query->{$method}(function (Builder $linked) {
                        $linked->selectRaw('1')
                            ->from('finance_transactions')
                            ->whereColumn('finance_transactions.receipt_submission_id', 'receipt_submissions.id');
                    });
                }
            });
    }

    private function whereNormalizedReference(Builder $query, string $column, string $reference): Builder
    {
        $driver = DB::connection()->getDriverName();
        $expression = $driver === 'mysql'
            ? "LOWER(REGEXP_REPLACE({$column}, '[^A-Za-z0-9]', ''))"
            : "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE({$column}, '-', ''), ' ', ''), '_', ''), '/', ''), '.', ''))";

        return $query->whereRaw("{$expression} = ?", [$reference]);
    }

    private function duplicate(string $code, array $match): array
    {
        return [
            'duplicate' => true,
            'code' => $code,
            'message' => $code === 'DUPLICATE_RECEIPT_IMAGE'
                ? 'This receipt image is already connected to an existing payment.'
                : 'This transaction/reference number is already connected to an existing payment.',
            'match' => $match,
        ];
    }

    private function match(string $source, object $record): array
    {
        return [
            'source' => $source,
            'record_id' => (int) $record->id,
            'status' => isset($record->status) ? (string) $record->status : null,
        ];
    }
}
