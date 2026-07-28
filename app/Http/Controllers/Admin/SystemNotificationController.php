<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // If AJAX / JSON API request (used by topbar dropdown)
        if ($request->expectsJson() || $request->ajax() || $request->has('json')) {
            $notifications = SystemNotification::query()
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereNull('user_id');
                })
                ->latest()
                ->take(20)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title,
                        'message' => $item->message,
                        'type' => $item->type,
                        'action_url' => $item->action_url,
                        'is_read' => (bool) $item->is_read,
                        'time_ago' => $item->created_at ? $item->created_at->diffForHumans() : 'Just now',
                        'created_at_formatted' => $item->created_at ? $item->created_at->format('M d, Y h:i A') : '',
                    ];
                });

            $unreadCount = SystemNotification::query()
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereNull('user_id');
                })
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'unread_count' => $unreadCount,
                'notifications' => $notifications,
            ]);
        }

        // Full Web View Page (/admin/notifications)
        $query = SystemNotification::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            });

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if ($request->input('filter') === 'unread') {
            $query->where('is_read', false);
        } elseif ($request->input('filter') === 'backup') {
            $query->where(function ($q) {
                $q->where('type', 'backup')
                    ->orWhere('title', 'like', '%backup%')
                    ->orWhere('message', 'like', '%backup%');
            });
        } elseif ($request->input('filter') === 'system') {
            $query->where(function ($q) {
                $q->where('type', 'system')
                    ->orWhere('type', 'security')
                    ->orWhere('type', 'info');
            });
        }

        $notifications = $query->latest()->paginate(25)->withQueryString();

        $unreadCount = SystemNotification::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->where('is_read', false)
            ->count();

        $totalCount = SystemNotification::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->count();

        return view('admin.notifications.index', compact('notifications', 'unreadCount', 'totalCount'));
    }

    public function markAsRead(Request $request, $id): JsonResponse
    {
        $user = $request->user();

        $notification = SystemNotification::query()
            ->where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->first();

        if ($notification) {
            $notification->is_read = true;
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        SystemNotification::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    public function clearAll(Request $request): JsonResponse
    {
        $user = $request->user();

        SystemNotification::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->delete();

        return response()->json(['success' => true]);
    }
}
