<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Ebook extends Model
{
    protected $table = 'ebooks';

    protected $fillable = [
        'title',
        'description',
        'author',
        'grade_level',
        'file_path',
        'cover_image_path',
        'is_downloadable',
        'status',
        'created_by',
    ];

    protected $casts = [
        'is_downloadable' => 'boolean',
    ];

    public function getPdfSizeAttribute(): string
    {
        if ($this->file_path && Storage::disk('ebook_private')->exists($this->file_path)) {
            $bytes = Storage::disk('ebook_private')->size($this->file_path);
            
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($bytes, 0);
            $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
            $pow = min($pow, count($units) - 1);
            $bytes /= pow(1024, $pow);
            return round($bytes, 2) . ' ' . $units[$pow];
        }

        return 'N/A';
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(EbookAccessLog::class, 'ebook_id');
    }

    public function readers()
    {
        return $this->belongsToMany(User::class, 'ebook_access_logs', 'ebook_id', 'user_id')
            ->distinct();
    }
}
