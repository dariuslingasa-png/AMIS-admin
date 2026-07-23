<?php

use App\Models\AdminAuditLog;
use App\Models\EnrollmentApplicant;
use App\Models\Payment;
use App\Models\StudentAccount;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Fetch all audit logs of types we care about
        $logs = AdminAuditLog::whereIn('event', [
            'application_approved',
            'application_status_updated',
            'onboarding_email_resent',
            'section_verified',
            'payment_approved',
            'payment_rejected',
            'fee_adjustment',
            'payment_reminder_sent',
        ])
            ->orWhere('event', 'like', 'document%')
            ->get();

        foreach ($logs as $log) {
            $metadata = $log->metadata;
            if (! is_array($metadata)) {
                continue;
            }

            $applicantId = $metadata['applicant_id'] ?? null;
            $paymentId = $metadata['payment_id'] ?? null;
            $accountId = $metadata['account_id'] ?? null;

            $applicantName = null;

            // Resolve applicant name from applicantId
            if ($applicantId) {
                $applicant = EnrollmentApplicant::find($applicantId);
                if ($applicant) {
                    $applicantName = $applicant->full_name;
                }
            }

            // If not found, check paymentId
            if (! $applicantName && $paymentId) {
                $payment = Payment::find($paymentId);
                if ($payment && $payment->applicant) {
                    $applicantName = $payment->applicant->full_name;
                }
            }

            // If not found, check accountId
            if (! $applicantName && $accountId) {
                $account = StudentAccount::find($accountId);
                if ($account) {
                    $applicantName = $account->student?->applicant?->full_name ?: ($account->applicant?->full_name ?: null);
                }
            }

            if (! $applicantName) {
                continue;
            }

            $message = $log->message;

            // Rewrite the message based on the event type
            if ($log->event === 'application_approved') {
                if (str_contains($message, '(family batch)')) {
                    $message = "Enrollment application approved (family batch): {$applicantName}.";
                } else {
                    $message = "Enrollment application approved for {$applicantName}.";
                }
            } elseif ($log->event === 'application_status_updated') {
                $status = $metadata['status'] ?? 'updated';
                $message = "Application review status updated to '{$status}' for {$applicantName}.";
            } elseif ($log->event === 'onboarding_email_resent') {
                $message = "Enrollment onboarding inbox email resend requested for {$applicantName}.";
            } elseif ($log->event === 'section_verified') {
                $section = $metadata['section'] ?? '';
                $action = $metadata['action'] ?? '';
                $message = "Section '{$section}' {$action}d for {$applicantName}.";
            } elseif ($log->event === 'payment_approved') {
                $message = "Payment proof approved for {$applicantName}.";
            } elseif ($log->event === 'payment_rejected') {
                $message = "Payment proof rejected for {$applicantName}.";
            } elseif ($log->event === 'fee_adjustment') {
                $message = "Fee adjusted for student account: {$applicantName}.";
            } elseif ($log->event === 'payment_reminder_sent') {
                $message = "Payment reminder sent for {$applicantName}.";
            } elseif (str_starts_with($log->event, 'documents_')) {
                $status = $metadata['status'] ?? '';
                $message = "Uploaded documents status updated to '{$status}' for {$applicantName}.";
            } elseif (str_starts_with($log->event, 'document_')) {
                $docKey = $metadata['doc_key'] ?? '';
                $status = $metadata['status'] ?? '';
                $message = "Document '{$docKey}' status updated to '{$status}' for {$applicantName}.";
            }

            $log->update(['message' => $message]);
        }
    }

    public function down(): void
    {
        // No down needed
    }
};
