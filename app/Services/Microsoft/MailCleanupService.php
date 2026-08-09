<?php

namespace App\Services\Microsoft;

use Illuminate\Support\Facades\Log;

class MailCleanupService
{
    protected MicrosoftUserService $userService;
    protected MicrosoftMailboxService $mailboxService;
    protected NotificationClassifier $classifier;
    protected MailCleanupLogger $logger;

    public function __construct(
        MicrosoftUserService $userService,
        MicrosoftMailboxService $mailboxService,
        NotificationClassifier $classifier,
        MailCleanupLogger $logger
    ) {
        $this->userService = $userService;
        $this->mailboxService = $mailboxService;
        $this->classifier = $classifier;
        $this->logger = $logger;
    }

    public function scan(?string $targetUser = null, ?string $filterType = null, int $limit = 2000, ?int $olderThanDays = null, int $sampleLimit = 10): array
    {
        $facultyOnly = ($filterType === 'faculty');
        return $this->scanMailboxes($targetUser, $facultyOnly, $limit, $olderThanDays, $sampleLimit);
    }

    public function cleanup(?string $targetUser = null, ?string $filterType = null, int $limit = 2000, ?int $olderThanDays = null): array
    {
        $facultyOnly = ($filterType === 'faculty');
        return $this->executeCleanup($targetUser, $facultyOnly, $limit, $olderThanDays);
    }

    /**
     * Run Dry-Run Mailbox Scan.
     */
    public function scanMailboxes(?string $targetUser = null, bool $facultyOnly = false, int $limit = 2000, ?int $olderThanDays = null, int $sampleLimit = 10): array
    {
        $mailboxes = $this->getMailboxesToProcess($targetUser, $facultyOnly);
        $batchId = $this->logger->getBatchId();

        $report = [
            'batch_id' => $batchId,
            'mode' => 'DRY_RUN',
            'csv_log_path' => $this->logger->getCsvFilePath(),
            'total_mailboxes' => count($mailboxes),
            'total_inspected' => 0,
            'total_candidates' => 0,
            'total_protected' => 0,
            'total_failed' => 0,
            'mailbox_summaries' => [],
            'samples' => [],
        ];

        $allMatchedSamples = [];

        foreach ($mailboxes as $upn) {
            $inspectedCount = 0;
            $candidateCount = 0;
            $protectedCount = 0;
            $skipToken = null;

            while ($inspectedCount < $limit) {
                $pageSize = min(100, $limit - $inspectedCount);
                $inboxResult = $this->mailboxService->getInboxMessages($upn, $pageSize, $skipToken, $olderThanDays);
                $messages = $inboxResult['messages'] ?? [];
                $nextLink = $inboxResult['nextLink'] ?? null;

                if (empty($messages)) {
                    break;
                }

                foreach ($messages as $msg) {
                    $inspectedCount++;
                    $report['total_inspected']++;
                    $classification = $this->classifier->classify($msg);

                    $msgId = $msg['id'] ?? '';
                    $receivedDate = $msg['receivedDateTime'] ?? '';
                    $senderData = $msg['sender']['emailAddress'] ?? ($msg['from']['emailAddress'] ?? []);
                    $senderEmail = strtolower($senderData['address'] ?? '');
                    $subject = $msg['subject'] ?? '(No Subject)';

                    if ($classification['is_candidate']) {
                        $candidateCount++;
                        $report['total_candidates']++;

                        $sampleRecord = [
                            'mailbox' => $upn,
                            'message_id' => $msgId,
                            'received_date' => $receivedDate,
                            'sender' => $senderEmail,
                            'subject' => $subject,
                            'matched_rule' => $classification['rule'],
                            'proposed_action' => 'MOVE_TO_DELETED_ITEMS',
                        ];

                        $allMatchedSamples[] = $sampleRecord;

                        $this->logger->logMessage(
                            $upn,
                            $msgId,
                            $receivedDate,
                            $senderEmail,
                            $subject,
                            $classification['rule'],
                            'MOVE_TO_DELETED_ITEMS',
                            'DRY_RUN_WOULD_MOVE'
                        );
                    } elseif ($classification['is_protected']) {
                        $protectedCount++;
                        $report['total_protected']++;
                    }
                }

                if ($nextLink && preg_match('/\$skiptoken=([^&]+)/i', $nextLink, $matches)) {
                    $skipToken = urldecode($matches[1]);
                } else {
                    break;
                }
            }

            $report['mailbox_summaries'][] = [
                'mailbox' => $upn,
                'inspected' => $inspectedCount,
                'candidates' => $candidateCount,
                'protected' => $protectedCount,
                'failed' => 0,
            ];
        }

        $report['samples'] = array_slice($allMatchedSamples, 0, $sampleLimit);
        return $report;
    }

