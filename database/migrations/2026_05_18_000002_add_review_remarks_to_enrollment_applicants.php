<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('enrollment_applicants') && ! Schema::hasColumn('enrollment_applicants', 'review_remarks')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $table->text('review_remarks')->nullable()->after('document_statuses');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('enrollment_applicants') && Schema::hasColumn('enrollment_applicants', 'review_remarks')) {
            Schema::table('enrollment_applicants', function (Blueprint $table) {
                $table->dropColumn('review_remarks');
            });
        }
    }
};
