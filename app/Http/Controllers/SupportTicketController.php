<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupportTicketController extends Controller
{
    public function index()
    {
        return view('support.index');
    }

    public function create()
    {
        $concernTypes = $this->concernTypes();
        $gradeLevels = $this->gradeLevels();

        return view('support.create', compact('concernTypes', 'gradeLevels'));
    }

    public function store(Request $request)
    {
        $concernTypes = $this->concernTypes();
        $gradeLevels = $this->gradeLevels();

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_number' => 'nullable|string|max:30',
            'fb_or_whatsapp' => 'nullable|string|max:255',
            'student_full_name' => 'nullable|string|max:255',
            'grade_level' => ['nullable', 'string', 'max:50', Rule::in($gradeLevels)],
            'amis_id' => 'nullable|string|max:50',
            'concern_type' => ['required', 'string', 'max:100', Rule::in($concernTypes)],
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'screenshot' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        // Force uppercase on string values except email
        $validated['full_name'] = mb_strtoupper($validated['full_name']);
        if (!empty($validated['contact_number'])) {
            $validated['contact_number'] = mb_strtoupper($validated['contact_number']);
        }
        if (!empty($validated['student_full_name'])) {
            $validated['student_full_name'] = mb_strtoupper($validated['student_full_name']);
        }
        if (!empty($validated['amis_id'])) {
            $validated['amis_id'] = mb_strtoupper($validated['amis_id']);
        }
        $validated['subject'] = mb_strtoupper($validated['subject']);
        $validated['description'] = mb_strtoupper($validated['description']);

        if ($request->hasFile('screenshot')) {
            $path = $request->file('screenshot')->store('support_attachments', 'public');
            $validated['screenshot_path'] = $path;
        }

        $ticket = SupportTicket::create($validated);

        return back()->with('success', 'Your support request has been submitted successfully. Our team will review it shortly!')
                     ->with('reference_number', $ticket->reference_number);
    }

    private function concernTypes(): array
    {
        return [
            'Forgot Password',
            'Resend Credentials',
            'Enrollment Concern',
            'Payment Concern',
            'Microsoft Account Issue',
            'General Inquiry',
        ];
    }

    private function gradeLevels(): array
    {
        return [
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
    }
}
