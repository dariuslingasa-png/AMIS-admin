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
        $schoolYear = $student?->school_year ?? '2026-2027';
        $gradeName = $student?->grade_level ?: 'All Students';

        $announcementsData = [
            'welcome-portal' => [
                'id' => 'welcome-portal',
                'title' => 'Welcome to our new AMIS Student Portal',
                'type' => 'Portal Update',
                'category' => 'portal',
                'date' => now()->format('M d, Y'),
                'icon' => 'sparkles',
                'tone' => 'emerald',
                'is_pinned' => true,
                'summary' => 'Welcome to the official AMIS Student & Parent Portal for School Year ' . $schoolYear . '! Access your subjects, class timetable, statement of account, and profile anytime.',
                'details' => 'The AMIS Student Portal is designed to provide quick and easy access to your daily academic activities. You can monitor your enrolled subjects, check weekly class schedules with assigned teachers, track tuition fee balances, and view official school updates. Please keep your profile information up-to-date.',
                'audience' => $gradeName,
            ],
            'class-schedule-sy2627' => [
                'id' => 'class-schedule-sy2627',
                'title' => 'Official Class Schedule & Timetable for SY ' . $schoolYear,
                'type' => 'Academic',
                'category' => 'academic',
                'date' => now()->subDays(2)->format('M d, Y'),
                'icon' => 'calendar-range',
                'tone' => 'sky',
                'is_pinned' => false,
                'summary' => 'The official class timetable for School Year ' . $schoolYear . ' is now finalized and published on your Class Schedule page.',
                'details' => 'Please review your section schedule for complete timeslot details including subject durations, teacher assignments, prayer times, and transition breaks. Students are expected to be ready 10 minutes before each class starts.',
                'audience' => $gradeName,
            ],
            'billing-soa-guidelines' => [
                'id' => 'billing-soa-guidelines',
                'title' => 'Statement of Account (SOA) & Payment Proof Guidelines',
                'type' => 'Finance & Billing',
                'category' => 'finance',
                'date' => now()->subDays(5)->format('M d, Y'),
                'icon' => 'credit-card',
                'tone' => 'amber',
                'is_pinned' => false,
                'summary' => 'Review your current tuition assessment, payment deadlines, and receipt verification status under My Billing (SOA).',
                'details' => 'When submitting payment receipts via bank transfer or deposit, please make sure the bank transaction reference number, payment date, and exact amount are clearly readable. The Finance Office verifies submitted receipts within 24 to 48 business hours.',
                'audience' => 'All Students & Parents',
            ],
            'general-assembly-advisory' => [
                'id' => 'general-assembly-advisory',
                'title' => 'General Assembly & Morning Routine Guidelines',
                'type' => 'Campus Life',
                'category' => 'campus',
                'date' => now()->subDays(8)->format('M d, Y'),
                'icon' => 'bell',
                'tone' => 'indigo',
                'is_pinned' => false,
                'summary' => 'Daily General Assembly begins promptly every morning. Students are required to observe proper uniform and etiquette.',
                'details' => 'As part of our Islamic values and academic discipline, all students must attend the morning General Assembly on time. Please ensure proper school uniform and Islamic attire are worn daily. For questions, feel free to coordinate with your respective class adviser.',
                'audience' => 'All Levels',
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
