<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('mailer', 50)->default('smtp')->index();
            $table->string('transport', 50)->default('smtp');
            $table->string('from_address', 255)->nullable();
            $table->text('to_addresses')->nullable();
            $table->string('subject', 500)->nullable();
            $table->string('status', 20)->default('sent')->index();
            $table->text('error_message')->nullable();
            $table->string('message_id', 500)->nullable();
            $table->timestamp('sent_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(['sent_at', 'mailer']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
