<?php

namespace App\Services\System;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SmartSmtpRotatorService
{
    protected int $dailyLimit = 450; // Daily send limit per SMTP account before auto-switching

    /**
     * Get pool of configured SMTP mailer names.
     */
    public function getMailerPool(): array
    {
        $allMailers = ['smtp', 'smtp_backup', 'smtp_backup_2', 'inquiries'];
        $configured = [];

        foreach ($allMailers as $mailer) {
            $host = config("mail.mailers.{$mailer}.host");
            $user = config("mail.mailers.{$mailer}.username");
            if (filled($host) && filled($user)) {
                $configured[] = $mailer;
            }
        }

        // Fallback to default mailer if no additional mailers configured
        if (empty($configured)) {
            $configured[] = config('mail.default', 'smtp');
        }

        return array_unique($configured);
    }

    /**
     * Get current daily send count for a specific mailer.
     */
    public function getDailyCount(string $mailer): int
    {
        $key = 'smtp_daily_count_' . $mailer . '_' . date('Y-m-d');
        return (int) Cache::get($key, 0);
    }

    /**
     * Increment daily send count for a specific mailer.
     */
    public function incrementDailyCount(string $mailer): void
    {
        $key = 'smtp_daily_count_' . $mailer . '_' . date('Y-m-d');
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, now()->endOfDay());
    }

    /**
     * Send email with automatic failover and daily rate-limit switching.
     */
    public function sendMail(string|array $to, Mailable $mailable): array
    {
        $pool = $this->getMailerPool();
        $lastException = null;

        foreach ($pool as $mailer) {
            $count = $this->getDailyCount($mailer);

            // Skip mailers that reached daily quota
            if ($count >= $this->dailyLimit) {
                Log::warning("SmartSmtpRotator: Mailer '{$mailer}' reached daily limit of {$this->dailyLimit} sends. Auto-switching to next SMTP.");
                continue;
            }

            try {
                Mail::mailer($mailer)->to($to)->send($mailable);
                $this->incrementDailyCount($mailer);

                Log::info("SmartSmtpRotator: Email sent successfully via mailer '{$mailer}' (Daily count: " . ($count + 1) . ")");
                return [
                    'success' => true,
                    'mailer_used' => $mailer,
                    'daily_count' => $count + 1,
                    'message' => "Email sent successfully using SMTP account ({$mailer}).",
                ];
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::error("SmartSmtpRotator: Mailer '{$mailer}' failed: " . $e->getMessage() . ". Switching to backup SMTP...");
            }
        }

        // Final fallback: try default mailer or throw
        $errorMsg = $lastException ? $lastException->getMessage() : 'All configured SMTP mailers have reached their daily limit.';
        Log::error("SmartSmtpRotator: All SMTP failover attempts exhausted. Error: {$errorMsg}");

        throw new \Exception("SMTP Failover Failed: {$errorMsg}");
    }

    /**
     * Get pool metrics for System Health dashboard display.
     */
    public function getPoolMetrics(): array
    {
        $pool = $this->getMailerPool();
        $metrics = [];
        $activeMailer = null;

        foreach ($pool as $mailer) {
            $user = config("mail.mailers.{$mailer}.username") ?: config('mail.from.address');
            $host = config("mail.mailers.{$mailer}.host") ?: 'Default SMTP';
            $count = $this->getDailyCount($mailer);
            $isLimitReached = $count >= $this->dailyLimit;

            if (!$activeMailer && !$isLimitReached) {
                $activeMailer = $mailer;
            }

            $metrics[] = [
                'mailer' => $mailer,
                'username' => $user,
                'host' => $host,
                'daily_count' => $count,
                'daily_limit' => $this->dailyLimit,
                'limit_reached' => $isLimitReached,
                'status' => $isLimitReached ? 'quota_exceeded' : 'active',
            ];
        }

        return [
            'active_mailer' => $activeMailer ?? ($pool[0] ?? 'smtp'),
            'pool_count' => count($pool),
            'mailers' => $metrics,
        ];
    }
}
