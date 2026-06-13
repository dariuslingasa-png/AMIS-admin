<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->string('state_province')->nullable()->after('country');
            $table->string('city')->nullable()->after('state_province');
            $table->string('street_address')->nullable()->after('city');
            $table->string('postal_code')->nullable()->after('street_address');
            $table->string('home_state_province')->nullable()->after('home_address');
            $table->string('home_city')->nullable()->after('home_state_province');
            $table->string('home_street_address')->nullable()->after('home_city');
            $table->string('home_postal_code')->nullable()->after('home_street_address');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropColumn([
                'state_province',
                'city',
                'street_address',
                'postal_code',
                'home_state_province',
                'home_city',
                'home_street_address',
                'home_postal_code',
            ]);
        });
    }
};
