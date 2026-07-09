<?php

namespace App\Services\Academic;

use App\Models\Academic\Section;
use App\Models\AdminAuditLog;
use Illuminate\Support\Str;

class SectionService
{
    public function create(array $data): Section
    {
        $section = Section::create($data);

        $this->log('section_created', true, "Created section: {$section->name} for grade level {$section->grade_level}");

        return $section;
    }

    public function update(Section $section, array $data): Section
    {
        $section->update($data);

        $this->log('section_updated', true, "Updated section: {$section->name} (Grade: {$section->grade_level})");

        return $section;
    }

    public function toggleStatus(Section $section): void
    {
        $newStatus = $section->status === 'active' ? 'inactive' : 'active';
        $section->update(['status' => $newStatus]);

        $this->log('section_status_toggled', true, "Toggled status for section {$section->name} to {$newStatus}");
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
