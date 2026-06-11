<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('enrollment_settings')) {
            Schema::create('enrollment_settings', function (Blueprint $table) {
                $table->id();
                $table->boolean('send_onboarding_email')->default(true);
                $table->boolean('generate_amis_id')->default(true);
                $table->boolean('generate_microsoft_account')->default(true);
                $table->boolean('generate_soa')->default(true);
                $table->boolean('require_documents_approved')->default(true);
                $table->boolean('require_payment_verified')->default(true);
                $table->boolean('require_complete_fields')->default(true);
                $table->timestamps();
            });

            // Insert default settings row if table is empty
            if (DB::table('enrollment_settings')->count() === 0) {
                DB::table('enrollment_settings')->insert([
                    'send_onboarding_email' => true,
                    'generate_amis_id' => true,
                    'generate_microsoft_account' => true,
                    'generate_soa' => true,
                    'require_documents_approved' => true,
                    'require_payment_verified' => true,
                    'require_complete_fields' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            Schema::table('enrollment_settings', function (Blueprint $table) {
                if (!Schema::hasColumn('enrollment_settings', 'send_onboarding_email')) {
                    $table->boolean('send_onboarding_email')->default(true);
                }
                if (!Schema::hasColumn('enrollment_settings', 'generate_amis_id')) {
                    $table->boolean('generate_amis_id')->default(true);
                }
                if (!Schema::hasColumn('enrollment_settings', 'generate_microsoft_account')) {
                    $table->boolean('generate_microsoft_account')->default(true);
                }
                if (!Schema::hasColumn('enrollment_settings', 'generate_soa')) {
                    $table->boolean('generate_soa')->default(true);
                }
                if (!Schema::hasColumn('enrollment_settings', 'require_documents_approved')) {
                    $table->boolean('require_documents_approved')->default(true);
                }
                if (!Schema::hasColumn('enrollment_settings', 'require_payment_verified')) {
                    $table->boolean('require_payment_verified')->default(true);
                }
                if (!Schema::hasColumn('enrollment_settings', 'require_complete_fields')) {
                    $table->boolean('require_complete_fields')->default(true);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_settings');
    }
};
