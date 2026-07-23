<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBulkEmailCampaignJob;
use App\Mail\GenericComposerMailable;
use App\Models\AdminAuditLog;
use App\Models\BulkEmailCampaign;
use App\Models\EmailDraft;
use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Models\Section;
use App\Models\Student;
use App\Services\Email\EmailComposerService;
use App\Services\System\SmartSmtpRotatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EmailComposerController extends Controller
{
    protected EmailComposerService $composerService;

    protected SmartSmtpRotatorService $rotatorService;

    public function __construct(
        EmailComposerService $composerService,
        SmartSmtpRotatorService $rotatorService
    ) {
        $this->composerService = $composerService;
        $this->rotatorService = $rotatorService;
    }

    private function ensureAdminAccess(): void
    {
        $role = auth()->user()?->role;
        if (! in_array($role, ['super_admin', 'admin', 'registrar', 'staff', 'finance'])) {
            abort(403, 'Unauthorized. Access to Email Composer is restricted.');
        }
    }

    /**
     * Display Email Composer Dashboard & Campaign Overview.
     */
    public function index()
    {
        $this->ensureAdminAccess();
        $metrics = $this->composerService->getDashboardMetrics();
        $drafts = Schema::hasTable('email_drafts') ? EmailDraft::latest()->get() : [];

        return view('admin.email-composer.index', array_merge($metrics, compact('drafts')));
    }

    /**
     * Display Professional Email Composer interface (Gmail / Outlook style).
     */
    public function create(Request $request)
    {
        $this->ensureAdminAccess();
        $this->composerService->seedDefaultTemplates();

        $templates = EmailTemplate::latest()->get();
        $drafts = Schema::hasTable('email_drafts') ? EmailDraft::latest()->get() : [];

        // Grade levels & courses for recipient filter
        $gradeLevels = [
            'Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4',
            'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12',
        ];

        // Available sections
        $sections = [];
        if (Schema::hasTable('sections')) {
            $sections = Section::pluck('name')->toArray();
        }

        // Student records for quick selection modal safely checked against schema
        $students = [];
        if (Schema::hasTable('students')) {
            $hasEmail = Schema::hasColumn('students', 'email');
            $hasSchoolEmail = Schema::hasColumn('students', 'school_email');
            $hasMsEmail = Schema::hasColumn('students', 'ms_email');

            $query = Student::query();
            if ($hasEmail || $hasSchoolEmail || $hasMsEmail) {
                $query->where(function ($q) use ($hasEmail, $hasSchoolEmail, $hasMsEmail) {
                    if ($hasEmail) {
                        $q->orWhereNotNull('email');
                    }
                    if ($hasSchoolEmail) {
                        $q->orWhereNotNull('school_email');
                    }
                    if ($hasMsEmail) {
                        $q->orWhereNotNull('ms_email');
                    }
                });
            }
            $students = $query->take(150)->get();
        }

        // Selected draft if requested
        $selectedDraft = null;
        if ($request->has('draft_id') && Schema::hasTable('email_drafts')) {
            $selectedDraft = EmailDraft::find($request->draft_id);
        }

        return view('admin.email-composer.create', compact('templates', 'drafts', 'gradeLevels', 'sections', 'students', 'selectedDraft'));
    }

    /**
     * Send test email to administrator or custom recipient.
     */
    public function sendTest(Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'test_email' => 'required|email',
        ]);

        try {
            $ccEmails = array_filter(array_map('trim', explode(',', $request->cc_emails ?? '')));
            $bccEmails = array_filter(array_map('trim', explode(',', $request->bcc_emails ?? '')));

            $mailable = new GenericComposerMailable(
                customSubject: '[TEST] '.$request->subject,
                bodyHtml: $request->body_html,
                attachmentPaths: [],
                senderName: $request->sender_name ?: 'AMIS Information Technology',
                ccEmails: $ccEmails,
                bccEmails: $bccEmails
            );

            $result = $this->rotatorService->sendMail($request->test_email, $mailable);

            return back()->with('success', "Test email dispatched successfully to {$request->test_email} using SMTP ({$result['mailer_used']})!");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Test Email Failed: '.$e->getMessage()]);
        }
    }

    /**
     * Dispatch Bulk Email Campaign.
     */
    public function sendBulk(Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'recipient_type' => 'required|string|in:students,faculty,staff,parents,alumni,custom_emails',
            'recipient_filter' => 'nullable|string',
            'cc_emails' => 'nullable|string',
            'bcc_emails' => 'nullable|string',
            'attachments.*' => 'nullable|file|max:15360',
        ]);

        // Security attachment validation
        $attachmentPaths = [];
        if ($request->hasFile('attachments')) {
            try {
                $attachmentPaths = $this->composerService->validateAttachments($request->file('attachments'));
            } catch (\InvalidArgumentException $e) {
                return back()->withErrors(['error' => $e->getMessage()])->withInput();
            }
        }

        // Resolve recipient count
        $recipients = $this->composerService->resolveRecipients(
            $request->recipient_type,
            $request->recipient_filter
        );

        $recipientCount = count($recipients);
        if ($recipientCount === 0) {
            return back()->withErrors(['error' => 'No valid email recipients were found for the selected recipient group & filter.'])->withInput();
        }

        $campaign = BulkEmailCampaign::create([
            'title' => $request->title,
            'subject' => $request->subject,
            'body_html' => $request->body_html,
            'sender_email' => config('mail.from.address'),
            'sender_name' => $request->sender_name ?: 'AMIS Information Technology',
            'cc_emails' => $request->cc_emails,
            'bcc_emails' => $request->bcc_emails,
            'recipient_type' => $request->recipient_type,
            'recipient_filter' => $request->recipient_filter,
            'recipient_count' => $recipientCount,
            'status' => 'queued',
            'attachments_json' => $attachmentPaths,
            'created_by' => auth()->id(),
        ]);

        // Dispatch bulk email campaign immediately
        SendBulkEmailCampaignJob::dispatchSync($campaign->id);

        AdminAuditLog::record(
            'bulk_email_campaign_queued',
            true,
            "Dispatched Bulk Email Campaign '{$campaign->title}' for {$recipientCount} recipients."
        );

        return redirect()->route('admin.email-composer.index')
            ->with('success', "Bulk Email Campaign '{$campaign->title}' dispatched successfully! Processed {$recipientCount} recipients in real time.");
    }

    /**
     * Save draft campaign.
     */
    public function saveDraft(Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'title' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'body_html' => 'nullable|string',
        ]);

        EmailDraft::create([
            'title' => $request->title,
            'subject' => $request->subject,
            'body_html' => $request->body_html,
            'recipient_type' => $request->recipient_type ?? 'students',
            'recipient_filter' => $request->recipient_filter,
            'cc_emails' => $request->cc_emails,
            'bcc_emails' => $request->bcc_emails,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', "Draft '{$request->title}' saved successfully!");
    }

    /**
     * Delete email draft.
     */
    public function destroyDraft(EmailDraft $draft)
    {
        $this->ensureAdminAccess();
        $draft->delete();

        return back()->with('success', 'Email draft deleted successfully.');
    }

    /**
     * Display Email Templates Directory.
     */
    public function templates()
    {
        $this->ensureAdminAccess();
        $this->composerService->seedDefaultTemplates();
        $templates = EmailTemplate::latest()->get();

        return view('admin.email-composer.templates', compact('templates'));
    }

    /**
     * Duplicate existing template.
     */
    public function duplicateTemplate(EmailTemplate $template)
    {
        $this->ensureAdminAccess();

        $new = $template->replicate();
        $new->name = $template->name.' (Copy)';
        $new->is_preset = false;
        $new->save();

        return back()->with('success', "Template '{$template->name}' duplicated successfully!");
    }

    /**
     * Store custom email template.
     */
    public function storeTemplate(Request $request)
    {
        $this->ensureAdminAccess();

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
        ]);

        EmailTemplate::create([
            'name' => $request->name,
            'category' => $request->category,
            'subject' => $request->subject,
            'body_html' => $request->body_html,
            'is_preset' => false,
        ]);

        return back()->with('success', "Email Template '{$request->name}' saved successfully!");
    }

    /**
     * Delete email template.
     */
    public function destroyTemplate(EmailTemplate $template)
    {
        $this->ensureAdminAccess();
        $template->delete();

        return back()->with('success', 'Email template deleted successfully.');
    }

    /**
     * Display full email audit logs table.
     */
    public function logs(Request $request)
    {
        $this->ensureAdminAccess();

        $query = EmailLog::query()->latest('sent_at');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('to_addresses', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('mailer', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.email-composer.logs', compact('logs'));
    }
}
