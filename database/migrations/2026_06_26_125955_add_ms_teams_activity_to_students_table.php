<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Last time the student had any activity in the Teams app
            // (message sent, meeting joined, call made, file accessed, etc.)
            // Sourced from MS Graph: /reports/getTeamsUserActivityUserDetail
            if (! Schema::hasColumn('students', 'ms_teams_last_activity_at')) {
                $table->timestamp('ms_teams_last_activity_at')->nullable()->after('ms_teams_enrolled_at');
            }
            // Total meetings joined ever (from Teams activity report)
            if (! Schema::hasColumn('students', 'ms_teams_meetings_attended')) {
                $table->unsignedInteger('ms_teams_meetings_attended')->default(0)->after('ms_teams_last_activity_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumnIfExists('ms_teams_last_activity_at');
            $table->dropColumnIfExists('ms_teams_meetings_attended');
        });
    }
};
