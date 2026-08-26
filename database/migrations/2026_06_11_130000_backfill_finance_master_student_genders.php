<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('finance_master_entry_students')
            || ! Schema::hasColumn('finance_master_entry_students', 'gender')
            || ! Schema::hasTable('finance_master_entries')
            || ! Schema::hasTable('payments')
            || ! Schema::hasTable('enrollment_applicants')
        ) {
            return;
        }

        DB::table('finance_master_entry_students')
            ->whereNull('gender')
            ->chunkById(200, function ($students) {
                $entryIds = $students->pluck('finance_master_entry_id')->filter()->unique()->values();
                if ($entryIds->isEmpty()) {
                    return;
                }

                $entries = DB::table('finance_master_entries')
                    ->whereIn('id', $entryIds)
                    ->pluck('payment_id', 'id');

                $payments = DB::table('payments')
                    ->whereIn('id', $entries->filter()->unique()->values())
                    ->get(['id', 'enrollment_applicant_id'])
                    ->keyBy('id');

                $seedApplicants = DB::table('enrollment_applicants')
                    ->whereIn('id', $payments->pluck('enrollment_applicant_id')->filter()->unique()->values())
                    ->get(['id', 'user_id', 'family_application_id']);

                $familyIds = $seedApplicants->pluck('family_application_id')->filter()->unique()->values();
                $userIds = $seedApplicants
                    ->filter(fn ($applicant) => blank($applicant->family_application_id))
                    ->pluck('user_id')
                    ->filter()
                    ->unique()
                    ->values();

                if ($familyIds->isEmpty() && $userIds->isEmpty()) {
                    return;
                }

                $familyApplicants = DB::table('enrollment_applicants')
                    ->whereNotNull('gender')
                    ->where(function ($query) use ($familyIds, $userIds) {
                        if ($familyIds->isNotEmpty()) {
                            $query->whereIn('family_application_id', $familyIds);
                        }

                        if ($userIds->isNotEmpty()) {
                            $query->orWhere(function ($userQuery) use ($userIds) {
                                $userQuery->whereIn('user_id', $userIds)
                                    ->whereNull('family_application_id');
                            });
                        }
                    })
                    ->get(['id', 'user_id', 'family_application_id', 'first_name', 'middle_name', 'last_name', 'gender']);

                $byFamily = $familyApplicants->whereNotNull('family_application_id')->groupBy('family_application_id');
                $byUser = $familyApplicants->whereNull('family_application_id')->groupBy('user_id');
                $seedById = $seedApplicants->keyBy('id');

                foreach ($students as $student) {
                    $paymentId = $entries[$student->finance_master_entry_id] ?? null;
                    $payment = $paymentId ? ($payments[$paymentId] ?? null) : null;
                    $seed = $payment ? ($seedById[$payment->enrollment_applicant_id] ?? null) : null;

                    if (! $seed) {
                        continue;
                    }

                    $applicants = $seed->family_application_id
                        ? ($byFamily[$seed->family_application_id] ?? collect())
                        : ($byUser[$seed->user_id] ?? collect());

                    $genderByName = $applicants->flatMap(function ($applicant) {
                        $fullName = $this->studentNameKey(collect([
                            $applicant->first_name,
                            $applicant->middle_name,
                            $applicant->last_name,
                        ])->filter()->join(' '));

                        $firstLast = $this->studentNameKey(collect([
                            $applicant->first_name,
                            $applicant->last_name,
                        ])->filter()->join(' '));

                        return collect([$fullName, $firstLast])
                            ->filter()
                            ->unique()
                            ->mapWithKeys(fn ($name) => [$name => $applicant->gender]);
                    });

                    $gender = $genderByName[$this->studentNameKey($student->student_name)] ?? null;
                    if (! $gender) {
                        continue;
                    }

                    DB::table('finance_master_entry_students')
                        ->where('id', $student->id)
                        ->whereNull('gender')
                        ->update(['gender' => $gender]);
                }
            });
    }

    public function down(): void
    {
        // Data backfill only; do not erase gender values on rollback.
    }

    private function studentNameKey(?string $name): string
    {
        return strtolower(preg_replace('/\s+/', ' ', trim((string) $name)));
    }
};
