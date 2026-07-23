<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdminAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'email',
        'ip_address',
        'user_agent',
        'successful',
        'message',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $event, bool $successful = true, ?string $message = null, array $metadata = []): void
    {
        $request = request();
        $user = auth()->user();

        self::create([
            'user_id' => $user?->id,
            'event' => $event,
            'email' => $user?->email,
            'ip_address' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 1000, ''),
            'successful' => $successful,
            'message' => $message,
            'metadata' => $metadata ?: null,
        ]);
    }
}
