<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReminderMail;

class SendPaymentReminders extends Command
{
    protected $signature = 'email:send-payment-reminder 
                            {--send : Actually dispatch emails to all parents and students} 
                            {--preview= : Send a single test email to this address for preview}
                            {--force : Skip confirmation prompt for non-interactive scripts}';

    protected $description = 'Stage, preview, or dispatch the monthly payment reminder email to active students and their parents.';

    public function handle()
    {
        // 1. Query unique parent emails of active students
        $parentEmails = DB::table('students')
            ->join('enrollment_applicants', 'students.enrollment_applicant_id', '=', 'enrollment_applicants.id')
            ->whereNotNull('enrollment_applicants.parent_email')
            ->where('enrollment_applicants.parent_email', '<>', '')
            ->distinct()
            ->pluck('enrollment_applicants.parent_email')
            ->toArray();

        // 2. Query unique student emails of active students
        $studentEmails = DB::table('students')
            ->whereNotNull('school_email')
            ->where('school_email', '<>', '')
            ->distinct()
            ->pluck('school_email')
            ->toArray();

        $allEmails = array_values(array_unique(array_filter(array_merge($parentEmails, $studentEmails))));

        $totalParents = count($parentEmails);
        $totalStudents = count($studentEmails);
        $totalRecipients = count($allEmails);

        $this->info("==================================================");
        $this->info("      AL MUNAWWARA MONTHLY PAYMENT REMINDER      ");
        $this->info("==================================================");
        $this->info("Active Student Records Filter Applied:");
        $this->info("  - Unique Parent Emails: {$totalParents}");
        $this->info("  - Unique Student Emails: {$totalStudents}");
        $this->info("  - Total Unique Recipients: {$totalRecipients}");
        $this->info("==================================================");

        // 3. Handle Preview Mode
        $previewAddress = $this->option('preview');
        if ($previewAddress) {
            $this->info("Sending test/preview email to: {$previewAddress}...");
            try {
                Mail::to($previewAddress)->send(new PaymentReminderMail());
                $this->info("SUCCESS: Test email successfully sent to {$previewAddress}!");
            } catch (\Exception $e) {
                $this->error("ERROR sending preview email: " . $e->getMessage());
            }
            return Command::SUCCESS;
        }

        // 4. Handle Actual Sending
        $actuallySend = $this->option('send');
        if ($actuallySend) {
            if ($totalRecipients === 0) {
                $this->warn("No recipient emails found to send to.");
                return Command::FAILURE;
            }

            if (!$this->option('force')) {
                if (!$this->confirm("Are you absolutely sure you want to send this payment reminder to ALL {$totalRecipients} recipients?")) {
                    $this->info("Operation cancelled.");
                    return Command::SUCCESS;
                }
            }

            $this->info("Starting bulk email dispatch...");
            $successCount = 0;
            $failCount = 0;

            foreach ($allEmails as $email) {
                try {
                    Mail::to($email)->send(new PaymentReminderMail());
                    $this->info("Sent reminder to: {$email}");
                    $successCount++;
                } catch (\Exception $e) {
                    $this->error("Failed to send to {$email}: " . $e->getMessage());
                    $failCount++;
                }
            }

            $this->info("--------------------------------------------------");
            $this->info("Bulk Dispatch Completed!");
            $this->info("Success: {$successCount} | Failed: {$failCount}");
            $this->info("--------------------------------------------------");
            return Command::SUCCESS;
        }

        // 5. Default: Dry Run Mode
        $this->warn("\n*** CURRENTLY IN DRY-RUN / PREVIEW MODE ***");
        $this->info("No emails were dispatched to parents/students (wag muna send).");
        $this->info("To send a test copy to yourself/finance to see how it looks, run:");
        $this->comment("  php artisan email:send-payment-reminder --preview=your-email@example.com");
        $this->info("\nTo dispatch this reminder to all {$totalRecipients} recipients, run:");
        $this->comment("  php artisan email:send-payment-reminder --send");
        $this->info("==================================================");

        return Command::SUCCESS;
    }
}
