<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceMasterEntry extends Model
{
    protected $fillable = [
        'payment_id',
        'family_name',
        'remittance_source',
        'reference_no',
        'method',
        'payment_date',
        'amount',
        'or_number',
        'verified_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(FinanceMasterEntryStudent::class, 'finance_master_entry_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return match (strtolower($this->method)) {
            'gcash' => 'GCash',
            'bdo' => 'BDO Bank Transfer',
            'remittance' => 'Remittance',
            'maya' => 'Maya',
            'cash' => 'Cash',
            default => ucfirst($this->method),
        };
    }
}
