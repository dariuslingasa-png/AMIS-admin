<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('enrollment_applicants') || !Schema::hasColumn('enrollment_applicants', 'family_application_id')) {
            return;
        }

        DB::table('enrollment_applicants')
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('MIN(COALESCE(family_application_id, id)) as root_id'))
            ->groupBy('user_id')
            ->orderBy('user_id')
            ->chunk(100, function ($families) {
                foreach ($families as $family) {
                    DB::table('enrollment_applicants')
                        ->where('user_id', $family->user_id)
                        ->whereNull('family_application_id')
                        ->update(['family_application_id' => $family->root_id]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
