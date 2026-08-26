<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_family_credit_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('credit_source_type', 20);
            $table->unsignedBigInteger('credit_source_id');
            $table->foreignId('soa_monthly_billing_id');
            $table->foreignId('student_account_payment_id')->nullable();
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('APPLIED');
            $table->timestamp('applied_at');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['credit_source_type', 'credit_source_id'], 'family_credit_source_index');
            $table->unique(
                ['credit_source_type', 'credit_source_id', 'soa_monthly_billing_id'],
                'family_credit_source_billing_unique'
            );
            $table->foreign('user_id', 'ffca_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('soa_monthly_billing_id', 'ffca_billing_fk')->references('id')->on('soa_monthly_billings')->restrictOnDelete();
            $table->foreign('student_account_payment_id', 'ffca_payment_fk')->references('id')->on('student_account_payments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_family_credit_applications');
    }
};
