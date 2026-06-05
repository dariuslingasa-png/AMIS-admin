<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'firebase_uid')) {
                $table->string('firebase_uid')->nullable()->unique()->after('google_linked_at');
            }

            if (! Schema::hasColumn('users', 'firebase_email')) {
                $table->string('firebase_email')->nullable()->after('firebase_uid');
            }

            if (! Schema::hasColumn('users', 'firebase_linked_at')) {
                $table->timestamp('firebase_linked_at')->nullable()->after('firebase_email');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['firebase_linked_at', 'firebase_email', 'firebase_uid'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
