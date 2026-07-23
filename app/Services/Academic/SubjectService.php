<?php

namespace App\Services\Academic;

use App\Models\Academic\Subject;
use App\Models\AdminAuditLog;
use Illuminate\Support\Str;

class SubjectService
{
    public function create(array $data): Subject
    {
        $subject = Subject::create($data);

        $this->log('subject_created', true, "Created subject: {$subject->name} ({$subject->code})");

        return $subject;
    }

    public function update(Subject $subject, array $data): Subject
    {
        $subject->update($data);

        $this->log('subject_updated', true, "Updated subject: {$subject->name} ({$subject->code})");

        return $subject;
    }

    public function toggleStatus(Subject $subject): void
    {
        $newStatus = $subject->status === 'active' ? 'inactive' : 'active';

        $updateData = ['status' => $newStatus];
        if ($newStatus === 'inactive') {
            $updateData['archived_at'] = now();
        } else {
            $updateData['archived_at'] = null;
        }

        $subject->update($updateData);

        $this->log('subject_status_toggled', true, "Toggled status for subject {$subject->name} ({$subject->code}) to {$newStatus}");
    }

    protected function log(string $event, bool $successful, string $message): void
    {
        $user = auth()->user();
        AdminAuditLog::create([
            'user_id' => $user?->id,
            'event' => $event,
            'email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
            'successful' => $successful,
            'message' => $message,
        ]);
    }
}
