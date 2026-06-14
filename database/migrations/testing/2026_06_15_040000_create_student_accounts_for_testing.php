<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('discount_settings')) {
            Schema::create('discount_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('second_child_percentage')->default(10);
                $table->unsignedTinyInteger('third_child_percentage')->default(15);
                $table->unsignedTinyInteger('fourth_child_percentage')->default(20);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_accounts')) {
            Schema::create('student_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('enrollment_applicant_id')->constrained()->onDelete('cascade');
                $table->string('school_year', 20);
                $table->string('grade_level', 50);

                // Fee breakdown
                $table->decimal('tuition_fee', 10, 2);
                $table->decimal('monthly_tuition', 10, 2);
                $table->decimal('miscellaneous_fee', 10, 2)->default(1900.00);
                $table->decimal('books_fee', 10, 2);
                
                // Sibling discount fields
                $table->unsignedSmallInteger('sibling_order')->nullable();
                $table->string('discount_type', 50)->nullable();
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);

                $table->decimal('gross_total', 10, 2);
                $table->decimal('enrollment_fee_paid', 10, 2)->default(4000.00);
                $table->decimal('total_balance', 10, 2);

                // Running totals
                $table->decimal('amount_paid', 10, 2)->default(0.00);
                $table->decimal('remaining_balance', 10, 2);

                $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
                $table->timestamps();
            });
        }

        // Add sibling order to enrollment_applicants if not already present
        if (Schema::hasTable('enrollment_applicants') && !Schema::hasColumn('enrollment_applicants', 'sibling_order')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $table->unsignedSmallInteger('sibling_order')->nullable();
                $table->string('discount_type', 50)->nullable();
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->decimal('discount_amount', 10, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_accounts');
        Schema::dropIfExists('discount_settings');
        
        if (Schema::hasTable('enrollment_applicants')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $table->dropColumn(['sibling_order', 'discount_type', 'discount_percentage', 'discount_amount']);
            });
        }
    }
};
