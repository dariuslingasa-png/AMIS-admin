<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ebooks')) {
            Schema::create('ebooks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('grade_level', 100)->nullable();
                $table->string('file_path');
                $table->boolean('is_downloadable')->default(false);
                $table->string('status', 50)->default('published');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->timestamps();

                $table->index(['status', 'grade_level']);
            });
        }

        if (! Schema::hasTable('ebook_access_logs')) {
            Schema::create('ebook_access_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ebook_id')->constrained('ebooks')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('action');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['ebook_id', 'user_id', 'action']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ebook_access_logs');
        Schema::dropIfExists('ebooks');
    }
};
