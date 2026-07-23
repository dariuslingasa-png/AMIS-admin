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
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! Schema::hasColumn('payments', 'or_number')) {
                    $table->string('or_number', 100)->nullable()->after('reference_no');
                }
            });
        }

        if (Schema::hasTable('student_account_payments')) {
            Schema::table('student_account_payments', function (Blueprint $table) {
                if (! Schema::hasColumn('student_account_payments', 'or_number')) {
                    $table->string('or_number', 100)->nullable()->after('reference_no');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'or_number')) {
                    $table->dropColumn('or_number');
                }
            });
        }

        if (Schema::hasTable('student_account_payments')) {
            Schema::table('student_account_payments', function (Blueprint $table) {
                if (Schema::hasColumn('student_account_payments', 'or_number')) {
                    $table->dropColumn('or_number');
                }
            });
        }
    }
};
