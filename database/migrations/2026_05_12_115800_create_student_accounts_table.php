<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_applicant_id')->constrained()->onDelete('cascade');
            $table->string('school_year', 20);
            $table->string('grade_level', 50);

            // Fee breakdown
            $table->decimal('tuition_fee', 10, 2);
            $table->decimal('monthly_tuition', 10, 2);   // tuition / 10
            $table->decimal('miscellaneous_fee', 10, 2)->default(1900.00);
            $table->decimal('books_fee', 10, 2);
            $table->decimal('gross_total', 10, 2);        // tuition + misc + books
            $table->decimal('enrollment_fee_paid', 10, 2)->default(4000.00);
            $table->decimal('total_balance', 10, 2);      // gross - enrollment_paid

            // Running totals (updated as payments come in)
            $table->decimal('amount_paid', 10, 2)->default(0.00);
            $table->decimal('remaining_balance', 10, 2);

            $table->enum('status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_accounts');
    }
};
