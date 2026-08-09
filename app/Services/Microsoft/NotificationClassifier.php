<?php

namespace App\Services\Microsoft;

class NotificationClassifier
{
    /**
     * Classify an inbox message.
     *
     * @return array ['is_candidate' => bool, 'rule' => string|null, 'is_protected' => bool, 'protection_reason' => string|null, 'proposed_action' => string]
     */
    public function classify(array $message): array
    {
        $subject = trim($message['subject'] ?? '');
        $subjectLower = strtolower($subject);
        $bodyPreview = strtolower($message['bodyPreview'] ?? '');

        $senderData = $message['sender']['emailAddress'] ?? ($message['from']['emailAddress'] ?? []);
        $senderEmail = strtolower(trim($senderData['address'] ?? ''));
        $senderName = strtolower(trim($senderData['name'] ?? ''));

        // STEP 1: High-Priority Security & Financial Protection Rules ONLY
        $criticalProtection = $this->checkCriticalProtectionRules($senderEmail, $senderName, $subjectLower, $bodyPreview);
        if ($criticalProtection !== null) {
            return [
                'is_candidate' => false,
                'rule' => null,
                'is_protected' => true,
                'protection_reason' => $criticalProtection,
                'proposed_action' => 'KEEP_IN_INBOX',
            ];
        }

        // STEP 2: Comprehensive Teams & Class Meeting Notification Rules (TOP MATCH PRIORITY)
        $candidateRule = $this->checkNotificationRules($senderEmail, $senderName, $subjectLower, $bodyPreview);
        if ($candidateRule !== null) {
            return [
                'is_candidate' => true,
                'rule' => $candidateRule,
                'is_protected' => false,
                'protection_reason' => null,
                'proposed_action' => 'MOVE_TO_DELETED_ITEMS',
            ];
        }

        // STEP 3: General Direct Human-to-Human Email Protection
        $protectionReason = $this->checkGeneralProtectionRules($senderEmail, $senderName, $subjectLower, $bodyPreview);
        if ($protectionReason !== null) {
            return [
                'is_candidate' => false,
                'rule' => null,
                'is_protected' => true,
                'protection_reason' => $protectionReason,
                'proposed_action' => 'KEEP_IN_INBOX',
            ];
        }

        return [
            'is_candidate' => false,
            'rule' => null,
            'is_protected' => false,
            'protection_reason' => null,
            'proposed_action' => 'KEEP_IN_INBOX',
        ];
    }

    private function checkCriticalProtectionRules(string $senderEmail, string $senderName, string $subject, string $bodyPreview): ?string
    {
        // Category 1: Password Resets, MFA, & Security Alerts
        $securityKeywords = ['password reset', 'security code', 'verification code', 'mfa', 'one-time password', 'otp', 'multi-factor authentication', 'account recovery', 'amis test email'];
        foreach ($securityKeywords as $kw) {
            if (str_contains($subject, $kw) || str_contains($bodyPreview, $kw)) {
                return 'PROTECTED_SECURITY_ALERT (' . $kw . ')';
            }
        }

        // Category 2: Finance, Payment, Tuition, & Receipts
        $financeKeywords = ['tuition', 'payment receipt', 'statement of account', 'billing statement', 'soa', 'cashier', 'finance department', 'assessment fee', 'official receipt'];
        foreach ($financeKeywords as $kw) {
            if (str_contains($subject, $kw) || str_contains($bodyPreview, $kw)) {
                return 'PROTECTED_FINANCE_PAYMENT (' . $kw . ')';
            }
        }

        return null;
    }

