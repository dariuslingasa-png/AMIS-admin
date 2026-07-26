<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_type',
        'format',
        'filter_grade',
        'filter_mode',
        'filter_gender',
        'filter_search',
        'total_count',
        'processed_count',
        'status',
        'file_path',
        'file_name',
        'file_size_bytes',
        'error_message',
    ];

    protected $casts = [
        'total_count' => 'integer',
        'processed_count' => 'integer',
        'file_size_bytes' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_count <= 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $percent = (int) round(($this->processed_count / $this->total_count) * 100);
        return min(100, max(0, $percent));
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size_bytes) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size_bytes;
        $factor = floor((strlen($bytes) - 1) / 3);

        return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor] ?? 'B');
    }
}
