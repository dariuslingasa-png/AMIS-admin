<?php

namespace App\Jobs;

use App\Mail\FinancePaymentApprovedAdviserMail;
use App\Mail\FinancePaymentApprovedParentMail;
use App\Models\FinanceParentNotification;
use App\Services\System\SmartSmtpRotatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendFinancePaymentNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly int $notificationId)
    {
        // Keep Admin Finance mail away from the shared default queue. The
        // payment portal also consumes that queue but does not contain this
        // Admin-only job class, which leaves notifications undelivered.
        $this->onQueue('admin-finance');
        $this->afterCommit();
    }

    public function handle(SmartSmtpRotatorService $smtp): void
    {
        $notification = FinanceParentNotification::query()->with([
            'user',
            'transaction.family',
            'transaction.family.students.applicant',
            'transaction.family.students.user',
            'transaction.officialReceipt',
            'transaction.allocations.student.applicant',
            'transaction.allocations.student.user',
            'transaction.allocations.monthlyBilling',
        ])->findOrFail($this->notificationId);
        $payload = $notification->payload;

        if ($notification->type === 'PAYMENT_REVERSED') {
            Mail::raw(
                "Assalamu Alaikum {$notification->user->name},\n\n".
                ($payload['message'] ?? 'Your family payment record was updated by AMIS Support Staff.')."\n\n".
                "Official Receipt No.: {$notification->transaction->official_receipt_number}\n".
                "Payment Reference: ".($notification->transaction->reference_number ?: 'Not applicable')."\n".
                'Amount: PHP '.number_format((float) $notification->transaction->amount, 2)."\n".
                'Status: '.$notification->transaction->status,
                fn ($mail) => $mail->to($notification->user->email)->subject('AMIS Support Staff payment reversal notice')
            );
            $notification->update(['status' => 'SENT', 'sent_at' => now(), 'error' => null]);

            return;
        }

        if (! config('services.finance_notifications.enabled', true)) {
            $notification->update(['status' => 'SKIPPED', 'sent_at' => now(), 'error' => null]);

            return;
        }

        $deliveries = (array) ($payload['deliveries'] ?? []);
        $parentEmail = trim((string) $notification->user?->email);
        if (! filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('The parent account does not have a valid email address.');
        }

        if (empty($deliveries['parent']['sent_at'])) {
            $result = $smtp->sendMail($parentEmail, new FinancePaymentApprovedParentMail($notification->transaction));
            $deliveries['parent'] = [
                'email' => $parentEmail,
                'sent_at' => now()->toIso8601String(),
                'mailer' => $result['mailer_used'] ?? config('mail.default'),
            ];
            $payload['deliveries'] = $deliveries;
            $notification->update(['payload' => $payload, 'error' => null]);
        }

        $adviserEmail = trim((string) config('services.finance_notifications.adviser_email'));
        $settledAllocations = collect($notification->transaction->allocation_snapshot)
            ->filter(fn ($row) => (float) ($row['remaining_after'] ?? 0) <= 0);
        if ($settledAllocations->isEmpty()) {
            $deliveries['adviser'] = ['status' => 'SKIPPED', 'reason' => 'No student billing was fully settled.'];
        } elseif (! filter_var($adviserEmail, FILTER_VALIDATE_EMAIL)) {
            $deliveries['adviser'] = ['status' => 'SKIPPED', 'reason' => 'No valid adviser email is configured.'];
        } elseif (empty($deliveries['adviser']['sent_at'])) {
            $result = $smtp->sendMail($adviserEmail, new FinancePaymentApprovedAdviserMail($notification->transaction));
            $deliveries['adviser'] = [
                'email' => $adviserEmail,
                'sent_at' => now()->toIso8601String(),
                'mailer' => $result['mailer_used'] ?? config('mail.default'),
            ];
        }

        $payload['deliveries'] = $deliveries;

        $notification->update(['payload' => $payload, 'status' => 'SENT', 'sent_at' => now(), 'error' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        FinanceParentNotification::query()->whereKey($this->notificationId)->update([
            'status' => 'FAILED',
            'error' => $exception?->getMessage(),
        ]);
    }
}
