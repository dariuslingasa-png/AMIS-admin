<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StudentDocument extends Model
{
    use HasFactory;

    protected $table = 'student_documents';

    protected $fillable = [
        'student_id',
        'enrollment_applicant_id',
        'document_type',
        'document_version',
        'is_current',
        'original_filename',
        'stored_filename',
        'local_path',
        'file_size',
        'mime_type',
        'checksum',
        'google_drive_file_id',
        'google_drive_folder_id',
        'generation_status',
        'archive_status',
        'snapshot_data',
        'generated_at',
        'queued_at',
        'synced_at',
        'verified_at',
        'local_deleted_at',
        'sync_attempts',
        'last_sync_attempt_at',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'document_version' => 'integer',
        'is_current' => 'boolean',
        'file_size' => 'integer',
        'snapshot_data' => 'array',
        'generated_at' => 'datetime',
        'queued_at' => 'datetime',
        'synced_at' => 'datetime',
        'verified_at' => 'datetime',
        'local_deleted_at' => 'datetime',
        'last_sync_attempt_at' => 'datetime',
        'sync_attempts' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(EnrollmentApplicant::class, 'enrollment_applicant_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isVerifiedOnDrive(): bool
    {
        return $this->archive_status === 'VERIFIED' && filled($this->google_drive_file_id);
    }

    public function isLocallyAvailable(): bool
    {
        if (empty($this->local_path)) {
            return false;
        }

        return file_exists($this->local_path) || Storage::disk('public')->exists($this->local_path);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.1f %s', $bytes / (1024 ** $factor), $units[$factor] ?? 'B');
    }

    public function getDocumentTypeLabelAttribute(): string
    {
        return match ($this->document_type) {
            'enrollment_form' => 'Official Enrollment Application Form',
            'photo_2x2' => '2x2 ID Photo',
            'birth_cert' => 'PSA / NSO Birth Certificate',
            'report_card' => 'Report Card / Form 138',
            'marriage_contract' => 'Parents Marriage Contract',
            'medical_record' => 'Medical History Records',
            'affidavit' => 'Temporary Proof (Affidavit)',
            'payment_receipt' => 'Enrollment Payment Receipt',
            default => ucwords(str_replace('_', ' ', $this->document_type)),
        };
    }
}
