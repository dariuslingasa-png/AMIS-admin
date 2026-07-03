<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('announcement_interactions')) {
            Schema::create('announcement_interactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('announcement_key')->index();
                $table->integer('views_count')->default(0);
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'announcement_key'], 'user_announcement_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_interactions');
    }
};
