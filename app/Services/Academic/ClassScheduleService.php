<?php

namespace App\Services\Academic;

use App\Models\Academic\ClassSchedule;
use App\Models\AdminAuditLog;
use Illuminate\Support\Str;

class ClassScheduleService
{
    public function create(array $data): ClassSchedule
    {
        $data['created_by'] = auth()->id();
        $classSchedule = ClassSchedule::create($data);

        $this->log('schedule_created', true, "Created class schedule for section {$classSchedule->section_id}: {$classSchedule->subject_name}");

        return $classSchedule;
    }

    public function update(ClassSchedule $classSchedule, array $data): ClassSchedule
    {
        $classSchedule->update($data);

        $this->log('schedule_updated', true, "Updated class schedule ID {$classSchedule->id}: {$classSchedule->subject_name}");

        return $classSchedule;
    }

    public function delete(ClassSchedule $classSchedule): void
    {
        $classSchedule->delete();

        $this->log('schedule_deleted', true, "Deleted class schedule ID {$classSchedule->id} for section {$classSchedule->section_id}");
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
