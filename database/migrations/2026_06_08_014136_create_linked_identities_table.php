<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('linked_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('microsoft_email')->unique();
            $table->string('tenant_id');
            $table->string('identity_type'); // 'GENERATED' or 'LEGACY'
            $table->timestamps();

            $table->index('microsoft_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('linked_identities');
    }
};
