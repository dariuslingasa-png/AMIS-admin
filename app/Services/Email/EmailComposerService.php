<?php

namespace App\Services\Email;

use App\Models\BulkEmailCampaign;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Student;
use App\Models\User;
use App\Services\System\SmartSmtpRotatorService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmailComposerService
{
    protected array $dangerousExtensions = ['exe', 'bat', 'sh', 'php', 'js', 'py', 'vbs', 'cmd', 'ps1', 'cgi', 'pl', 'asp', 'aspx'];
    protected array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'csv', 'txt'];

    /**
     * Resolve email recipients dynamically based on selected target type and filter.
     */
    public function resolveRecipients(string $recipientType, ?string $filter = null): array
    {
        $emails = [];

        switch ($recipientType) {
            case 'students':
                $query = Student::query();
                if (filled($filter) && $filter !== 'all') {
                    $query->where('grade_level', $filter);
                }
                $students = $query->get();
                foreach ($students as $st) {
                    $e = $st->email ?? $st->school_email ?? $st->ms_email;
                    if (filled($e) && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                        $emails[$e] = $e;
                    }
                }
                break;

            case 'faculty':
                $emails = User::whereIn('role', ['teacher', 'faculty', 'instructor'])
                              ->whereNotNull('email')
                              ->pluck('email', 'name')
                              ->toArray();
                break;

            case 'staff':
                $emails = User::whereIn('role', ['staff', 'admin', 'registrar', 'finance'])
                              ->whereNotNull('email')
                              ->pluck('email', 'name')
                              ->toArray();
                break;

            case 'alumni':
                if (Schema::hasTable('students')) {
                    $emails = Student::where('status', 'graduated')
                                    ->whereNotNull('email')
                                    ->pluck('email', 'full_name')
                                    ->toArray();
                }
                break;

            case 'custom_emails':
                if (filled($filter)) {
                    $raw = preg_split('/[\s,;]+/', $filter);
                    foreach ($raw as $e) {
                        $e = trim($e);
                        if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
                            $emails[$e] = $e;
                        }
                    }
                }
                break;

            default:
                $emails = Student::whereNotNull('email')->pluck('email', 'full_name')->toArray();
                break;
        }

        return array_unique(array_filter($emails));
    }

    /**
     * Validate attachment files against security rules (file type, max size 15MB, dangerous extensions).
     */
    public function validateAttachments(array $files): array
    {
        $validatedPaths = [];

        foreach ($files as $file) {
            if (!($file instanceof UploadedFile) || !$file->isValid()) {
                continue;
            }

            $ext = strtolower($file->getClientOriginalExtension());

            // Security Check 1: Block dangerous extensions
            if (in_array($ext, $this->dangerousExtensions, true)) {
                throw new \InvalidArgumentException("Security Violation: File extension '.{$ext}' is restricted and blocked.");
            }

            // Security Check 2: Max File Size 15MB (15360 KB)
            if ($file->getSize() > 15 * 1024 * 1024) {
                throw new \InvalidArgumentException("Attachment file '{$file->getClientOriginalName()}' exceeds the maximum allowed size of 15MB.");
            }

            // Store validated attachment in storage/app/public/attachments/
            $path = $file->storeAs('attachments', time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $ext, 'public');
            $validatedPaths[] = storage_path('app/public/' . $path);
        }

        return $validatedPaths;
    }

    /**
     * Seed default reusable email templates if none exist.
     */
    public function seedDefaultTemplates(): void
    {
        if (!Schema::hasTable('email_templates') || EmailTemplate::count() > 0) {
            return;
        }

        $presets = [
            [
                'name' => 'General Official Announcement',
                'category' => 'announcement',
                'subject' => 'Official Announcement - Al-Munawwara Islamic School',
                'body_html' => '<h2>Dear AMIS Community,</h2><p>We are pleased to inform you of the following important updates regarding upcoming school activities...</p><p>Best regards,<br><strong>School Administration</strong></p>',
                'is_preset' => true,
            ],
            [
                'name' => 'Upcoming Event Notification',
                'category' => 'event',
                'subject' => 'Event Invitation: Upcoming School Activity',
                'body_html' => '<h2>Assalamu Alaikum,</h2><p>You are cordially invited to attend our upcoming event scheduled for next week. Please review the details below...</p>',
                'is_preset' => true,
            ],
            [
                'name' => 'Payment & Document Reminder',
                'category' => 'reminder',
                'subject' => 'Urgent Reminder: Pending Enrollment Document / Requirement',
                'body_html' => '<h2>Important Notice,</h2><p>This is a friendly reminder regarding your pending requirements for the current academic term. Please submit them at your earliest convenience.</p>',
                'is_preset' => true,
            ],
            [
                'name' => 'Account Credentials Verification',
                'category' => 'verification',
                'subject' => 'Your AMIS Student Portal Access Credentials',
                'body_html' => '<h2>Welcome to AMIS Student Portal,</h2><p>Your account credentials have been generated successfully. Please find your login details below...</p>',
                'is_preset' => true,
            ],
            [
                'name' => 'Graduation & Commencement Notice',
                'category' => 'graduation',
                'subject' => 'Graduation Ceremony & Clearance Notice',
                'body_html' => '<h2>Congratulations Candidates for Graduation!</h2><p>Please complete your graduation clearance requirements by referring to the instructions attached below.</p>',
                'is_preset' => true,
            ],
        ];

        foreach ($presets as $p) {
            EmailTemplate::create($p);
        }
    }

    /**
     * Fetch Email Composer Dashboard statistics.
     */
    public function getDashboardMetrics(): array
    {
        $this->seedDefaultTemplates();

        $totalSent = 0;
        $totalFailed = 0;
        if (Schema::hasTable('email_logs')) {
            $totalSent = DB::table('email_logs')->where('status', 'sent')->count();
            $totalFailed = DB::table('email_logs')->where('status', 'failed')->count();
        }

        $campaigns = [];
        if (Schema::hasTable('bulk_email_campaigns')) {
            $campaigns = BulkEmailCampaign::latest()->take(10)->get();
        }

        $rotator = app(SmartSmtpRotatorService::class);
        $smtpMetrics = $rotator->getPoolMetrics();

        return [
            'totalSent' => $totalSent,
            'totalFailed' => $totalFailed,
            'campaigns' => $campaigns,
            'smtpMetrics' => $smtpMetrics,
            'presetTemplatesCount' => Schema::hasTable('email_templates') ? EmailTemplate::count() : 0,
        ];
    }
}