    private function checkNotificationRules(string $senderEmail, string $senderName, string $subject, string $bodyPreview): ?string
    {
        // 1. Automated Teams/SharePoint Senders
        if (str_contains($senderName, 'microsoft teams') ||
            str_contains($senderName, 'sharepoint') ||
            str_contains($senderEmail, 'noreply@microsoftteams.com') ||
            str_contains($senderEmail, 'no-reply@sharepointonline.com') ||
            str_contains($senderEmail, 'email.teams.microsoft.com') ||
            str_contains($senderEmail, 'notifications.teams.microsoft.com') ||
            str_ends_with($senderEmail, 'teams.microsoft.com')) {
            return 'TEAMS_AUTOMATED_SENDER (' . ($senderName ?: $senderEmail) . ')';
        }

        // 2. Class Meeting Broadcast & Canceled Events
        if (str_contains($subject, 'canceled:') || str_contains($subject, 'cancelled:') || str_contains($subject, 'event canceled')) {
            return 'TEAMS_CANCELED_MEETING_NOTIFICATION';
        }

        // 3. Subject Pattern Keywords
        $teamsKeywords = [
            'circle time', 'araling panlipunan', 'mapeh', 'filipino', 'english', 'science',
            'mathematics', 'shaf', 'qur\'an', 'quran', 'hadith', 'fiqh', 'isal', 'قراءة', 'سورة',
            'request has been made', 'requested to join', 'wants to access', 'wants to view',
            'shared a file', 'shared with you', 'added you to', 'posted a message in',
            'posted in', 'sent a message in', 'added a channel', 'mentioned you',
            'reply to team', 'teams activity', 'missed activity', 'missed message',
            'assignment', 'class notification', 'class notebook'
        ];

        foreach ($teamsKeywords as $kw) {
            if (str_contains($subject, $kw)) {
                return 'TEAMS_CLASS_NOTIFICATION (' . $kw . ')';
            }
        }

        // 4. Grade / Section / Class Subjects (e.g. "K1 -", "K2 -", "G1 -", "G2 -", "G3 -", "G5 -", "G10 -", etc.)
        if (preg_match('/^(k1|k2|g1|g2|g3|g4|g5|g6|g7|g8|g9|g10|g11|g12|isal)\b/i', $subject)) {
            return 'TEAMS_GRADE_CLASS_NOTIFICATION';
        }

        // 5. Match Faculty / ISAL / Student ID senders with class meeting previews or short subject titles
        if (str_contains($senderEmail, 'tr.') || str_contains($senderEmail, 'isal.') || preg_match('/^\d{6}/', $senderEmail)) {
            if (str_contains($bodyPreview, 'teams meeting') || str_contains($bodyPreview, 'join the meeting') || str_contains($bodyPreview, 'occurs every') || str_contains($bodyPreview, 'no preview') || empty($bodyPreview)) {
                return 'TEAMS_CLASS_MEETING_BROADCAST';
            }
        }

        return null;
    }

    private function checkGeneralProtectionRules(string $senderEmail, string $senderName, string $subject, string $bodyPreview): ?string
    {
        // Protected: Enrollment & Admissions
        $enrollmentKeywords = ['enrollment application', 'admission result', 'applicant status', 'registration form', 'student onboarding'];
        foreach ($enrollmentKeywords as $kw) {
            if (str_contains($subject, $kw) || str_contains($bodyPreview, $kw)) {
                return 'PROTECTED_ENROLLMENT (' . $kw . ')';
            }
        }

        // Protected: Official School Announcements
        $adminKeywords = ['official announcement', 'memorandum', 'memo:', 'school director', 'principal notice', 'holiday advisory', 'executive office'];
        foreach ($adminKeywords as $kw) {
            if (str_contains($subject, $kw) || str_contains($bodyPreview, $kw)) {
                return 'PROTECTED_OFFICIAL_ANNOUNCEMENT (' . $kw . ')';
            }
        }

        // Protected: Personal Direct Human-to-Human Emails
        if (str_contains($senderEmail, '@amis.edu.ph') &&
            !str_contains($senderEmail, 'noreply') &&
            !str_contains($senderEmail, 'no-reply') &&
            !str_contains($senderEmail, 'system') &&
            !str_contains($senderEmail, 'bot')) {
            return 'PROTECTED_HUMAN_DIRECT_EMAIL';
        }

        return null;
    }
}
