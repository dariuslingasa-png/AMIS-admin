<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TicketReplyMailable;
use App\Models\AdminAuditLog;
use App\Models\SupportSetting;
use App\Models\SupportTicket;
use App\Services\System\SmartSmtpRotatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminSupportTicketController extends Controller
{
    /**
     * Display a listing of support tickets.
     */
    public function index(Request $request)
    {
        $query = SupportTicket::query()->latest();

        // 1. Search Query Filter
        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            // Check if user is searching for a dynamic Reference Number like AMIS-20260624-0001
            if (preg_match('/AMIS-\d{8}-(\d+)/i', $search, $matches)) {
                $ticketId = (int) $matches[1];
                $query->where('id', $ticketId);
            } else {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('student_full_name', 'like', "%{$search}%")
                        ->orWhere('amis_id', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }
        }

        // 2. Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Concern Type Filter
        if ($request->filled('concern_type')) {
            $query->where('concern_type', $request->concern_type);
        }

        // 4. Grade Level Filter
        if ($request->filled('grade')) {
            $query->where('grade_level', $request->grade);
        }

        // Paginate results
        $tickets = $query->paginate(15)->withQueryString();

        // Concern Types for filter dropdown
        $concernTypes = [
            'Forgot Password',
            'Resend Credentials',
            'Enrollment Concern',
            'Payment Concern',
            'Microsoft Account Issue',
            'General Inquiry',
        ];

        // Grade Levels for filter dropdown
        $gradeLevels = [
            'Kinder 1',
            'Kinder 2',
            'Grade 1',
            'Grade 2',
            'Grade 3',
            'Grade 4',
            'Grade 5',
            'Grade 6',
            'Grade 7',
            'Grade 8',
            'Grade 9',
            'Grade 10',
            'Grade 11',
            'Grade 12',
        ];

        // Status choices & labels
        $statusLabels = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
        ];

        $statusBadges = [
            'open' => 'rose',
            'in_progress' => 'amber',
            'resolved' => 'emerald',
        ];

        // KPI Counts
        $kpis = [
            'total' => SupportTicket::count(),
            'open' => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'resolved' => SupportTicket::where('status', 'resolved')->count(),
        ];

        return view('admin.support.index', compact(
            'tickets',
            'concernTypes',
            'gradeLevels',
            'statusLabels',
            'statusBadges',
            'kpis'
        ));
    }

    /**
     * Display the specified support ticket details.
     */
    public function show(SupportTicket $ticket)
    {
        $statusLabels = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
        ];

        $statusBadges = [
            'open' => 'rose',
            'in_progress' => 'amber',
            'resolved' => 'emerald',
        ];

        return view('admin.support.show', compact('ticket', 'statusLabels', 'statusBadges'));
    }

    /**
     * Update the ticket's status.
     */
    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'status' => 'required|string|in:open,in_progress,resolved',
        ]);

        $oldStatus = $ticket->status;
        $ticket->update(['status' => $request->status]);

        // Audit Log entry
        AdminAuditLog::record(
            'support_ticket_status_updated',
            true,
            "Support Ticket #{$ticket->id} ({$ticket->reference_number}) status updated from '{$oldStatus}' to '{$request->status}' by Admin.",
            [
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status,
            ]
        );

        return back()->with('success', 'Ticket status updated to '.ucfirst(str_replace('_', ' ', $request->status)).' successfully.');
    }

    /**
     * Stream support ticket screenshot securely.
     */
    public function viewScreenshot(Request $request)
    {
        $path = $request->query('path');
        if (blank($path)) {
            abort(400, 'Path is required.');
        }

        // Prevent directory traversal
        if (str_contains($path, '..')) {
            abort(400, 'Invalid path.');
        }

        // Search path list
        $searchPaths = [
            base_path('../amis_support/storage/app/public/'.ltrim($path, '/')),
            base_path('../../amis_support/storage/app/public/'.ltrim($path, '/')),
            base_path('../support.amis.edu.ph/storage/app/public/'.ltrim($path, '/')),
            base_path('../../support.amis.edu.ph/storage/app/public/'.ltrim($path, '/')),
            storage_path('app/public/'.ltrim($path, '/')),
            public_path('storage/'.ltrim($path, '/')),
        ];

        $filePath = null;
        foreach ($searchPaths as $p) {
            if (file_exists($p) && is_file($p)) {
                $filePath = $p;
                break;
            }
        }

        if (! $filePath) {
            abort(404, 'Support attachment file not found.');
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'application/octet-stream'
        };

        return response()->file($filePath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Show support center settings page for department emails.
     */
    public function settings()
    {
        $concernTypes = [
            'Forgot Password',
            'Resend Credentials',
            'Enrollment Concern',
            'Payment Concern',
            'Microsoft Account Issue',
            'Document Request',
            'General Inquiry',
        ];

        $settings = [];
        foreach ($concernTypes as $type) {
            $settings[$type] = SupportSetting::getValue('support_email_'.$type);
        }

        return view('admin.support.settings', compact('concernTypes', 'settings'));
    }

    /**
     * Save support center settings.
     */
    public function saveSettings(Request $request)
    {
        $concernTypes = [
            'Forgot Password',
            'Resend Credentials',
            'Enrollment Concern',
            'Payment Concern',
            'Microsoft Account Issue',
            'Document Request',
            'General Inquiry',
        ];

        $rules = [];
        foreach ($concernTypes as $type) {
            $rules[str_replace(' ', '_', $type)] = 'nullable|email|max:255';
        }

        $validated = $request->validate($rules);

        foreach ($concernTypes as $type) {
            $inputName = str_replace(' ', '_', $type);
            $email = $validated[$inputName] ?? null;
            SupportSetting::setValue('support_email_'.$type, $email);
        }

        AdminAuditLog::record('support_settings_updated', true, 'Updated support department notification emails.');

        return redirect()->route('admin.support.index')->with('success', 'Support department settings saved successfully.');
    }

    /**
     * Send email reply to ticket requester with image/attachment upload support.
     */
    public function reply(Request $request, SupportTicket $ticket)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx|max:10240',
            'status' => 'nullable|string|in:open,in_progress,resolved',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = 'reply_'.$ticket->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('support_replies', $fileName, 'public');
            $attachmentPath = storage_path('app/public/'.$path);
        }

        $mailable = new TicketReplyMailable(
            replySubject: $request->subject,
            replyMessage: $request->message,
            recipientName: $ticket->full_name ?? 'User',
            referenceNumber: $ticket->reference_number ?? ('AMIS-'.$ticket->id),
            attachmentPath: $attachmentPath
        );

        $rotator = app(SmartSmtpRotatorService::class);
        $result = $rotator->sendMail($ticket->email, $mailable);

        if ($request->filled('status')) {
            $ticket->update(['status' => $request->status]);
        }

        AdminAuditLog::record(
            'support_ticket_reply_sent',
            true,
            "Sent reply email to {$ticket->email} for Ticket #{$ticket->id} using {$result['mailer_used']}.",
            ['ticket_id' => $ticket->id, 'mailer' => $result['mailer_used']]
        );

        return back()->with('success', "Reply email dispatched successfully to {$ticket->email} using SMTP ({$result['mailer_used']})!");
    }
}
