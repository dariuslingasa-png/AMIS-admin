<?php

namespace App\Services\Admin\Enrollment;

use App\Models\EnrollmentApplicant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnrollmentAnalyticsService
{
    public function reportQuery(Request $request): Builder
    {
        $query = EnrollmentApplicant::with('user', 'payment')
            ->whereNotIn('status', ['draft']);

        if ($request->filled('search')) {
            $s = (string) $request->input('search');
            $query->where(function (Builder $q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                    ->orWhere('last_name', 'like', "%{$s}%")
                    ->orWhere('middle_name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhereHas('user', fn (Builder $u) => $u->where('email', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('grade')) {
            $query->where('grade_level', (string) $request->input('grade'));
        }

        if ($request->filled('type')) {
            $query->where('student_type', (string) $request->input('type'));
        }

        if ($request->filled('learning_mode')) {
            $mode = (string) $request->input('learning_mode');
            if ($mode === 'f2f') {
                $query->where('learning_mode', 'Face-to-Face');
            } elseif ($mode === 'flexible_1st') {
                $query->where('learning_mode', 'like', '%1st Shift%');
            } elseif ($mode === 'flexible_2nd') {
                $query->where('learning_mode', 'like', '%2nd Shift%');
            }
        }

        if ($request->filled('payment_status')) {
            $paymentStatus = (string) $request->payment_status;

            if ($paymentStatus === 'missing') {
                $query->where(function (Builder $query) {
                    $query->whereDoesntHave('payment')
                        ->orWhereHas('payment', fn (Builder $payment) => $payment->whereNull('receipt_url'));
                });
            } else {
                $query->whereHas('payment', fn (Builder $payment) => $payment->where('status', $paymentStatus));
            }
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', (string) $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', (string) $request->input('to'));
        }

        return $query;
    }

    public function locationCounts(string $column): Collection
    {
        return EnrollmentApplicant::select($column, DB::raw('COUNT(*) as total'))
            ->whereNotIn('status', ['draft'])
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit(12)
            ->pluck('total', $column);
    }

    public function gradeSlotData(string $schoolYear, Collection $gradeCounts): Collection
    {
        if (
            ! Schema::hasTable('grade_levels')
            || ! Schema::hasColumn('grade_levels', 'name')
            || ! Schema::hasColumn('grade_levels', 'capacity')
            || ! Schema::hasColumn('grade_levels', 'enrolled_count')
        ) {
            return $this->fallbackGradeRows($gradeCounts);
        }

        $query = DB::table('grade_levels');

        if (Schema::hasColumn('grade_levels', 'school_year')) {
            $query->where('school_year', $schoolYear);
        }

        if (Schema::hasColumn('grade_levels', 'is_active')) {
            $query->where('is_active', true);
        }

        if (Schema::hasColumn('grade_levels', 'sort_order')) {
            $query->orderBy('sort_order');
        } else {
            $query->orderBy('name');
        }

        $rows = $query
            ->get()
            ->map(function ($grade) use ($gradeCounts) {
                $capacity = (int) $grade->capacity;
                $enrolled = (int) $grade->enrolled_count;
                $available = max(0, $capacity - $enrolled);
                $usedPercent = $capacity > 0 ? min(100, round(($enrolled / $capacity) * 100)) : 0;
                $applicantCount = (int) ($gradeCounts[$grade->name] ?? 0);

                return [
                    'grade' => $grade->name,
                    'capacity' => $capacity,
                    'enrolled' => $enrolled,
                    'available' => $available,
                    'used_percent' => $usedPercent,
                    'applicant_count' => $applicantCount,
                    'status' => $available <= 0 ? 'Full' : ($available <= 5 ? 'Limited' : 'Open'),
                ];
            });

        return $rows->isNotEmpty() ? $rows : $this->fallbackGradeRows($gradeCounts);
    }

    public function shiftSlotData(string $schoolYear): Collection
    {
        if (
            ! Schema::hasTable('grade_shift_slots')
            || ! Schema::hasTable('grade_levels')
            || ! Schema::hasTable('enrollment_shifts')
            || ! Schema::hasColumn('grade_shift_slots', 'grade_level_id')
            || ! Schema::hasColumn('grade_shift_slots', 'enrollment_shift_id')
            || ! Schema::hasColumn('grade_shift_slots', 'capacity')
            || ! Schema::hasColumn('grade_shift_slots', 'enrolled_count')
            || ! Schema::hasColumn('grade_levels', 'id')
            || ! Schema::hasColumn('grade_levels', 'name')
            || ! Schema::hasColumn('enrollment_shifts', 'id')
            || ! Schema::hasColumn('enrollment_shifts', 'name')
        ) {
            return collect();
        }

        $query = DB::table('grade_shift_slots as slots')
            ->join('grade_levels as grades', 'grades.id', '=', 'slots.grade_level_id')
            ->join('enrollment_shifts as shifts', 'shifts.id', '=', 'slots.enrollment_shift_id');

        if (Schema::hasColumn('grade_shift_slots', 'school_year')) {
            $query->where('slots.school_year', $schoolYear);
        }

        if (Schema::hasColumn('grade_shift_slots', 'is_active')) {
            $query->where('slots.is_active', true);
        }

        if (Schema::hasColumn('grade_levels', 'is_active')) {
            $query->where('grades.is_active', true);
        }

        if (Schema::hasColumn('enrollment_shifts', 'is_active')) {
            $query->where('shifts.is_active', true);
        }

        if (Schema::hasColumn('grade_levels', 'sort_order')) {
            $query->orderBy('grades.sort_order');
        } else {
            $query->orderBy('grades.name');
        }

        if (Schema::hasColumn('enrollment_shifts', 'start_time')) {
            $query->orderBy('shifts.start_time');
        } else {
            $query->orderBy('shifts.name');
        }

        $columns = [
            'grades.name as grade',
            'shifts.name as shift',
            'slots.capacity',
            'slots.enrolled_count',
        ];

        if (Schema::hasColumn('enrollment_shifts', 'start_time')) {
            $columns[] = 'shifts.start_time';
        }

        if (Schema::hasColumn('enrollment_shifts', 'end_time')) {
            $columns[] = 'shifts.end_time';
        }

        return $query->get($columns)
            ->map(function ($slot) {
                $capacity = (int) $slot->capacity;
                $enrolled = (int) $slot->enrolled_count;
                $available = max(0, $capacity - $enrolled);

                return [
                    'grade' => $slot->grade,
                    'shift' => $slot->shift,
                    'time' => isset($slot->start_time, $slot->end_time)
                        ? substr((string) $slot->start_time, 0, 5).' - '.substr((string) $slot->end_time, 0, 5)
                        : null,
                    'capacity' => $capacity,
                    'enrolled' => $enrolled,
                    'available' => $available,
                    'used_percent' => $capacity > 0 ? min(100, round(($enrolled / $capacity) * 100)) : 0,
                    'status' => $available <= 0 ? 'Full' : ($available <= 5 ? 'Limited' : 'Open'),
                ];
            });
    }

    public function learningModeDemandData(string $schoolYear): Collection
    {
        $query = EnrollmentApplicant::select('grade_level', 'learning_mode', DB::raw('COUNT(*) as total'))
            ->whereNotIn('status', ['draft'])
            ->whereNotNull('grade_level');

        if (Schema::hasColumn('enrollment_applicants', 'school_year')) {
            $query->where('school_year', $schoolYear);
        }

        return $query
            ->groupBy('grade_level', 'learning_mode')
            ->get()
            ->groupBy('grade_level')
            ->map(function (Collection $rows) {
                return [
                    'face_to_face' => (int) $rows
                        ->filter(fn ($row) => (string) $row->learning_mode === 'Face-to-Face')
                        ->sum('total'),
                    'first_shift' => (int) $rows
                        ->filter(fn ($row) => str_contains((string) $row->learning_mode, '1st Shift'))
                        ->sum('total'),
                    'second_shift' => (int) $rows
                        ->filter(fn ($row) => str_contains((string) $row->learning_mode, '2nd Shift'))
                        ->sum('total'),
                ];
            });
    }

    public function slotMatrixData(Collection $gradeSlots, Collection $shiftSlots, ?Collection $demandCounts = null): Collection
    {
        $shiftGroups = $shiftSlots->groupBy('grade');
        $demandCounts ??= collect();

        return $gradeSlots->map(function (array $gradeSlot) use ($shiftGroups, $demandCounts) {
            $gradeShifts = $shiftGroups->get($gradeSlot['grade'], collect());
            $gradeDemand = $demandCounts->get($gradeSlot['grade'], []);

            return [
                'grade' => $gradeSlot['grade'],
                'applicant_count' => $gradeSlot['applicant_count'] ?? 0,
                'face_to_face' => $this->withDemand($gradeSlot, (int) ($gradeDemand['face_to_face'] ?? 0)),
                'first_shift' => $this->withDemand($this->findShiftSlot($gradeShifts, 'first'), (int) ($gradeDemand['first_shift'] ?? 0)),
                'second_shift' => $this->withDemand($this->findShiftSlot($gradeShifts, 'second'), (int) ($gradeDemand['second_shift'] ?? 0)),
            ];
        });
    }

    private function findShiftSlot(Collection $slots, string $target): array
    {
        $slot = $slots->first(function (array $slot) use ($target) {
            $name = strtolower((string) $slot['shift']);

            return $target === 'first'
                ? str_contains($name, '1') || str_contains($name, 'first')
                : str_contains($name, '2') || str_contains($name, 'second');
        });

        if ($slot) {
            return $slot;
        }

        return [
            'capacity' => 0,
            'enrolled' => 0,
            'available' => 0,
            'used_percent' => 0,
            'status' => 'No slot config',
        ];
    }

    private function withDemand(array $slot, int $applicants): array
    {
        $slot['applicants'] = $applicants;

        return $slot;
    }

    private function fallbackGradeRows(Collection $gradeCounts): Collection
    {
        return $gradeCounts->map(fn ($count, $grade) => [
            'grade' => $grade ?: 'Not Set',
            'capacity' => 0,
            'enrolled' => 0,
            'available' => 0,
            'used_percent' => 0,
            'applicant_count' => (int) $count,
            'status' => 'No slot config',
        ])->values();
    }
}
