<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('enrollment_settings')) {
            Schema::create('enrollment_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('send_onboarding_email')->default(true);
                $table->boolean('require_documents_approved')->default(true);
                $table->boolean('require_payment_verified')->default(true);
                $table->boolean('require_complete_fields')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            DB::table('enrollment_settings')->insert([
                'send_onboarding_email' => true,
                'require_documents_approved' => true,
                'require_payment_verified' => true,
                'require_complete_fields' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_settings');
    }
};