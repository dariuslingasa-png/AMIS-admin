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
        return $this->hasMany(FinanceMasterEntryStudent::class, 'finance_master_entry_id')
            ->orderByRaw("
                CASE grade_level
                    WHEN 'Kinder 1' THEN 1
                    WHEN 'Kinder 2' THEN 2
                    WHEN 'Grade 1' THEN 3
                    WHEN 'Grade 2' THEN 4
                    WHEN 'Grade 3' THEN 5
                    WHEN 'Grade 4' THEN 6
                    WHEN 'Grade 5' THEN 7
                    WHEN 'Grade 6' THEN 8
                    WHEN 'Grade 7' THEN 9
                    WHEN 'Grade 8' THEN 10
                    WHEN 'Grade 9' THEN 11
                    WHEN 'Grade 10' THEN 12
                    WHEN 'Grade 11' THEN 13
                    WHEN 'Grade 12' THEN 14
                    ELSE 99
                END
            ");
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

    public function getAllReceiptUrlsAttribute(): array
    {
        if ($this->payment && $this->payment->invoice) {
            return $this->payment->invoice->payments
                ->whereNotNull('receipt_url')
                ->filter(fn ($url) => ! blank($url))
                ->pluck('receipt_url')
                ->unique()
                ->values()
                ->toArray();
        }

        return $this->payment && ! blank($this->payment->receipt_url) ? [$this->payment->receipt_url] : [];
    }
}
