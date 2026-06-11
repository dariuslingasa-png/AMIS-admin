<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('finance_master_entries')) {
            Schema::create('finance_master_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
                $table->string('family_name');
                $table->string('remittance_source')->nullable();
                $table->string('reference_no')->nullable();
                $table->string('method');
                $table->date('payment_date');
                $table->decimal('amount', 12, 2);
                $table->string('or_number')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();

                $table->index(['family_name', 'reference_no', 'method', 'payment_date'], 'finance_master_entries_search_idx');
            });
        }

        if (! Schema::hasTable('finance_master_entry_students')) {
            Schema::create('finance_master_entry_students', function (Blueprint $table) {
                $table->id();
                $table->foreignId('finance_master_entry_id')->constrained('finance_master_entries')->onDelete('cascade');
                $table->string('student_name');
                $table->string('grade_level');
                $table->string('learning_mode');
                $table->string('student_type');
                $table->timestamps();

                $table->index(['student_name', 'grade_level'], 'finance_master_entry_stud_search_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_master_entry_students');
        Schema::dropIfExists('finance_master_entries');
    }
};
