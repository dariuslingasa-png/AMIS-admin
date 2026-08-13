<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_account_payments') && DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE student_account_payments MODIFY COLUMN status ENUM('pending', 'verified', 'rejected', 'reversed') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_account_payments') && DB::getDriverName() === 'mysql') {
            DB::table('student_account_payments')->where('status', 'reversed')->update(['status' => 'rejected']);
            DB::statement("ALTER TABLE student_account_payments MODIFY COLUMN status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
