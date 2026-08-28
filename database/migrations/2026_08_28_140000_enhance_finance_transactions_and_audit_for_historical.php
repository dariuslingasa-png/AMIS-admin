<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_transactions')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('finance_transactions', 'academic_year')) {
                    $table->string('academic_year', 20)->default('2026-2027')->after('source')->nullable();
                }
                if (! Schema::hasColumn('finance_transactions', 'fee_category')) {
                    $table->string('fee_category', 50)->default('TUITION')->after('academic_year')->nullable();
                }
                if (! Schema::hasColumn('finance_transactions', 'student_id')) {
                    $table->unsignedBigInteger('student_id')->after('user_id')->nullable()->index();
                }
                if (! Schema::hasColumn('finance_transactions', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                }
            });

            // Expand source column length to 30 chars if MySQL
            try {
                DB::statement("ALTER TABLE finance_transactions MODIFY COLUMN source VARCHAR(30) NOT NULL DEFAULT 'ONSITE'");
            } catch (\Throwable $e) {
                // In SQLite/testing, MODIFY COLUMN is ignored
            }
        }

        if (Schema::hasTable('finance_audit_logs')) {
            Schema::table('finance_audit_logs', function (Blueprint $table) {
                if (! Schema::hasColumn('finance_audit_logs', 'academic_year')) {
                    $table->string('academic_year', 20)->nullable()->after('event');
                }
                if (! Schema::hasColumn('finance_audit_logs', 'student_id')) {
                    $table->unsignedBigInteger('student_id')->nullable()->after('finance_transaction_id')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('finance_transactions')) {
            Schema::table('finance_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('finance_transactions', 'updated_by')) {
                    $table->dropConstrainedForeignId('updated_by');
                }
                if (Schema::hasColumn('finance_transactions', 'student_id')) {
                    $table->dropColumn('student_id');
                }
                if (Schema::hasColumn('finance_transactions', 'fee_category')) {
                    $table->dropColumn('fee_category');
                }
                if (Schema::hasColumn('finance_transactions', 'academic_year')) {
                    $table->dropColumn('academic_year');
                }
            });
        }

        if (Schema::hasTable('finance_audit_logs')) {
            Schema::table('finance_audit_logs', function (Blueprint $table) {
                if (Schema::hasColumn('finance_audit_logs', 'student_id')) {
                    $table->dropColumn('student_id');
                }
                if (Schema::hasColumn('finance_audit_logs', 'academic_year')) {
                    $table->dropColumn('academic_year');
                }
            });
        }
    }
};
