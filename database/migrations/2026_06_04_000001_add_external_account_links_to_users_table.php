<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('email');
            }

            if (! Schema::hasColumn('users', 'google_email')) {
                $table->string('google_email')->nullable()->after('google_id');
            }

            if (! Schema::hasColumn('users', 'google_linked_at')) {
                $table->timestamp('google_linked_at')->nullable()->after('google_email');
            }

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['google_linked_at', 'google_email', 'google_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
