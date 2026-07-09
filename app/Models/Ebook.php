<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
