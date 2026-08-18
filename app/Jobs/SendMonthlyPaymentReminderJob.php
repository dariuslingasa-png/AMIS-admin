<?php

namespace App\Jobs;

use App\Mail\PaymentReminderMail;
use App\Models\MonthlyPaymentReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendMonthlyPaymentReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Max execution attempts.
     */
    public int $tries = 5;

    /**
     * Max execution time in seconds.
     */
    public int $timeout = 60;

    /**
     * Retry backoffs in seconds: 15m, 30m, 1h, 3h, 6h
     */
    public array $backoff = [900, 1800, 3600, 10800, 21600];

    public function __construct(
        public int $reminderId
    ) {
    }

    public function handle(): void
    {
        // ── PESSIMISTIC LOCKING TO ENSURE ZERO DUPLICATE SENDS ───────────────
        $reminder = DB::transaction(function () {
            return MonthlyPaymentReminder::where('id', $this->reminderId)
                ->lockForUpdate()
                ->first();
        });

        if (!$reminder) {
            Log::warning("SendMonthlyPaymentReminderJob: Reminder #{$this->reminderId} not found.");
            return;
        }

        // ── STRICT IDEMPOTENCY GUARD ─────────────────────────────────────────
        // If already SENT or SKIPPED, NEVER resend.
        if (in_array($reminder->status, [
            MonthlyPaymentReminder::STATUS_SENT,
            MonthlyPaymentReminder::STATUS_SKIPPED_ALREADY_SENT,
            MonthlyPaymentReminder::STATUS_SKIPPED_FULLY_PAID,
            MonthlyPaymentReminder::STATUS_SKIPPED_NO_EMAIL,
        ], true)) {
            Log::info("SendMonthlyPaymentReminderJob: Skipping #{$reminder->id} ({$reminder->parent_email}) — status is already {$reminder->status}.");
            return;
        }

        // Validate email format
        $email = trim(strtolower((string) $reminder->parent_email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $reminder->update([
                'status'     => MonthlyPaymentReminder::STATUS_SKIPPED_NO_EMAIL,
                'last_error' => "Invalid email format: {$reminder->parent_email}",
            ]);
            return;
        }

        // Mark as PROCESSING
        $reminder->update([
            'status'          => MonthlyPaymentReminder::STATUS_PROCESSING,
            'attempts'        => $reminder->attempts + 1,
            'last_attempt_at' => now(),
        ]);

        try {
            $mailable = new PaymentReminderMail(
                parentName: $reminder->parent_name,
                studentNames: $reminder->student_names
            );

            Mail::to($email)->send($mailable);

            // ── SUCCESS: TERMINAL STATE ──────────────────────────────────────
            $reminder->update([
                'status'         => MonthlyPaymentReminder::STATUS_SENT,
                'sent_at'        => now(),
                'mail_transport' => config('mail.default', 'smtp'),
                'last_error'     => null,
                'next_retry_at'  => null,
            ]);

            Log::info("SendMonthlyPaymentReminderJob: Reminder #{$reminder->id} sent successfully to {$email} for month {$reminder->billing_month}.");

        } catch (Throwable $e) {
            $errorMsg = substr($e->getMessage(), 0, 500);
            Log::error("SendMonthlyPaymentReminderJob: Failed to send to {$email} (Attempt #{$reminder->attempts}): {$errorMsg}");

            $isTerminal = $this->attempts() >= $this->tries;

            $nextRetry = null;
            if (!$isTerminal) {
                $backoffSeconds = $this->backoff[$this->attempts() - 1] ?? 3600;
                $nextRetry = now()->addSeconds($backoffSeconds);
            }

            $reminder->update([
                'status'        => $isTerminal ? MonthlyPaymentReminder::STATUS_FAILED : MonthlyPaymentReminder::STATUS_RETRY,
                'last_error'    => $errorMsg,
                'next_retry_at' => $nextRetry,
            ]);

            if (!$isTerminal) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 900);
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        MonthlyPaymentReminder::where('id', $this->reminderId)->update([
            'status'     => MonthlyPaymentReminder::STATUS_FAILED,
            'last_error' => substr($exception->getMessage(), 0, 500),
        ]);
    }
}
