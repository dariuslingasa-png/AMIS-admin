<?php

namespace App\Services\Email;

use App\Models\BulkEmailCampaign;
use App\Models\EmailTemplate;
use App\Models\EnrollmentApplicant;
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

    /**
     * Resolve email recipients dynamically based on selected target type and filter parameters.
     */
    public function resolveRecipients(string $recipientType, ?string $filter = null): array
    {
        $emails = [];

        switch ($recipientType) {
            case 'students':
                $query = Student::query();
                if (filled($filter) && $filter !== 'all') {
                    $query->where(function ($q) use ($filter) {
                        $q->where('grade_level', $filter);
                        if (Schema::hasColumn('students', 'section')) {
                            $q->orWhere('section', $filter);
                        }
                    });
                }
                $students = $query->get();
                foreach ($students as $st) {
                    $e = $st->school_email ?? $st->ms_email ?? $st->email;
                    if (filled($e) && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                        $name = trim(($st->first_name ?? '').' '.($st->last_name ?? '')) ?: ($st->full_name ?? 'Student');
                        $emails[$e] = [
                            'email' => $e,
                            'name' => $name,
                            'student_id' => $st->student_number ?? $st->id,
                            'grade_level' => $st->grade_level ?? 'N/A',
                        ];
                    }
                }
                break;

            case 'faculty':
                $facultyUsers = User::whereIn('role', ['teacher', 'faculty', 'instructor', 'adviser'])
                    ->whereNotNull('email')->get();
                foreach ($facultyUsers as $u) {
                    if (filter_var($u->email, FILTER_VALIDATE_EMAIL)) {
                        $emails[$u->email] = [
                            'email' => $u->email,
                            'name' => $u->name,
                            'role' => 'Faculty',
                        ];
                    }
                }
                break;

            case 'staff':
                $staffUsers = User::whereIn('role', ['staff', 'admin', 'super_admin', 'registrar', 'finance'])
                    ->whereNotNull('email')->get();
                foreach ($staffUsers as $u) {
                    if (filter_var($u->email, FILTER_VALIDATE_EMAIL)) {
                        $emails[$u->email] = [
                            'email' => $u->email,
                            'name' => $u->name,
                            'role' => 'Staff',
                        ];
                    }
                }
                break;

            case 'parents':
                if (Schema::hasTable('enrollment_applicants')) {
                    $applicants = EnrollmentApplicant::whereNotNull('guardian_email')
                        ->orWhereNotNull('father_email')
                        ->orWhereNotNull('mother_email')->get();
                    foreach ($applicants as $app) {
                        $pEmail = $app->guardian_email ?: ($app->father_email ?: $app->mother_email);
                        if (filled($pEmail) && filter_var($pEmail, FILTER_VALIDATE_EMAIL)) {
                            $emails[$pEmail] = [
                                'email' => $pEmail,
                                'name' => $app->guardian_name ?: 'Parent / Guardian',
                                'student_name' => trim($app->first_name.' '.$app->last_name),
                            ];
                        }
                    }
                }
                break;

            case 'alumni':
                if (Schema::hasTable('students')) {
                    $alumni = Student::where('status', 'graduated')->get();
                    foreach ($alumni as $st) {
                        $e = $st->school_email ?? $st->ms_email ?? $st->email;
                        if (filled($e) && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                            $emails[$e] = [
                                'email' => $e,
                                'name' => trim(($st->first_name ?? '').' '.($st->last_name ?? '')) ?: 'Alumni',
                            ];
                        }
                    }
                }
                break;

            case 'custom_emails':
                if (filled($filter)) {
                    $raw = preg_split('/[\s,;]+/', $filter);
                    foreach ($raw as $e) {
                        $e = trim($e);
                        // Header injection security check
                        if (str_contains($e, "\r") || str_contains($e, "\n")) {
                            continue;
                        }
                        if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
                            $emails[$e] = [
                                'email' => $e,
                                'name' => $e,
                            ];
                        }
                    }
                }
                break;
        }

        return $emails;
    }

    /**
     * Replace dynamic template variables ({student_name}, {student_id}, {grade_level}, {school_year}, {current_date}).
     */
    public function renderTemplateVariables(string $templateHtml, array $recipientData): string
    {
        $schoolYear = config('services.school.year', '2026-2027');
        $currentDate = now()->format('F d, Y');

        $replacements = [
            '{student_name}' => $recipientData['name'] ?? ($recipientData['student_name'] ?? 'Student'),
            '{student_id}' => $recipientData['student_id'] ?? 'N/A',
            '{grade_level}' => $recipientData['grade_level'] ?? 'N/A',
            '{school_year}' => $schoolYear,
            '{current_date}' => $currentDate,
            '{school_email}' => config('services.school.email', 'info@amis.edu.ph'),
            '{course}' => $recipientData['grade_level'] ?? 'K-12 Basic Education',
        ];

        return strtr($templateHtml, $replacements);
    }

    /**
     * Validate attachment files against security rules (file type, max size 15MB, dangerous extensions).
     */
    public function validateAttachments(array $files): array
    {
        $validatedPaths = [];

        foreach ($files as $file) {
            if (! ($file instanceof UploadedFile) || ! $file->isValid()) {
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
            $path = $file->storeAs('attachments', time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$ext, 'public');
            $validatedPaths[] = storage_path('app/public/'.$path);
        }

        return $validatedPaths;
    }

    /**
     * Seed comprehensive preset email templates covering Finance, Registrar, Student Affairs, Guidance, Library, HR, and General.
     */
    public function seedDefaultTemplates(): void
    {
        if (! Schema::hasTable('email_templates') || EmailTemplate::count() >= 15) {
            return;
        }

        $presets = [
            // FINANCE TEMPLATES
            [
                'name' => 'Tuition Balance & Billing Reminder',
                'category' => 'Finance',
                'subject' => 'Statement of Account & Tuition Fee Reminder - SY {school_year}',
                'body_html' => '<h2>Assalamu Alaikum Dear Parent / Student,</h2><p>This is an official statement of account notification regarding your pending tuition fee balance for SY {school_year}.</p><p><strong>Student Name:</strong> {student_name}<br><strong>Student ID:</strong> {student_id}<br><strong>Grade Level:</strong> {grade_level}</p><p>Please settle the outstanding amount at the AMIS Finance Office or through official bank transfer. Kindly disregard if payment has already been completed.</p><p>Jazakumullahu Khairan,<br><strong>AMIS Finance Office</strong></p>',
                'is_preset' => true,
            ],
            [
                'name' => 'Payment Confirmation & Receipt Notice',
                'category' => 'Finance',
                'subject' => 'Official Payment Confirmation & Receipt Verification - AMIS',
                'body_html' => '<h2>Payment Received & Verified</h2><p>Dear {student_name},</p><p>We are pleased to confirm that your enrollment payment has been successfully verified by our Finance Team for SY {school_year}.</p><p>Your Official Receipt (OR) has been issued and linked to your student account portal.</p><p>Warm regards,<br><strong>AMIS Finance Department</strong></p>',
                'is_preset' => true,
            ],
            [
                'name' => 'Scholarship & Discount Notification',
                'category' => 'Finance',
                'subject' => 'Official Notice: Approved Scholarship Grant / Fee Discount',
                'body_html' => '<h2>Scholarship Approval Notice</h2><p>Dear {student_name},</p><p>Congratulations! We are pleased to inform you that your application for scholarship grant / tuition fee discount has been officially approved for SY {school_year}.</p><p>Best regards,<br><strong>AMIS Scholarship Committee</strong></p>',
                'is_preset' => true,
            ],

            // REGISTRAR TEMPLATES
            [
                'name' => 'Official Enrollment Confirmation',
                'category' => 'Registrar',
                'subject' => 'Official Enrollment Confirmation - Al-Munawwara Islamic School',
                'body_html' => '<h2>Welcome to AMIS! Enrollment Confirmed</h2><p>Dear {student_name},</p><p>Assalamu Alaikum Warahmatullahi Wabarakatuh!</p><p>We are excited to inform you that your enrollment for SY {school_year} in <strong>{grade_level}</strong> has been officially completed.</p><p><strong>Student ID:</strong> {student_id}<br><strong>Date Enrolled:</strong> {current_date}</p><p>Please keep this email for your official records.</p><p>Sincerely,<br><strong>AMIS Registrar Office</strong></p>',
                'is_preset' => true,
            ],
            [
                'name' => 'Graduation & Commencement Clearance Notice',
                'category' => 'Registrar',
                'subject' => 'Graduation Ceremony & Clearance Notice - SY {school_year}',
                'body_html' => '<h2>Congratulations Candidate for Graduation!</h2><p>Dear {student_name},</p><p>As we approach the end of the academic year, please ensure all your academic and institutional clearance requirements are fulfilled prior to commencement rehearsals.</p><p>Congratulations on your achievements!</p><p>Warm regards,<br><strong>AMIS Graduation Committee</strong></p>',
                'is_preset' => true,
            ],
            [
                'name' => 'Transcript & Document Release Notice',
                'category' => 'Registrar',
                'subject' => 'Requested Academic Transcript / Certificate Available for Pickup',
                'body_html' => '<h2>Document Release Advisory</h2><p>Dear {student_name},</p><p>Your requested academic records / Transcript of Records / Certificate of Enrollment are now available for pickup at the AMIS Registrar Office.</p><p>Thank you,<br><strong>Registrar Office</strong></p>',
                'is_preset' => true,
            ],

            // STUDENT AFFAIRS TEMPLATES
            [
                'name' => 'Official School Announcement',
                'category' => 'Student Affairs',
                'subject' => 'Official School Advisory: Important Activity Update',
                'body_html' => '<h2>Dear AMIS Community,</h2><p>Please be guided by the following important institutional announcement regarding upcoming school schedules and activities...</p><p>Jazakumullahu Khairan,<br><strong>Office of Student Affairs</strong></p>',
                'is_preset' => true,
            ],
            [
                'name' => 'School Event & Assembly Invitation',
                'category' => 'Student Affairs',
                'subject' => 'Invitation: Upcoming AMIS School Event & Gathering',
                'body_html' => '<h2>Assalamu Alaikum,</h2><p>You are cordially invited to participate in our upcoming institutional gathering scheduled for this month. Detailed schedules and venues are attached below.</p><p>Best regards,<br><strong>AMIS Student Council & Affairs</strong></p>',
                'is_preset' => true,
            ],

            // GUIDANCE TEMPLATES
            [
                'name' => 'Counseling & Guidance Appointment',
                'category' => 'Guidance',
                'subject' => 'Guidance Office Appointment & Consultation Schedule',
                'body_html' => '<h2>Guidance Office Consultation</h2><p>Dear {student_name},</p><p>This is to inform you of a scheduled consultation with the AMIS Guidance Office on {current_date}.</p><p>Sincerely,<br><strong>Guidance & Counseling Department</strong></p>',
                'is_preset' => true,
            ],

            // LIBRARY TEMPLATES
            [
                'name' => 'Overdue Book & Library Notice',
                'category' => 'Library',
                'subject' => 'Urgent: Overdue Library Book Return Notice',
                'body_html' => '<h2>Library Book Return Advisory</h2><p>Dear {student_name},</p><p>Our records indicate that you have an overdue library book borrowed from the AMIS Library. Please return the book at your earliest convenience.</p><p>Thank you,<br><strong>AMIS Library Services</strong></p>',
                'is_preset' => true,
            ],

            // HUMAN RESOURCES TEMPLATES
            [
                'name' => 'HR Interview Invitation',
                'category' => 'Human Resources',
                'subject' => 'Interview Invitation - Al-Munawwara Islamic School',
                'body_html' => '<h2>Job Application Interview Invitation</h2><p>Dear Candidate,</p><p>Thank you for applying at Al-Munawwara Islamic School. We would like to invite you for an interview with our HR and Academic Department.</p><p>Best regards,<br><strong>AMIS HR Department</strong></p>',
                'is_preset' => true,
            ],

            // GENERAL TEMPLATES
            [
                'name' => 'Welcome & Portal Credentials Notice',
                'category' => 'General',
                'subject' => 'Welcome to AMIS Student Portal Access Credentials',
                'body_html' => '<h2>Welcome to AMIS Student Portal</h2><p>Dear {student_name},</p><p>Your student portal access credentials have been generated. You can now sign in to view your class schedules, SOA, and academic progress.</p><p><strong>Student ID:</strong> {student_id}<br><strong>School Year:</strong> {school_year}</p><p>Best regards,<br><strong>AMIS Information Technology</strong></p>',
                'is_preset' => true,
            ],
            [
                'name' => 'System Maintenance Advisory',
                'category' => 'General',
                'subject' => 'Scheduled System Maintenance Notice - AMIS Admin Portal',
                'body_html' => '<h2>Scheduled System Maintenance Advisory</h2><p>Please be advised that the AMIS Admin and Student Portals will undergo scheduled system maintenance to perform infrastructure updates.</p><p>Thank you for your patience and understanding.<br><strong>AMIS IT & Systems Team</strong></p>',
                'is_preset' => true,
            ],
        ];

        foreach ($presets as $p) {
            EmailTemplate::updateOrCreate(['name' => $p['name']], $p);
        }
    }

    /**
     * Fetch Email Composer Dashboard statistics.
     */
    public function getDashboardMetrics(): array
    {
        $this->seedDefaultTemplates();

        $sentToday = 0;
        $totalSent = 0;
        $totalFailed = 0;
        if (Schema::hasTable('email_logs')) {
            $sentToday = DB::table('email_logs')->where('status', 'sent')->whereDate('created_at', now()->toDateString())->count();
            $totalSent = DB::table('email_logs')->where('status', 'sent')->count();
            $totalFailed = DB::table('email_logs')->where('status', 'failed')->count();
        }

        $pendingQueue = 0;
        $campaigns = [];
        if (Schema::hasTable('bulk_email_campaigns')) {
            $pendingQueue = DB::table('bulk_email_campaigns')->whereIn('status', ['queued', 'sending'])->count();
            $campaigns = BulkEmailCampaign::latest()->take(10)->get();
        }

        $rotator = app(SmartSmtpRotatorService::class);
        $smtpMetrics = $rotator->getPoolMetrics();

        return [
            'sentToday' => $sentToday,
            'totalSent' => $totalSent,
            'totalFailed' => $totalFailed,
            'pendingQueue' => $pendingQueue,
            'campaigns' => $campaigns,
            'smtpMetrics' => $smtpMetrics,
            'presetTemplatesCount' => Schema::hasTable('email_templates') ? EmailTemplate::count() : 0,
        ];
    }
}
