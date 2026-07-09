<?php

namespace App\Services\Academic;

use App\Models\Academic\SchoolYear;
use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SchoolYearService
{
    public function create(array $data): SchoolYear
    {
        return DB::transaction(function () use ($data) {
            if (!empty($data['is_active'])) {
                SchoolYear::query()->update(['is_active' => false]);
            }

            $schoolYear = SchoolYear::create($data);

            $this->log('school_year_created', true, "Created school year: {$schoolYear->code}");

            return $schoolYear;
        });
    }

    public function update(SchoolYear $schoolYear, array $data): SchoolYear
    {
        return DB::transaction(function () use ($schoolYear, $data) {
            if (!empty($data['is_active'])) {
                SchoolYear::query()->where('id', '!=', $schoolYear->id)->update(['is_active' => false]);
            }

            $schoolYear->update($data);

            $this->log('school_year_updated', true, "Updated school year: {$schoolYear->code}");

            return $schoolYear;
        });
    }

    public function toggleActive(SchoolYear $schoolYear): void
    {
        DB::transaction(function () use ($schoolYear) {
            if ($schoolYear->is_active) {
                $schoolYear->update(['is_active' => false]);
            } else {
                SchoolYear::query()->update(['is_active' => false]);
                $schoolYear->update(['is_active' => true]);
            }

            $this->log('school_year_active_toggled', true, "Toggled active status for school year: {$schoolYear->code}");
        });
    }

    public function toggleStatus(SchoolYear $schoolYear): void
    {
        $newStatus = $schoolYear->status === 'active' ? 'inactive' : 'active';
        $schoolYear->update(['status' => $newStatus]);

        $this->log('school_year_status_toggled', true, "Toggled status for school year: {$schoolYear->code} to {$newStatus}");
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
