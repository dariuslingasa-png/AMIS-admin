<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceMasterEntryStudent extends Model
{
    protected $fillable = [
        'finance_master_entry_id',
        'student_name',
        'grade_level',
        'learning_mode',
        'student_type',
    ];

    public function financeMasterEntry(): BelongsTo
    {
        return $this->belongsTo(FinanceMasterEntry::class, 'finance_master_entry_id');
    }
}
