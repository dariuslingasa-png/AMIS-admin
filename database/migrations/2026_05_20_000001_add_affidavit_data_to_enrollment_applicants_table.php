<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('enrollment_applicants', 'affidavit_data')) {
            return;
        }

        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->json('affidavit_data')->nullable()->after('affidavit_url');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('enrollment_applicants', 'affidavit_data')) {
            return;
        }

        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropColumn('affidavit_data');
        });
    }
};
