<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $formatInitial = function (?string $val): ?string {
            if ($val === null) return null;
            $trimmed = trim($val);
            if ($trimmed === '') return null;
            $firstChar = mb_substr($trimmed, 0, 1, 'UTF-8');
            return mb_strtoupper(($firstChar === '.') ? '.' : $firstChar . '.', 'UTF-8');
        };

        if (Schema::hasTable('enrollment_applicants')) {
            DB::table('enrollment_applicants')->chunkById(100, function ($applicants) use ($formatInitial) {
                foreach ($applicants as $app) {
                    $mInit = $formatInitial($app->middle_name ?? null);
                    $fInit = $formatInitial($app->father_middle_name ?? null);
                    $moInit = $formatInitial($app->mother_middle_name ?? null);

                    $updates = [];
                    if ($mInit !== $app->middle_name) $updates['middle_name'] = $mInit;
                    if ($fInit !== $app->father_middle_name) $updates['father_middle_name'] = $fInit;
                    if ($moInit !== $app->mother_middle_name) $updates['mother_middle_name'] = $moInit;

                    if (!empty($updates)) {
                        DB::table('enrollment_applicants')->where('id', $app->id)->update($updates);
                    }
                }
            });
        }

        if (Schema::hasTable('students')) {
            if (Schema::hasColumn('students', 'middle_name')) {
                DB::table('students')->chunkById(100, function ($students) use ($formatInitial) {
                    foreach ($students as $st) {
                        $mInit = $formatInitial($st->middle_name ?? null);
                        if ($mInit !== $st->middle_name) {
                            DB::table('students')->where('id', $st->id)->update(['middle_name' => $mInit]);
                        }
                    }
                });
            }
        }
    }

    public function down(): void
    {
        // Non-reversible format change
    }
};
