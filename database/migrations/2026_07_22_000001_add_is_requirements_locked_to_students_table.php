<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('students', 'is_requirements_locked')) {
            Schema::table('students', function (Blueprint $table) {
                $table->boolean('is_requirements_locked')->default(false)->after('ms_account_enabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'is_requirements_locked')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('is_requirements_locked');
            });
        }
    }
};
