<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_rooms')) {
            Schema::create('academic_rooms', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('room_type', 60)->nullable();
                $table->unsignedInteger('capacity')->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamps();

                $table->index(['status', 'room_type']);
            });
        }

        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) {
                if (! Schema::hasColumn('subjects', 'weekly_hours')) {
                    $table->decimal('weekly_hours', 5, 2)->nullable()->after('description');
                }
                if (! Schema::hasColumn('subjects', 'semester')) {
                    $table->string('semester', 30)->nullable()->after('weekly_hours');
                }
            });
        }

        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                if (! Schema::hasColumn('sections', 'track_strand')) {
                    $table->string('track_strand', 80)->nullable()->after('gender');
                }
                if (! Schema::hasColumn('sections', 'academic_status')) {
                    $table->string('academic_status', 20)->default('active')->after('track_strand');
                }
            });
        }

        if (Schema::hasTable('class_schedules')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                if (! Schema::hasColumn('class_schedules', 'subject_id')) {
                    $table->foreignId('subject_id')->nullable()->after('section_id')->constrained('subjects')->nullOnDelete();
                }
                if (! Schema::hasColumn('class_schedules', 'room_id')) {
                    $table->foreignId('room_id')->nullable()->after('subject_id')->constrained('academic_rooms')->nullOnDelete();
                }
                if (! Schema::hasColumn('class_schedules', 'is_locked')) {
                    $table->boolean('is_locked')->default(false)->after('is_special');
                }
            });

        }
    }

    public function down(): void
    {
        if (Schema::hasTable('class_schedules')) {
            Schema::table('class_schedules', function (Blueprint $table) {
                if (Schema::hasColumn('class_schedules', 'room_id')) {
                    $table->dropConstrainedForeignId('room_id');
                }
                if (Schema::hasColumn('class_schedules', 'subject_id')) {
                    $table->dropConstrainedForeignId('subject_id');
                }
                if (Schema::hasColumn('class_schedules', 'is_locked')) {
                    $table->dropColumn('is_locked');
                }
            });
        }

        if (Schema::hasTable('sections')) {
            Schema::table('sections', function (Blueprint $table) {
                $columns = collect(['track_strand', 'academic_status'])
                    ->filter(fn (string $column) => Schema::hasColumn('sections', $column))
                    ->all();
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('subjects')) {
            Schema::table('subjects', function (Blueprint $table) {
                $columns = collect(['weekly_hours', 'semester'])
                    ->filter(fn (string $column) => Schema::hasColumn('subjects', $column))
                    ->all();
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        Schema::dropIfExists('academic_rooms');
    }
};
