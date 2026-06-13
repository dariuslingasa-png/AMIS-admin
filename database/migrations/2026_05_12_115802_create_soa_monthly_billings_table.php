<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('soa_monthly_billings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');

            $table->tinyInteger('month_number');  // 1=June, 2=July, ... 10=March
            $table->string('month_name', 20);     // June, July, etc.
            $table->date('due_date');             // e.g. 2026-06-15
            $table->decimal('amount_due', 10, 2);
            $table->string('description', 255);   // "Tuition + Misc + Books" or "Monthly Tuition"

            $table->enum('status', ['unpaid', 'paid', 'overdue'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('soa_monthly_billings');
    }
};
