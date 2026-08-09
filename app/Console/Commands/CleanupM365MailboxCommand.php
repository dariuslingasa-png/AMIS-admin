<?php

namespace App\Console\Commands;

use App\Models\M365MailCleanupLog;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupM365MailboxCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'm365:cleanup-inbox
        {--mailbox= : Target specific @amis.edu.ph mailbox UPN}
        {--faculty-only : Process all faculty @amis.edu.ph mailboxes}
        {--all-accounts : Process all @amis.edu.ph mailboxes}
        {--execute : Perform live move to Deleted Items folder}
        {--limit=50 : Maximum messages to inspect per mailbox}';

    /**
     * The console command description.
     */
    protected $description = 'Reversibly clean up unnecessary Teams/Class notification emails by moving them to Deleted Items (Outlook Trash).';

    public function handle(MicrosoftGraphService $graph): int
    {
        $hasExecuteFlag = (bool) $this->option('execute');
        $envMailCleanupEnabled = filter_var(env('MAIL_CLEANUP_ENABLED', false), FILTER_VALIDATE_BOOLEAN) || (bool) config('services.microsoft.mail_cleanup_enabled', false);
        $envDryRunDisabled = filter_var(env('DRY_RUN', true), FILTER_VALIDATE_BOOLEAN) === false || (bool) config('services.microsoft.mail_cleanup_dry_run_disabled', false);

        // Triple Guardrail check: Require ALL THREE for live move
        $isLiveExecution = $hasExecuteFlag && $envMailCleanupEnabled && $envDryRunDisabled;
        $isDryRun = ! $isLiveExecution;

        $modeLabel = $isLiveExecution ? 'LIVE MOVE (REVERSIBLE)' : 'DRY RUN (SAFETY MODE)';

        $this->info('========================================================================');
        $this->info(" 🛡️ MICROSOFT 365 INBOX CLEANUP ENGINE — [{$modeLabel}]");
        $this->info('========================================================================');

        if ($isLiveExecution) {
            $this->warn('⚠️ LIVE EXECUTION CONFIRMED: Matched emails will be moved to "Deleted Items" folder.');
            $this->warn('⚠️ NON-DESTRUCTIVE: All messages remain 100% recoverable in Outlook Trash.');
        } else {
            $this->info('ℹ️ DRY RUN MODE: No messages will be moved. Displaying matched messages only.');
            if ($hasExecuteFlag && (! $envMailCleanupEnabled || ! $envDryRunDisabled)) {
                $this->warn('⚠️ --execute flag was passed, but DRY_RUN=false AND MAIL_CLEANUP_ENABLED=true are required in .env for live moves.');
            }
        }

        // Determine target mailboxes
        $targetMailboxes = $this->determineMailboxes();

        if (empty($targetMailboxes)) {
            $this->error('No valid @amis.edu.ph mailboxes selected. Specify --mailbox=user@amis.edu.ph or use --faculty-only / --all-accounts.');

            return 1;
        }

        $this->info('Target Mailboxes Count: '.count($targetMailboxes));
        $this->info('------------------------------------------------------------------------');

        $limit = (int) $this->option('limit');
        $totalProcessed = 0;
        $totalMatched = 0;
        $totalMoved = 0;

        foreach ($targetMailboxes as $mailbox) {
            $this->line("📬 Inspecting Mailbox: <fg=cyan>{$mailbox}</>");

            try {
                $messages = $graph->getUserInboxMessages($mailbox, $limit);
            } catch (\Throwable $e) {
                $this->error("   ❌ Failed to fetch messages for {$mailbox}: ".$e->getMessage());

                M365MailCleanupLog::create([
                    'mailbox' => $mailbox,
                    'message_id' => 'N/A',
                    'sender' => 'N/A',
                    'subject' => 'N/A',
                    'original_folder' => 'Inbox',
                    'destination_folder' => 'Deleted Items',
                    'timestamp' => now(),
                    'matched_rule' => 'FETCH_ERROR',
                    'result' => 'ERROR',
                    'error_message' => $e->getMessage(),
                ]);

                continue;
            }

            if (empty($messages)) {
                $this->line('   ℹ️ Inbox is clean / no messages returned.');

                continue;
            }

            foreach ($messages as $msg) {
                $totalProcessed++;
                $msgId = $msg['id'] ?? '';
                $subject = $msg['subject'] ?? '(No Subject)';
                $senderData = $msg['sender']['emailAddress'] ?? ($msg['from']['emailAddress'] ?? []);
                $senderEmail = strtolower($senderData['address'] ?? '');
                $senderName = $senderData['name'] ?? '';

                $matchedRule = $this->matchCleanupRule($senderEmail, $subject);

                if (! $matchedRule) {
                    continue;
                }

                $totalMatched++;
                $timestamp = now();

                if ($isDryRun) {
                    $resultStatus = 'DRY_RUN_WOULD_MOVE';
                    $this->line("   [DRY RUN] <fg=yellow>WOULD MOVE</> | Sender: {$senderEmail} | Subject: \"{$subject}\" | Rule: {$matchedRule}");

                    Log::info("M365 Mail Cleanup [DRY RUN]: {$mailbox} -> Message '{$subject}' (Rule: {$matchedRule}) would be moved to Deleted Items.");

                    M365MailCleanupLog::create([
                        'mailbox' => $mailbox,
                        'message_id' => $msgId,
                        'sender' => $senderEmail,
                        'subject' => $subject,
                        'original_folder' => 'Inbox',
                        'destination_folder' => 'Deleted Items',
                        'timestamp' => $timestamp,
                        'matched_rule' => $matchedRule,
                        'result' => $resultStatus,
                    ]);
                } else {
                    // LIVE MOVE: Non-destructive move to Deleted Items via Graph API
                    try {
                        $moveResponse = $graph->moveMessageToDeletedItems($mailbox, $msgId);

                        if (($moveResponse['success'] ?? false) === true) {
                            $totalMoved++;
                            $resultStatus = 'MOVED';
                            $this->line("   [LIVE MOVE] <fg=green>MOVED TO DELETED ITEMS</> | Sender: {$senderEmail} | Subject: \"{$subject}\"");

                            Log::info("M365 Mail Cleanup [LIVE MOVE]: {$mailbox} -> Message '{$subject}' moved to Deleted Items.");

                            M365MailCleanupLog::create([
                                'mailbox' => $mailbox,
                                'message_id' => $msgId,
                                'sender' => $senderEmail,
                                'subject' => $subject,
                                'original_folder' => 'Inbox',
                                'destination_folder' => 'Deleted Items',
                                'timestamp' => $timestamp,
                                'matched_rule' => $matchedRule,
                                'result' => $resultStatus,
                            ]);
                        } else {
                            $resultStatus = 'ERROR';
                            $errMsg = $moveResponse['error'] ?? 'Unknown Graph API error';
                            $this->error("   [ERROR] Failed to move message: {$errMsg}");

                            M365MailCleanupLog::create([
                                'mailbox' => $mailbox,
                                'message_id' => $msgId,
                                'sender' => $senderEmail,
                                'subject' => $subject,
                                'original_folder' => 'Inbox',
                                'destination_folder' => 'Deleted Items',
                                'timestamp' => $timestamp,
                                'matched_rule' => $matchedRule,
                                'result' => 'ERROR',
                                'error_message' => $errMsg,
                            ]);
                        }
                    } catch (\Throwable $ex) {
                        $this->error("   [EXCEPTION] Failed to move message: ".$ex->getMessage());

                        M365MailCleanupLog::create([
                            'mailbox' => $mailbox,
                            'message_id' => $msgId,
                            'sender' => $senderEmail,
                            'subject' => $subject,
                            'original_folder' => 'Inbox',
                            'destination_folder' => 'Deleted Items',
                            'timestamp' => $timestamp,
                            'matched_rule' => $matchedRule,
                            'result' => 'ERROR',
                            'error_message' => $ex->getMessage(),
                        ]);
                    }
                }
            }
        }

        $this->info('========================================================================');
        $this->info(" SUMMARY RESULTS [{$modeLabel}]:");
        $this->info("  - Mailboxes Processed: ".count($targetMailboxes));
        $this->info("  - Messages Inspected: {$totalProcessed}");
        $this->info("  - Matched Notifications: {$totalMatched}");
        if ($isLiveExecution) {
            $this->info("  - Messages Moved to Deleted Items: {$totalMoved}");
        } else {
            $this->info("  - Messages Tagged for Move (Dry Run): {$totalMatched}");
        }
        $this->info('========================================================================');

        return 0;
    }

    /**
     * Determine list of mailboxes to target.
     */
    private function determineMailboxes(): array
    {
        if ($specificMailbox = $this->option('mailbox')) {
            return [trim($specificMailbox)];
        }

        $query = User::query();

        if ($this->option('faculty-only')) {
            $query->whereIn('role', ['teacher', 'faculty', 'admin']);
        }

        return $query->where('email', 'like', '%@amis.edu.ph')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Check if message matches unnecessary notification rule.
     */
    private function matchCleanupRule(string $senderEmail, string $subject): ?string
    {
        $sub = strtolower($subject);

        if (str_contains($senderEmail, 'noreply@microsoftteams.com') || str_contains($senderEmail, 'teams.microsoft.com')) {
            return 'TEAMS_SYSTEM_NOTIFICATION_SENDER';
        }

        $teamsKeywords = [
            'posted a message in',
            'added a channel',
            'mentioned you in teams',
            'reply to team',
            'class notification',
            'assignment due',
            'assignment created',
            'new assignment for',
            'teams activity',
        ];

        foreach ($teamsKeywords as $kw) {
            if (str_contains($sub, $kw)) {
                return 'TEAMS_CLASS_NOTIFICATION_SUBJECT ('.$kw.')';
            }
        }

        return null;
    }
}
