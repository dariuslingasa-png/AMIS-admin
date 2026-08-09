<?php

namespace App\Services\Microsoft;

use App\Models\M365MailCleanupLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MailCleanupLogger
{
    private string $batchId;

    private string $csvFilePath;

    public function __construct()
    {
        $this->batchId = 'CLEANUP-' . date('Ymd-His');
        $filename = "exports/m365_cleanup_report_" . date('Ymd_His') . ".csv";
        $this->csvFilePath = storage_path("app/{$filename}");

        $dir = dirname($this->csvFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Initialize CSV Header
        if (!file_exists($this->csvFilePath)) {
            file_put_contents($this->csvFilePath, "batch_id,timestamp,mailbox,message_id,received_date,sender,subject,matched_rule,proposed_action,result,error\n");
        }
    }

    public function getBatchId(): string
    {
        return $this->batchId;
    }

    public function getCsvFilePath(): string
    {
        return $this->csvFilePath;
    }

    /**
     * Log a single message cleanup event to Database and CSV.
     */
    public function logMessage(
        string $mailbox,
        string $messageId,
        string $receivedDate,
        string $sender,
        string $subject,
        string $matchedRule,
        string $proposedAction,
        string $result,
        ?string $errorMessage = null
    ): void {
        $timestamp = now();

        // 1. Database Log
        try {
            M365MailCleanupLog::create([
                'mailbox' => $mailbox,
                'message_id' => $messageId,
                'sender' => $sender,
                'subject' => $subject,
                'original_folder' => 'Inbox',
                'destination_folder' => 'Deleted Items',
                'timestamp' => $timestamp,
                'matched_rule' => $matchedRule,
                'result' => $result,
                'error_message' => $errorMessage,
            ]);
        } catch (\Throwable $e) {
            Log::error('MailCleanupLogger::logMessage DB insert failed: ' . $e->getMessage());
        }

        // 2. CSV Export Log
        try {
            $line = [
                $this->batchId,
                $timestamp->toIso8601String(),
                $this->sanitizeCsvField($mailbox),
                $this->sanitizeCsvField($messageId),
                $this->sanitizeCsvField($receivedDate),
                $this->sanitizeCsvField($sender),
                $this->sanitizeCsvField($subject),
                $this->sanitizeCsvField($matchedRule),
                $this->sanitizeCsvField($proposedAction),
                $this->sanitizeCsvField($result),
                $this->sanitizeCsvField($errorMessage ?? ''),
            ];

            file_put_contents($this->csvFilePath, implode(',', $line) . "\n", FILE_APPEND);
        } catch (\Throwable $e) {
            Log::error('MailCleanupLogger::logMessage CSV write failed: ' . $e->getMessage());
        }
    }

    private function sanitizeCsvField(string $field): string
    {
        $field = str_replace(["\r", "\n", '"'], [' ', ' ', '""'], $field);
        return '"' . $field . '"';
    }
}
