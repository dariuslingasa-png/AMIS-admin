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
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable()->after('temp_password');
            }
            if (!Schema::hasColumn('students', 'temp_password_set_at')) {
                $table->timestamp('temp_password_set_at')->nullable()->after('password_changed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'password_changed_at')) {
                $table->dropColumn('password_changed_at');
            }
            if (Schema::hasColumn('students', 'temp_password_set_at')) {
                $table->dropColumn('temp_password_set_at');
            }
        });
    }
};
