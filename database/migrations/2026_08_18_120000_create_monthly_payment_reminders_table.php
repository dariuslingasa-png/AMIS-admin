<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('monthly_payment_reminders')) {
            Schema::create('monthly_payment_reminders', function (Blueprint $table) {
                $table->id();
                $table->string('family_id')->nullable()->index();
                $table->string('billing_month', 7)->index(); // Format: '2026-08'
                $table->string('reminder_type', 50)->default('monthly_payment_reminder');
                $table->string('parent_name')->nullable();
                $table->string('parent_email')->index();
                $table->text('student_names')->nullable();
                $table->unsignedInteger('student_count')->default(1);
                $table->decimal('total_balance', 10, 2)->default(0.00);
                $table->enum('status', [
                    'PENDING',
                    'PROCESSING',
                    'SENT',
                    'FAILED',
                    'RETRY',
                    'SKIPPED_ALREADY_SENT',
                    'SKIPPED_FULLY_PAID',
                    'SKIPPED_NO_EMAIL',
                ])->default('PENDING')->index();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamp('sent_at')->nullable()->index();
                $table->timestamp('next_retry_at')->nullable()->index();
                $table->text('last_error')->nullable();
                $table->string('mail_transport', 50)->nullable();
                $table->string('smtp_message_id')->nullable();
                $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                // Idempotency: Exactly one reminder record per billing month + unique parent email
                $table->unique(['billing_month', 'parent_email', 'reminder_type'], 'mpr_month_email_type_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_payment_reminders');
    }
};
