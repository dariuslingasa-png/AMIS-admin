<?php

namespace App\Console\Commands;

use App\Services\Microsoft\MailCleanupService;
use Illuminate\Console\Command;

class AmisMailCleanupCommand extends Command
{
    protected $signature = 'amis:mail-cleanup
        {--user= : Target specific @amis.edu.ph mailbox UPN}
        {--faculty-only : Process faculty / staff mailboxes only}
        {--students-only : Process student mailboxes only}
        {--limit=50 : Maximum inbox messages to inspect per mailbox}
        {--older-than= : Only process messages older than N days}
        {--execute : Required flag to execute live move to Deleted Items}';

    protected $description = 'Perform reversible inbox cleanup by moving matched unnecessary notifications to Deleted Items.';

    public function handle(MailCleanupService $service): int
    {
        $hasExecuteFlag = (bool) $this->option('execute');
        $envMailCleanupEnabled = filter_var(env('MAIL_CLEANUP_ENABLED', false), FILTER_VALIDATE_BOOLEAN) || (bool) config('services.microsoft.mail_cleanup_enabled', false);
        $envDryRunDisabled = filter_var(env('DRY_RUN', true), FILTER_VALIDATE_BOOLEAN) === false || (bool) config('services.microsoft.mail_cleanup_dry_run_disabled', false);

        // Triple Guardrail check
        $isLiveExecution = $hasExecuteFlag && $envMailCleanupEnabled && $envDryRunDisabled;

        $this->info('========================================================================');
        $this->info(' 🧹 AMIS MICROSOFT 365 REVERSIBLE MAIL CLEANUP ENGINE');
        $this->info('========================================================================');

        if (! $isLiveExecution) {
            $this->error('❌ CLEANUP ABORTED: Live execution requirements not met.');
            $this->line('');
            $this->line('To enable live move to Deleted Items, you MUST fulfill ALL THREE conditions:');
            $this->line('  1. Set DRY_RUN=false in .env');
            $this->line('  2. Set MAIL_CLEANUP_ENABLED=true in .env');
            $this->line('  3. Pass the --execute flag on CLI');
            $this->line('');
            $this->line('Current status:');
            $this->line('  - --execute flag: '.($hasExecuteFlag ? 'YES' : 'NO'));
            $this->line('  - MAIL_CLEANUP_ENABLED=true: '.($envMailCleanupEnabled ? 'YES' : 'NO'));
            $this->line('  - DRY_RUN=false: '.($envDryRunDisabled ? 'YES' : 'NO'));
            $this->line('');
            $this->info('Use "php artisan amis:mail-scan" to run a dry-run report.');

            return 1;
        }

        $user = $this->option('user');
        $facultyOnly = (bool) $this->option('faculty-only');
        $studentsOnly = (bool) $this->option('students-only');
        $limit = (int) $this->option('limit');
        $olderThan = $this->option('older-than') ? (int) $this->option('older-than') : null;

        $filterType = null;
        if ($facultyOnly) {
            $filterType = 'faculty';
        } elseif ($studentsOnly) {
            $filterType = 'students';
        }

        $this->warn('⚠️ LIVE CLEANUP INITIATED: Moving matched notification emails to "Deleted Items".');
        $this->warn('⚠️ NON-DESTRUCTIVE: All moved emails remain 100% recoverable in Outlook Trash.');

        $report = $service->cleanup($user, $filterType, $limit, $olderThan);

        $this->info('');
        $this->info("Batch ID: {$report['batch_id']}");
        $this->info("CSV Audit Log File: {$report['csv_log_path']}");
        $this->info('------------------------------------------------------------------------');

        foreach ($report['mailbox_summaries'] as $summary) {
            $this->line("📬 <fg=cyan>{$summary['mailbox']}</>");
            $this->line("   - Inbox messages inspected: {$summary['inspected']}");
            $this->line("   - Matched candidates: {$summary['candidates']}");
            $this->line("   - Successfully moved to Deleted Items: <fg=green>{$summary['moved']}</>");
            if ($summary['failed'] > 0) {
                $this->line("   - Move errors: <fg=red>{$summary['failed']}</>");
            }
        }

        $this->info('------------------------------------------------------------------------');
        $this->info("Total Mailboxes Processed: {$report['total_mailboxes']}");
        $this->info("Total Messages Inspected: {$report['total_inspected']}");
        $this->info("Total Notification Candidates: {$report['total_candidates']}");
        $this->info("Total Successfully Moved to Deleted Items: {$report['total_moved']}");
        if ($report['total_failed'] > 0) {
            $this->error("Total Move Failures: {$report['total_failed']}");
        }
        $this->info('========================================================================');

        return 0;
    }
}
