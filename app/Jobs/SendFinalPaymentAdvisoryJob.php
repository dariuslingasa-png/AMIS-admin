<?php

namespace App\Jobs;

use App\Mail\FinalPaymentAdvisoryMail;
use App\Models\MonthlyPaymentReminder;
use App\Services\System\SmartSmtpRotatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFinalPaymentAdvisoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const REMINDER_TYPE = 'final_payment_advisory';

    public int $tries = 10;

    public int $timeout = 120;

    public function __construct(public int $reminderId)
    {
    }

    public function handle(SmartSmtpRotatorService $rotator): void
    {
        $reminder = DB::transaction(function () {
            $record = MonthlyPaymentReminder::query()
                ->whereKey($this->reminderId)
                ->lockForUpdate()
                ->first();

            if (! $record || $record->reminder_type !== self::REMINDER_TYPE) {
                return null;
            }

            if ($record->status === MonthlyPaymentReminder::STATUS_SENT) {
                return null;
            }

            $record->update([
                'status' => MonthlyPaymentReminder::STATUS_PROCESSING,
                'attempts' => $record->attempts + 1,
                'last_attempt_at' => now(),
                'next_retry_at' => null,
            ]);

            return $record->fresh();
        });

        if (! $reminder) {
            return;
        }

        $email = strtolower(trim((string) $reminder->parent_email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $reminder->update([
                'status' => MonthlyPaymentReminder::STATUS_SKIPPED_NO_EMAIL,
                'last_error' => "Invalid email format: {$reminder->parent_email}",
            ]);

            return;
        }

        try {
            $name = trim((string) ($reminder->student_names ?: $reminder->parent_name));
            $name = preg_replace('/\s*\([^)]*\)/', '', $name) ?: 'Valued Family';
            $name = trim(explode(',', $name)[0]);
            $dispatchRef = 'ADV-'.$reminder->id.'-'.now()->format('Ymd');
            $result = $rotator->sendMail($email, new FinalPaymentAdvisoryMail($name, $dispatchRef));

            $reminder->update([
                'status' => MonthlyPaymentReminder::STATUS_SENT,
                'sent_at' => now(),
                'mail_transport' => $result['mailer_used'] ?? 'smtp',
                'last_error' => null,
                'next_retry_at' => null,
            ]);

            Log::info("FinalPaymentAdvisory: #{$reminder->id} accepted for {$email} via {$result['mailer_used']}.");
        } catch (Throwable $exception) {
            $this->handleSendFailure($reminder, $exception);
        }
    }

    private function handleSendFailure(MonthlyPaymentReminder $reminder, Throwable $exception): void
    {
        $message = substr($exception->getMessage(), 0, 500);
        $lower = strtolower($message);
        $delaySeconds = null;
        $nextRetryAt = null;

        if (str_contains($lower, 'daily limit') || str_contains($lower, 'daily quota')) {
            $nextRetryAt = now()->addDay()->startOfDay()->addMinutes(10);
            $delaySeconds = max(60, now()->diffInSeconds($nextRetryAt));
        } elseif (str_contains($lower, 'max emails per hour') || str_contains($lower, 'hourly sending limit')) {
            $nextRetryAt = now()->addHour()->addMinutes(10);
            $delaySeconds = 4200;
        } elseif (str_contains($lower, 'too many messages') || str_contains($lower, 'too many login attempts') || str_contains($lower, 'temporarily')) {
            $nextRetryAt = now()->addMinutes(20);
            $delaySeconds = 1200;
        } elseif ($this->attempts() < $this->tries) {
            $delaySeconds = min(21600, 900 * max(1, $this->attempts()));
            $nextRetryAt = now()->addSeconds($delaySeconds);
        }

        $terminal = $delaySeconds === null;
        $reminder->update([
            'status' => $terminal ? MonthlyPaymentReminder::STATUS_FAILED : MonthlyPaymentReminder::STATUS_RETRY,
            'last_error' => $message,
            'next_retry_at' => $nextRetryAt,
        ]);

        Log::warning("FinalPaymentAdvisory: #{$reminder->id} send failed; ".($terminal ? 'terminal' : 'retry scheduled').": {$message}");

        if (! $terminal) {
            $this->release($delaySeconds);
        }
    }

    public function failed(Throwable $exception): void
    {
        MonthlyPaymentReminder::query()
            ->whereKey($this->reminderId)
            ->where('status', '!=', MonthlyPaymentReminder::STATUS_SENT)
            ->update([
                'status' => MonthlyPaymentReminder::STATUS_FAILED,
                'last_error' => substr($exception->getMessage(), 0, 500),
            ]);
    }
}
