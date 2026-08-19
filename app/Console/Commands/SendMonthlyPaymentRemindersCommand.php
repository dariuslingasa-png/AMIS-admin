<?php

namespace App\Console\Commands;

use App\Services\Finance\MonthlyPaymentReminderService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMonthlyPaymentRemindersCommand extends Command
{
    protected $signature = 'reminders:send-monthly 
                            {month? : Billing month in YYYY-MM format, defaults to current month} 
                            {--force : Force resend to all families, including those already sent}';

    protected $description = 'Dispatch monthly payment reminder emails for all eligible families.';

    public function handle(MonthlyPaymentReminderService $service): int
    {
        $month = $this->argument('month') ?: Carbon::now()->format('Y-m');
        $force = (bool) $this->option('force');

        $this->info("Dispatching monthly payment reminders for month: {$month}" . ($force ? " (FORCE RESEND ALL)" : ""));

        $result = $service->dispatchMonthlyReminders($month, null, $force);

        $this->info("✓ Dispatched / Queued: {$result['dispatched']} reminders");
        if ($result['skipped_already_sent'] > 0) {
            $this->warn("  Skipped (Already Sent): {$result['skipped_already_sent']}");
        }
        if ($result['skipped_invalid'] > 0) {
            $this->warn("  Skipped (Invalid Email): {$result['skipped_invalid']}");
        }

        return Command::SUCCESS;
    }
}
