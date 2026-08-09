<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyCredit extends Model
{
    protected $fillable = [
        'user_id',
        'family_application_id',
        'source_payment_id',
        'original_amount',
        'remaining_amount',
        'status',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sourcePayment()
    {
        return $this->belongsTo(Payment::class, 'source_payment_id');
    }
}
