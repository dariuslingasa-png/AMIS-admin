<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            // Track which step the applicant last saved on
            $table->unsignedTinyInteger('last_step')->default(1)->after('school_year');

            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE enrollment_applicants MODIFY COLUMN status ENUM('draft','pending','submitted','under_review','approved','rejected') DEFAULT 'draft'");
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropColumn('last_step');
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement("ALTER TABLE enrollment_applicants MODIFY COLUMN status ENUM('pending','submitted','under_review','approved','rejected') DEFAULT 'pending'");
            }
        });
    }
};
