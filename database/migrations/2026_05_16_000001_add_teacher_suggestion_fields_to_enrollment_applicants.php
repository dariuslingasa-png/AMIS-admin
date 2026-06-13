<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->string('ethnicity')->nullable()->after('religion');
            $table->string('mobile_country_code', 8)->nullable()->after('email');
            $table->string('parent_country_code', 8)->nullable()->after('home_address');
            $table->text('medical_history')->nullable()->after('prescription_med');
            $table->string('referral_source')->nullable()->after('parent_email');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropColumn([
                'ethnicity',
                'mobile_country_code',
                'parent_country_code',
                'medical_history',
                'referral_source',
            ]);
        });
    }
};
