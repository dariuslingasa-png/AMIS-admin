<?php

namespace App\Jobs;

use App\Mail\GenericComposerMailable;
use App\Models\AdminAuditLog;
use App\Models\BulkEmailCampaign;
use App\Models\EmailLog;
use App\Services\Email\EmailComposerService;
use App\Services\System\SmartSmtpRotatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkEmailCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes max execution time for bulk sending

    public function __construct(
        public int $campaignId
    ) {}

    public function handle(
        EmailComposerService $composerService,
        SmartSmtpRotatorService $rotatorService
    ): void {
        $campaign = BulkEmailCampaign::find($this->campaignId);
        if (!$campaign) {
            Log::error("SendBulkEmailCampaignJob: Campaign ID {$this->campaignId} not found.");
            return;
        }

        $campaign->update(['status' => 'sending']);

        $recipients = $composerService->resolveRecipients(
            $campaign->recipient_type,
            $campaign->recipient_filter
        );

        $totalRecipients = count($recipients);
        $campaign->update(['recipient_count' => $totalRecipients]);

        if ($totalRecipients === 0) {
            $campaign->update([
                'status' => 'failed',
                'error_log' => 'No valid email recipients found for the selected filter target.',
            ]);
            return;
        }

        $sentCount = 0;
        $failedCount = 0;
        $attachmentPaths = $campaign->attachments_json ?: [];

        foreach ($recipients as $recipientName => $recipientEmail) {
            if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                $failedCount++;
                continue;
            }

            try {
                $mailable = new GenericComposerMailable(
                    customSubject: $campaign->subject,
                    bodyHtml: $campaign->body_html,
                    attachmentPaths: $attachmentPaths
                );

                $res = $rotatorService->sendMail($recipientEmail, $mailable);
                $sentCount++;

                // Record in email_logs table
                EmailLog::create([
                    'mailer' => $res['mailer_used'] ?? 'smtp',
                    'transport' => 'smtp',
                    'from_address' => config('mail.from.address'),
                    'to_addresses' => $recipientEmail,
                    'subject' => $campaign->subject,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $failedCount++;
                Log::error("SendBulkEmailCampaignJob: Failed sending to {$recipientEmail}: " . $e->getMessage());

                EmailLog::create([
                    'mailer' => 'smtp',
                    'transport' => 'smtp',
                    'from_address' => config('mail.from.address'),
                    'to_addresses' => $recipientEmail,
                    'subject' => $campaign->subject,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_at' => now(),
                ]);
            }

            // Periodically update campaign progress
            if (($sentCount + $failedCount) % 5 === 0) {
                $campaign->update([
                    'sent_count' => $sentCount,
                    'failed_count' => $failedCount,
                ]);
            }
        }

        $finalStatus = ($sentCount > 0) ? 'completed' : 'failed';
        $campaign->update([
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'status' => $finalStatus,
        ]);

        AdminAuditLog::record(
            'bulk_email_campaign_completed',
            $sentCount > 0,
            "Bulk Email Campaign '{$campaign->title}' completed. Sent: {$sentCount}, Failed: {$failedCount}.",
            ['campaign_id' => $campaign->id, 'sent' => $sentCount, 'failed' => $failedCount]
        );
    }
}
