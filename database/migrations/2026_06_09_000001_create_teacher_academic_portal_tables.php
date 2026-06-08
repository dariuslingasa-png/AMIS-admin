<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'description')) {
                $table->text('description')->nullable()->after('code');
            }
            if (! Schema::hasColumn('subjects', 'status')) {
                $table->string('status', 20)->default('active')->after('description')->index();
            }
            if (! Schema::hasColumn('subjects', 'archived_at')) {
                $table->timestamp('archived_at')->nullable()->after('status');
            }
        });

        if (! Schema::hasTable('teacher_subject_assignments')) {
            Schema::create('teacher_subject_assignments', function (Blueprint $table) {
                $table->id();
                $table->string('teacher_key', 160)->index();
                $table->string('teacher_name');
                $table->string('teacher_email')->nullable()->index();
                $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
                $table->string('status', 20)->default('active')->index();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->useCurrent();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index(['teacher_key', 'subject_id', 'status'], 'teacher_subject_status_index');
            });
        }

        if (! Schema::hasTable('teacher_subject_assignment_histories')) {
            Schema::create('teacher_subject_assignment_histories', function (Blueprint $table) {
                $table->id();
                $table->string('teacher_key', 160)->index();
                $table->string('teacher_name');
                $table->string('teacher_email')->nullable()->index();
                $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action', 30);
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('snapshot')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('class_advisory_assignments')) {
            Schema::create('class_advisory_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->constrained()->cascadeOnDelete();
                $table->string('teacher_key', 160)->index();
                $table->string('teacher_name');
                $table->string('teacher_email')->nullable()->index();
                $table->string('school_year', 20)->default('2026-2027')->index();
                $table->string('status', 20)->default('active')->index();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('assigned_at')->useCurrent();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index(['section_id', 'school_year', 'status'], 'section_advisory_status_index');
            });
        }

        if (! Schema::hasTable('class_advisory_assignment_histories')) {
            Schema::create('class_advisory_assignment_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
                $table->string('teacher_key', 160)->index();
                $table->string('teacher_name');
                $table->string('teacher_email')->nullable();
                $table->string('action', 30);
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('snapshot')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subject_meetings')) {
            Schema::create('subject_meetings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('section_subject_id')->nullable()->constrained()->nullOnDelete();
                $table->string('teacher_key', 160)->index();
                $table->string('teacher_name');
                $table->string('teacher_email')->nullable()->index();
                $table->string('title', 160);
                $table->text('description')->nullable();
                $table->date('meeting_date');
                $table->time('meeting_time');
                $table->unsignedSmallInteger('duration_minutes')->default(60);
                $table->string('meeting_url')->nullable();
                $table->string('provider', 40)->default('microsoft_teams');
                $table->string('status', 20)->default('scheduled')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('learning_materials')) {
            Schema::create('learning_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('section_subject_id')->nullable()->constrained()->nullOnDelete();
                $table->string('teacher_key', 160)->index();
                $table->string('teacher_name');
                $table->string('teacher_email')->nullable()->index();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('type', 40)->default('file');
                $table->string('disk', 40)->nullable();
                $table->string('path')->nullable();
                $table->string('external_url')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->string('visibility', 20)->default('published')->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subject_announcements')) {
            Schema::create('subject_announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('section_subject_id')->nullable()->constrained()->nullOnDelete();
                $table->string('teacher_key', 160)->index();
                $table->string('teacher_name');
                $table->string('teacher_email')->nullable()->index();
                $table->string('title', 160);
                $table->text('body');
                $table->timestamp('published_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_announcements');
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('subject_meetings');
        Schema::dropIfExists('class_advisory_assignment_histories');
        Schema::dropIfExists('class_advisory_assignments');
        Schema::dropIfExists('teacher_subject_assignment_histories');
        Schema::dropIfExists('teacher_subject_assignments');
    }
};
