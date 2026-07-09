<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StudentAnnouncementService
{
    public function recordViews($userId, $announcementKeys, $markAsRead = false): void
    {
        foreach ($announcementKeys as $key) {
            $record = DB::table('announcement_interactions')
                ->where('user_id', $userId)
                ->where('announcement_key', $key)
                ->first();

            if ($record) {
                $updateData = [
                    'views_count' => $record->views_count + 1,
                    'updated_at' => now(),
                ];
                if ($markAsRead && is_null($record->read_at)) {
                    $updateData['read_at'] = now();
                }
                DB::table('announcement_interactions')
                    ->where('id', $record->id)
                    ->update($updateData);
            } else {
                DB::table('announcement_interactions')->insert([
                    'user_id' => $userId,
                    'announcement_key' => $key,
                    'views_count' => 1,
                    'read_at' => $markAsRead ? now() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function getAnnouncements($student, $subjects): array
    {
        $userId = Auth::id();
        $announcementsData = [
            'welcome-portal' => [
                'id' => 'welcome-portal',
                'title' => 'Welcome to our new AMIS student portal',
                'type' => 'Portal Update',
                'date' => now()->format('M d, Y'),
                'icon' => 'sparkles',
                'tone' => 'emerald',
                'summary' => 'Welcome to our new AMIS student portal! Monitor your subjects, class schedule, billing status, and student profile all in one place.',
                'details' => 'Welcome to our new AMIS student portal! Monitor your subjects, class schedule, billing status, and student profile all in one place. Please review your student profile and class information regularly so you do not miss school updates.',
                'audience' => $student?->grade_level ?: 'All Students',
            ],
        ];

        // Fetch aggregate views count per key
        $totalViews = DB::table('announcement_interactions')
            ->select('announcement_key', DB::raw('SUM(views_count) as total_views'))
            ->groupBy('announcement_key')
            ->pluck('total_views', 'announcement_key')
            ->toArray();

        // Fetch read status for current user
        $userInteractions = DB::table('announcement_interactions')
            ->where('user_id', $userId)
            ->pluck('read_at', 'announcement_key')
            ->toArray();

        $result = [];
        foreach ($announcementsData as $key => $ann) {
            $ann['total_views'] = intval($totalViews[$key] ?? 0);
            $ann['is_read'] = array_key_exists($key, $userInteractions) && !is_null($userInteractions[$key]);
            $result[] = $ann;
        }

        return $result;
    }
}
