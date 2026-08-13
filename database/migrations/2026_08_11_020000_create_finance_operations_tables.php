<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 40)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('payment_submission_id')->nullable()->unique();
            $table->unsignedBigInteger('receipt_submission_id')->nullable()->index();
            $table->string('source', 10);
            $table->string('payment_method', 30);
            $table->string('reference_number', 150)->nullable();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('PHP');
            $table->dateTime('transaction_at');
            $table->string('status', 20)->default('APPROVED');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('allocation_snapshot');
            $table->decimal('advance_credit', 14, 2)->default(0);
            $table->decimal('family_balance_after', 14, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->text('correction_reason')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index(['source', 'status', 'transaction_at']);
            $table->index(['user_id', 'transaction_at']);
            $table->index(['payment_method', 'reference_number']);
        });

        Schema::create('finance_official_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('official_receipt_number', 40)->unique();
            $table->foreignId('finance_transaction_id')->unique()->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('ISSUED');
            $table->json('snapshot');
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_advance_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('finance_transaction_id')->unique()->constrained()->restrictOnDelete();
            $table->decimal('original_amount', 14, 2);
            $table->decimal('remaining_amount', 14, 2);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('finance_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('receipt_submission_id')->nullable()->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80);
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->string('reference_number', 150)->nullable();
            $table->json('allocation')->nullable();
            $table->json('changes')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event', 'created_at']);
        });

        Schema::create('finance_parent_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_transaction_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('type', 40);
            $table->string('channel', 20)->default('EMAIL');
            $table->string('status', 20)->default('QUEUED');
            $table->json('payload');
            $table->text('error')->nullable();
            $table->timestamp('queued_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        if (Schema::hasTable('student_account_payments') && ! Schema::hasColumn('student_account_payments', 'finance_transaction_id')) {
            $hasPaymentSubmission = Schema::hasColumn('student_account_payments', 'payment_submission_id');
            Schema::table('student_account_payments', function (Blueprint $table) use ($hasPaymentSubmission) {
                $column = $table->foreignId('finance_transaction_id')->nullable();
                if ($hasPaymentSubmission) {
                    $column->after('payment_submission_id');
                }
                $column->constrained('finance_transactions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('student_account_payments') && Schema::hasColumn('student_account_payments', 'finance_transaction_id')) {
            Schema::table('student_account_payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('finance_transaction_id');
            });
        }

        Schema::dropIfExists('finance_parent_notifications');
        Schema::dropIfExists('finance_audit_logs');
        Schema::dropIfExists('finance_advance_credits');
        Schema::dropIfExists('finance_official_receipts');
        Schema::dropIfExists('finance_transactions');
    }
};
