<?php

namespace App\Services\Admin\Finance;

use App\Models\StudentAccount;
use Illuminate\Support\Collection;

class AdminFinanceService
{
    public function getFamilyAccounts(StudentAccount $account): Collection
    {
        $applicant = $account->student?->applicant ?? $account->applicant;
        if (! $applicant) {
            return collect([$account->load('payments')]);
        }

        return StudentAccount::with(['student.applicant', 'payments'])
            ->where(function ($query) use ($applicant): void {
                if ($applicant->family_application_id) {
                    $query->whereHas('student.applicant', function ($applicantQuery) use ($applicant): void {
                        $applicantQuery->where('family_application_id', $applicant->family_application_id);
                    });

                    return;
                }

                $query->whereHas('student.applicant', function ($applicantQuery) use ($applicant): void {
                    $applicantQuery->where('user_id', $applicant->user_id);
                });
            })
            ->orderBy('id')
            ->get();
    }

    public function paymentReminderHtml(
        string $studentName,
        string $balance,
        string $overdueList,
        StudentAccount $account
    ): string {
        $schoolYear = $account->school_year ?? config('services.school.year', '2026-2027');
        $overdueRow = $overdueList
            ? '<tr><td style="font-size:13px;color:#92400e;padding:5px 0;">Overdue Months</td><td style="font-size:13px;color:#92400e;text-align:right;">'.$overdueList.'</td></tr>'
            : '';

        return '<!DOCTYPE html><html><body style="font-family:Inter,Arial,sans-serif;background:#f3f4f6;padding:40px 20px;">
        <table width="520" style="background:white;border-radius:16px;overflow:hidden;margin:0 auto;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
        <tr><td style="background:linear-gradient(135deg,#d97706,#92400e);padding:28px;text-align:center;">
            <h2 style="color:white;margin:0;font-size:18px;">Payment Reminder</h2>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:4px 0 0;">Al Munawwara Islamic School - SY '.$schoolYear.'</p>
        </td></tr>
        <tr><td style="padding:28px 36px;">
            <p style="color:#374151;font-size:14px;margin:0 0 20px;">Assalamualaikum,</p>
            <p style="color:#374151;font-size:14px;margin:0 0 20px;">This is a friendly reminder that the tuition account for <strong>'.$studentName.'</strong> has an outstanding balance:</p>
            <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:18px;margin-bottom:20px;">
                <table width="100%">
                    <tr><td style="font-size:13px;color:#92400e;padding:5px 0;">Remaining Balance</td><td style="font-size:18px;font-weight:800;color:#92400e;text-align:right;">PHP '.$balance.'</td></tr>
                    '.$overdueRow.'
                </table>
            </div>
            <p style="color:#6b7280;font-size:13px;">Please settle the outstanding balance at your earliest convenience. You may pay via GCash, Maya, or BDO bank transfer.</p>
            <p style="color:#6b7280;font-size:13px;margin-top:16px;">JazakAllahu khayran.</p>
        </td></tr>
        </table></body></html>';
    }
}
