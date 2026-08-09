<?php

namespace App\Console\Commands;

use App\Services\Microsoft\MailCleanupService;
use Illuminate\Console\Command;

class AmisMailScanCommand extends Command
{
    protected $signature = 'amis:mail-scan
        {--user= : Target specific @amis.edu.ph mailbox UPN}
        {--faculty-only : Process faculty / staff mailboxes only}
        {--students-only : Process student mailboxes only}
        {--limit=50 : Maximum inbox messages to scan per mailbox}
        {--older-than= : Only scan messages older than N days}
        {--samples=0 : Display N random sample matched messages}';

    protected $description = 'Perform DRY RUN scanner for unnecessary Teams/Class notification emails across @amis.edu.ph mailboxes.';

    public function handle(MailCleanupService $service): int
    {
        $user = $this->option('user');
        $facultyOnly = (bool) $this->option('faculty-only');
        $studentsOnly = (bool) $this->option('students-only');
        $limit = (int) $this->option('limit');
        $olderThan = $this->option('older-than') ? (int) $this->option('older-than') : null;
        $samplesCount = (int) $this->option('samples');

        $filterType = null;
        if ($facultyOnly) {
            $filterType = 'faculty';
        } elseif ($studentsOnly) {
            $filterType = 'students';
        }

        $this->info('========================================================================');
        $this->info(' 🔍 AMIS MICROSOFT 365 MAIL CLEANUP SCANNER');
        $this->info(' MODE: DRY RUN (NO EMAILS WERE MOVED OR DELETED)');
        $this->info('========================================================================');

        $this->info('Scanning Inbox messages...');
        $report = $service->scan($user, $filterType, $limit, $olderThan, $samplesCount);

        $this->info('');
        $this->info("Batch ID: {$report['batch_id']}");
        $this->info("CSV Log File: {$report['csv_log_path']}");
        $this->info('------------------------------------------------------------------------');

        foreach ($report['mailbox_summaries'] as $summary) {
            $this->line("📬 <fg=cyan>{$summary['mailbox']}</>");
            $this->line("   - Inbox messages scanned: {$summary['inspected']}");
            $this->line("   - Teams/Class notification candidates: <fg=yellow>{$summary['candidates']}</>");
            if ($summary['protected'] > 0) {
                $this->line("   - Protected emails (kept): <fg=green>{$summary['protected']}</>");
            }
        }

        $this->info('------------------------------------------------------------------------');
        $this->info("Total Mailboxes Scanned: {$report['total_mailboxes']}");
        $this->info("Total Messages Inspected: {$report['total_inspected']}");
        $this->info("Total Cleanup Candidates: {$report['total_candidates']}");
        $this->info("Total Protected Messages: {$report['total_protected']}");
        $this->info('------------------------------------------------------------------------');
        $this->info('NO EMAILS WERE DELETED OR MOVED. (DRY RUN ONLY)');

        // Display sample matched messages if requested
        if (! empty($report['samples'])) {
            $this->info('');
            $this->info("========================================================================");
            $this->info(" 👁️ SAMPLE MATCHED MESSAGES (Showing ".count($report['samples'])." samples):");
            $this->info("========================================================================");

            foreach ($report['samples'] as $idx => $s) {
                $num = $idx + 1;
                $this->line("<fg=yellow>[Sample #{$num}]</> Mailbox: <fg=cyan>{$s['mailbox']}</>");
                $this->line("   Received Date: {$s['received_date']}");
                $this->line("   Sender: {$s['sender']}");
                $this->line("   Subject: \"{$s['subject']}\"");
                $this->line("   Matched Rule: <fg=magenta>{$s['matched_rule']}</>");
                $this->line("   Proposed Action: <fg=green>{$s['proposed_action']}</>");
                $this->line("");
            }
        }

        return 0;
    }
}
