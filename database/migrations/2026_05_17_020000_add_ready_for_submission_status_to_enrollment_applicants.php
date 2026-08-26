<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE enrollment_applicants MODIFY COLUMN status ENUM('draft','ready_for_submission','pending','submitted','under_review','approved','rejected') DEFAULT 'draft'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("UPDATE enrollment_applicants SET status = 'draft' WHERE status = 'ready_for_submission'");
            DB::statement("ALTER TABLE enrollment_applicants MODIFY COLUMN status ENUM('draft','pending','submitted','under_review','approved','rejected') DEFAULT 'draft'");
        }
    }
};