    /**
     * Execute Reversible Cleanup Move to "Deleted Items".
     */
    public function executeCleanup(?string $targetUser = null, bool $facultyOnly = false, int $limit = 2000, ?int $olderThanDays = null): array
    {
        $mailboxes = $this->getMailboxesToProcess($targetUser, $facultyOnly);
        $batchId = $this->logger->getBatchId();

        $report = [
            'batch_id' => $batchId,
            'mode' => 'LIVE_EXECUTION',
            'csv_log_path' => $this->logger->getCsvFilePath(),
            'total_mailboxes' => count($mailboxes),
            'total_inspected' => 0,
            'total_candidates' => 0,
            'total_moved' => 0,
            'total_failed' => 0,
            'mailbox_summaries' => [],
        ];

        foreach ($mailboxes as $upn) {
            $inspectedCount = 0;
            $candidateCount = 0;
            $movedCount = 0;
            $failedCount = 0;
            $skipToken = null;

            while ($inspectedCount < $limit) {
                $pageSize = min(100, $limit - $inspectedCount);
                $inboxResult = $this->mailboxService->getInboxMessages($upn, $pageSize, $skipToken, $olderThanDays);
                $messages = $inboxResult['messages'] ?? [];
                $nextLink = $inboxResult['nextLink'] ?? null;

                if (empty($messages)) {
                    break;
                }

                foreach ($messages as $msg) {
                    $inspectedCount++;
                    $report['total_inspected']++;

                    $classification = $this->classifier->classify($msg);

                    $msgId = $msg['id'] ?? '';
                    $receivedDate = $msg['receivedDateTime'] ?? '';
                    $senderData = $msg['sender']['emailAddress'] ?? ($msg['from']['emailAddress'] ?? []);
                    $senderEmail = strtolower($senderData['address'] ?? '');
                    $subject = $msg['subject'] ?? '(No Subject)';

                    if ($classification['is_candidate']) {
                        $candidateCount++;
                        $report['total_candidates']++;

                        $moveResult = $this->mailboxService->moveMessageToDeletedItems($upn, $msgId);

                        if ($moveResult['success']) {
                            $movedCount++;
                            $report['total_moved']++;
                            $this->logger->logMessage(
                                $upn,
                                $msgId,
                                $receivedDate,
                                $senderEmail,
                                $subject,
                                $classification['rule'],
                                'MOVE_TO_DELETED_ITEMS',
                                'SUCCESS_MOVED'
                            );
                        } else {
                            $failedCount++;
                            $report['total_failed']++;
                            $this->logger->logMessage(
                                $upn,
                                $msgId,
                                $receivedDate,
                                $senderEmail,
                                $subject,
                                $classification['rule'],
                                'MOVE_TO_DELETED_ITEMS',
                                'FAILED_MOVE',
                                $moveResult['error'] ?? 'Unknown Error'
                            );
                        }
                    }
                }

                if ($nextLink && preg_match('/\$skiptoken=([^&]+)/i', $nextLink, $matches)) {
                    $skipToken = urldecode($matches[1]);
                } else {
                    break;
                }
            }

            $report['mailbox_summaries'][] = [
                'mailbox' => $upn,
                'inspected' => $inspectedCount,
                'candidates' => $candidateCount,
                'moved' => $movedCount,
                'failed' => $failedCount,
            ];
        }

        return $report;
    }

    protected function getMailboxesToProcess(?string $targetUser = null, bool $facultyOnly = false): array
    {
        if (!empty($targetUser)) {
            return [trim($targetUser)];
        }

        $filterType = $facultyOnly ? 'faculty' : null;
        $users = $this->userService->getAmisUsers($filterType);

        $mailboxes = [];
        foreach ($users as $user) {
            $upn = $user['userPrincipalName'] ?? ($user['mail'] ?? '');
            if ($upn) {
                $mailboxes[] = $upn;
            }
        }

        return $mailboxes;
    }
}
