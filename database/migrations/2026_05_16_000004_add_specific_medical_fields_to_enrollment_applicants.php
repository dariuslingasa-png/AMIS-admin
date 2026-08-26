<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->text('allergies')->nullable()->after('prescription_med');
            $table->text('current_medications')->nullable()->after('allergies');
            $table->text('health_conditions')->nullable()->after('current_medications');
            $table->text('emergency_instructions')->nullable()->after('health_conditions');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropColumn([
                'allergies',
                'current_medications',
                'health_conditions',
                'emergency_instructions',
            ]);
        });
    }
};
