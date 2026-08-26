<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            if (! Schema::hasColumn('enrollment_applicants', 'registry_email_sent_at')) {
                $table->timestamp('registry_email_sent_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_applicants', function (Blueprint $table) {
            $table->dropColumn('registry_email_sent_at');
        });
    }
};
