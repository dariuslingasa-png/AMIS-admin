<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bulk_email_campaigns')) {
            Schema::create('bulk_email_campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('subject');
                $table->longText('body_html');
                $table->string('sender_email')->nullable();
                $table->string('recipient_type')->default('students'); // students, faculty, staff, alumni, custom_emails, section, grade_level
                $table->string('recipient_filter')->nullable(); // grade level or section name or custom emails
                $table->integer('recipient_count')->default(0);
                $table->integer('sent_count')->default(0);
                $table->integer('failed_count')->default(0);
                $table->string('status')->default('draft'); // draft, queued, sending, completed, failed
                $table->json('attachments_json')->nullable();
                $table->text('error_log')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_email_campaigns');
    }
};
