<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\MicrosoftGraphService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminStudentAccountController extends Controller
{
    public function resendCredentials(Student $student)
    {
        $applicant = $student->applicant;

        $tempPassword = $student->temp_password;
        $isHashed = str_starts_with($tempPassword ?? '', '$');
        
        if (blank($tempPassword) || $isHashed) {
            $tempPassword = '(Already changed / set by student)';
        }

        $student->update([
            'credentials_sent_at' => now(),
        ]);

        $parentEmail = $applicant->parent_email ?: $applicant->email;
        if ($parentEmail && $parentEmail !== 'NA') {
            $this->sendCredentialsEmail($applicant, $student, $tempPassword);
        }

        return back()->with('success', 'Credentials resent to ' . ($parentEmail ?? 'parent') . '.');
    }

    private function sendCredentialsEmail($applicant, Student $student, string $tempPassword): void
    {
        $parentEmail = $applicant->parent_email ?: $applicant->email;

        $html = '<!DOCTYPE html><html><body style="font-family:Inter,Arial,sans-serif;background:#f3f4f6;padding:40px 20px;">
        <table width="520" style="background:white;border-radius:16px;overflow:hidden;margin:0 auto;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
        <tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:28px;text-align:center;">
            <img src="' . asset('images/AMIS_Logo.png') . '" width="56" height="56" style="margin-bottom:10px;">
            <h2 style="color:white;margin:0;font-size:18px;">Student Credentials</h2>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:4px 0 0;">Al Munawwara Islamic School — SY ' . $student->school_year . '</p>
        </td></tr>
        <tr><td style="padding:28px 36px;">
            <p style="color:#374151;font-size:14px;margin:0 0 20px;">Here are the updated credentials for <strong>' . $applicant->first_name . ' ' . $applicant->last_name . '</strong>:</p>
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:18px;margin-bottom:20px;">
                <table width="100%">
                    <tr><td style="font-size:13px;color:#6b7280;padding:5px 0;width:140px;">Student Number</td><td style="font-size:15px;font-weight:800;color:#059669;">' . $student->student_number . '</td></tr>
                    <tr><td style="font-size:13px;color:#6b7280;padding:5px 0;">School Email</td><td style="font-size:14px;font-weight:600;color:#111827;">' . $student->school_email . '</td></tr>
                    <tr><td style="font-size:13px;color:#6b7280;padding:5px 0;">Password</td><td style="font-size:14px;font-weight:600;color:#111827;letter-spacing:0.05em;">' . $tempPassword . '</td></tr>
                </table>
            </div>
            <p style="color:#6b7280;font-size:13px;">Login at <a href="https://portal.office.com" style="color:#059669;">portal.office.com</a> and change your password on first login.</p>
        </td></tr>
        </table></body></html>';

        try {
            Mail::html($html, fn($m) => $m->to($parentEmail)->subject('AMIS — Student Credentials'));
        } catch (\Exception $e) {
            Log::error('Failed to resend credentials: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, Student $student)
    {
        $data = $request->validate([
            'status' => ['required', 'in:verified,suspended,graduated,transferred,withdrawn'],
        ]);

        $status = $data['status'];
        $user = $student->user;

        if (!$user) {
            return back()->withErrors(['error' => 'Student user record not found.']);
        }

        $user->update([
            'account_status' => $status,
        ]);

        $graph = new MicrosoftGraphService();
        $email = $student->school_email;
        $studentSkuId = config('services.microsoft.student_sku_id');
        $msError = null;

        try {
            if ($student->ms_user_id || $graph->userExists($email)) {
                $msUserId = $student->ms_user_id ?: $graph->resolveUserId($email);

                if ($status === 'verified') {
                    $graph->setAccountEnabled($msUserId, true);

                    if ($studentSkuId) {
                        $graph->assignLicense($msUserId, [$studentSkuId], []);
                        \App\Models\AdminAuditLog::record('license_assigned', true, "Assigned student license to student {$email} via status change to verified", [
                            'email' => $email,
                            'sku_id' => $studentSkuId,
                            'ms_user_id' => $msUserId,
                        ]);
                    }
                } else {
                    $graph->setAccountEnabled($msUserId, false);

                    if ($studentSkuId) {
                        try {
                            $graph->assignLicense($msUserId, [], [$studentSkuId]);
                            \App\Models\AdminAuditLog::record('license_revoked', true, "Revoked student license from student {$email} via status change to {$status}", [
                                'email' => $email,
                                'sku_id' => $studentSkuId,
                                'ms_user_id' => $msUserId,
                            ]);
                        } catch (\Throwable $licEx) {
                            // Ignore
                        }
                    }
                }
            } else {
                $msError = 'No Microsoft account exists for this user in Entra ID.';
            }
        } catch (\Throwable $exception) {
            $msError = $exception->getMessage();
            Log::error("Student Microsoft status sync failed for {$email}: {$msError}");
        }

        if ($msError) {
            return back()->with('success', "Student status updated locally to '{$status}', but Microsoft AD sync failed: {$msError}");
        }

        return back()->with('success', "Student status updated successfully to '{$status}' and synced to Microsoft AD.");
    }

    public function updateEmail(Request $request, Student $student)
    {
        $request->validate([
            'email' => [
                'required',
                'email',
                'regex:/^[a-zA-Z0-9._%+-]+@amis\.edu\.ph$/i',
                'unique:students,school_email,' . $student->id,
                'unique:users,email,' . ($student->user_id ?? 'NULL'),
            ],
        ], [
            'email.regex' => 'The email must be a valid @amis.edu.ph address.',
            'email.unique' => 'This school email is already assigned to another user.',
        ]);

        $oldEmail = $student->school_email;
        $newEmail = strtolower(trim($request->email));

        if ($oldEmail === $newEmail) {
            return back()->with('success', 'Email is already set to ' . $newEmail);
        }

        $student->update([
            'school_email' => $newEmail,
        ]);

        if ($student->user) {
            $student->user->update([
                'email' => $newEmail,
            ]);
        }

        $msError = null;

        try {
            $graph = new MicrosoftGraphService();
            if ($student->ms_user_id || $graph->userExists($oldEmail)) {
                $msUserId = $student->ms_user_id ?: $graph->resolveUserId($oldEmail);
                $token = (new \ReflectionMethod($graph, 'getAccessToken'))->invoke($graph);
                
                $mailNickname = strstr($newEmail, '@', true);

                $response = Http::withToken($token)
                    ->patch("https://graph.microsoft.com/v1.0/users/{$msUserId}", [
                        'userPrincipalName' => $newEmail,
                        'mail' => $newEmail,
                        'mailNickname' => $mailNickname,
                    ]);

                if ($response->failed()) {
                    $msError = $response->json()['error']['message'] ?? 'Microsoft API returned an error.';
                } else {
                    if (!$student->ms_user_id) {
                        $student->update(['ms_user_id' => $msUserId]);
                    }
                    \App\Models\AdminAuditLog::record('email_renamed', true, "Renamed student Microsoft account from {$oldEmail} to {$newEmail}", [
                        'old_email' => $oldEmail,
                        'new_email' => $newEmail,
                        'ms_user_id' => $msUserId,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $msError = $e->getMessage();
            Log::error("Failed to update student Microsoft email from {$oldEmail} to {$newEmail}: " . $msError);
        }

        if ($msError) {
            return back()->with('success', "School email updated locally to '{$newEmail}', but Microsoft AD update failed: {$msError}");
        }

        return back()->with('success', "School email successfully updated to '{$newEmail}' locally and synced to Microsoft AD.");
    }
}
