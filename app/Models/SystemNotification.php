<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemNotification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'action_url',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Dispatch an in-app system notification to all Admin / Super Admin users.
     */
    public static function notifyAdmin(string $title, string $message, string $type = 'info', ?string $actionUrl = null): void
    {
        $admins = User::whereIn('role', ['super_admin', 'admin'])
            ->where('account_status', 'verified')
            ->pluck('id');

        if ($admins->isNotEmpty()) {
            foreach ($admins as $adminId) {
                self::create([
                    'user_id' => $adminId,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'action_url' => $actionUrl,
                    'is_read' => false,
                ]);
            }
        } else {
            // Fallback for system wide notification
            self::create([
                'user_id' => null,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'action_url' => $actionUrl,
                'is_read' => false,
            ]);
        }
    }

    /**
     * Dispatch an in-app notification to a specific user.
     */
    public static function notifyUser(int $userId, string $title, string $message, string $type = 'info', ?string $actionUrl = null): void
    {
        self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $actionUrl,
            'is_read' => false,
        ]);
    }
}
