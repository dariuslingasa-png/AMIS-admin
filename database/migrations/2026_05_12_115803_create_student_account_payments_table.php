<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_account_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('soa_monthly_billing_id')->nullable()->constrained()->onDelete('set null');

            $table->enum('method', ['gcash', 'maya', 'bdo', 'cash']);
            $table->decimal('amount', 10, 2);
            $table->string('receipt_url')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_account_payments');
    }
};
