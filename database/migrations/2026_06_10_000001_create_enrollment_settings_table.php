<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('enrollment_settings', 'generate_amis_id')) {
                $table->boolean('generate_amis_id')->default(true);
            }
            if (!Schema::hasColumn('enrollment_settings', 'generate_soa')) {
                $table->boolean('generate_soa')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_settings', function (Blueprint $table) {
            $table->dropColumn(['generate_amis_id', 'generate_soa']);
        });
    }
};
