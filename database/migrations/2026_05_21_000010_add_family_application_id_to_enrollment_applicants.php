<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('enrollment_applicants')) {
            return;
        }

        if (! Schema::hasColumn('enrollment_applicants', 'family_application_id')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $table->unsignedBigInteger('family_application_id')->nullable()->after('user_id')->index();
            });
        }

        DB::table('enrollment_applicants')
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('MIN(id) as root_id'))
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

        DB::table('enrollment_applicants')
            ->whereNull('family_application_id')
            ->orderBy('id')
            ->pluck('id')
            ->each(function ($id) {
                DB::table('enrollment_applicants')
                    ->where('id', $id)
                    ->update(['family_application_id' => $id]);
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('enrollment_applicants') || ! Schema::hasColumn('enrollment_applicants', 'family_application_id')) {
            return;
        }

        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropIndex(['family_application_id']);
            $table->dropColumn('family_application_id');
        });
    }
};
