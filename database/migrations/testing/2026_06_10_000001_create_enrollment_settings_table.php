<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('send_onboarding_email')->default(false);
            $table->boolean('generate_amis_id')->default(true);
            $table->boolean('generate_microsoft_account')->default(true);
            $table->boolean('generate_soa')->default(true);
            $table->boolean('require_documents_approved')->default(true);
            $table->boolean('require_payment_verified')->default(true);
            $table->boolean('require_complete_fields')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_settings');
    }
};
