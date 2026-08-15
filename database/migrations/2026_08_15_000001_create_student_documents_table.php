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
        if (! Schema::hasTable('student_documents')) {
            Schema::create('student_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('enrollment_applicant_id')->nullable()->constrained('enrollment_applicants')->nullOnDelete();

                $table->string('document_type', 64)->default('enrollment_form'); // enrollment_form, photo_2x2, birth_cert, report_card, marriage_contract, medical_record, affidavit, payment_receipt, other
                $table->unsignedInteger('document_version')->default(1);
                $table->boolean('is_current')->default(true);

                $table->string('original_filename')->nullable();
                $table->string('stored_filename');
                $table->string('local_path')->nullable();

                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('mime_type', 128)->default('application/pdf');
                $table->string('checksum', 64)->nullable(); // SHA-256

                $table->string('google_drive_file_id', 128)->nullable();
                $table->string('google_drive_folder_id', 128)->nullable();

                $table->string('generation_status', 32)->default('generated'); // draft, generated, failed
                $table->string('archive_status', 32)->default('QUEUED'); // PENDING, QUEUED, UPLOADING, SYNCED, VERIFIED, FAILED, RETRY_PENDING, ARCHIVED

                $table->json('snapshot_data')->nullable(); // Frozen approved student and parent information

                $table->timestamp('generated_at')->nullable();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamp('local_deleted_at')->nullable();

                $table->unsignedInteger('sync_attempts')->default(0);
                $table->timestamp('last_sync_attempt_at')->nullable();
                $table->text('error_message')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['student_id', 'document_type']);
                $table->index(['student_id', 'is_current']);
                $table->index('archive_status');
                $table->index('google_drive_file_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
