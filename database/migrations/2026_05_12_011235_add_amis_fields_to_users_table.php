<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add columns that don't already exist
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('email');
            }
            if (!Schema::hasColumn('users', 'role')) {
                $table->enum('role', ['admin', 'staff', 'applicant'])->default('applicant')->after('username');
            }
            if (!Schema::hasColumn('users', 'account_status')) {
                $table->enum('account_status', ['pending', 'verified', 'suspended'])->default('pending')->after('role');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
