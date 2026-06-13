<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            // JSON: { "photo_2x2": "approved", "birth_cert": "rejected", ... }
            $table->json('document_statuses')->nullable()->after('medical_record_url');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropColumn('document_statuses');
        });
    }
};
