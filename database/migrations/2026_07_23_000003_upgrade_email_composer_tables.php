<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bulk_email_campaigns')) {
            Schema::table('bulk_email_campaigns', function (Blueprint $table) {
                if (!Schema::hasColumn('bulk_email_campaigns', 'cc_emails')) {
                    $table->text('cc_emails')->nullable()->after('sender_email');
                }
                if (!Schema::hasColumn('bulk_email_campaigns', 'bcc_emails')) {
                    $table->text('bcc_emails')->nullable()->after('cc_emails');
                }
                if (!Schema::hasColumn('bulk_email_campaigns', 'sender_name')) {
                    $table->string('sender_name')->default('AMIS Information Technology')->after('sender_email');
                }
            });
        }

        if (Schema::hasTable('email_logs')) {
            Schema::table('email_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('email_logs', 'cc_addresses')) {
                    $table->text('cc_addresses')->nullable()->after('to_addresses');
                }
                if (!Schema::hasColumn('email_logs', 'bcc_addresses')) {
                    $table->text('bcc_addresses')->nullable()->after('cc_addresses');
                }
                if (!Schema::hasColumn('email_logs', 'template_name')) {
                    $table->string('template_name')->nullable()->after('subject');
                }
                if (!Schema::hasColumn('email_logs', 'attachments_count')) {
                    $table->integer('attachments_count')->default(0)->after('status');
                }
            });
        }

        if (!Schema::hasTable('email_drafts')) {
            Schema::create('email_drafts', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('subject')->nullable();
                $table->longText('body_html')->nullable();
                $table->string('recipient_type')->default('students');
                $table->string('recipient_filter')->nullable();
                $table->text('cc_emails')->nullable();
                $table->text('bcc_emails')->nullable();
                $table->json('attachments_json')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('email_drafts');
    }
};
