<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copy nationality data into ethnicity (if ethnicity is empty)
        DB::statement("UPDATE enrollment_applicants SET ethnicity = nationality WHERE ethnicity IS NULL OR ethnicity = ''");

        // Drop the nationality column
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropColumn('nationality');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->string('nationality')->nullable()->after('religion');
        });

        DB::statement("UPDATE enrollment_applicants SET nationality = ethnicity");
    }
};
