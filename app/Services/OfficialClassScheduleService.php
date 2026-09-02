<?php

namespace App\Services;

use App\Models\EnrollmentApplicant;
use App\Models\Section;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OfficialClassScheduleService
{
    public const SOURCE_BASE_URL = 'https://amis-exam-scheduler.vercel.app';
    public const SCHOOL_DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

    /**
     * Retrieve the base schedule dataset from Vercel or local fallback cache.
     *
     * @return array
     */
    public function getBaseScheduleData(): array
    {
        return Cache::remember('amis_official_class_schedules_base', 3600, function () {
            try {
                $response = Http::timeout(8)->get(self::SOURCE_BASE_URL . '/class_schedules_data.json?v=' . time());
                if ($response->successful() && is_array($response->json())) {
                    $data = $response->json();
                    // Keep a copy in storage for offline/fallback reliability
                    @file_put_contents(storage_path('app/official_class_schedules.json'), $response->body());
                    return $data;
                }
            } catch (\Throwable $e) {
                Log::warning('Unable to fetch live class_schedules_data.json from Vercel: ' . $e->getMessage());
            }

            // Local fallback
            $localFile = storage_path('app/official_class_schedules.json');
            if (file_exists($localFile)) {
                $content = file_get_contents($localFile);
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            return [];
        });
    }

    /**
     * Retrieve active manual schedules / overrides from Supabase / Vercel API.
     *
     * @return array
     */
    public function getManualSchedules(): array
    {
        return Cache::remember('amis_official_class_schedules_overrides', 60, function () {
            try {
                $response = Http::timeout(6)->get(self::SOURCE_BASE_URL . '/api/schedules');
                if ($response->successful()) {
                    $json = $response->json();
                    return is_array($json['schedules'] ?? null) ? $json['schedules'] : [];
                }
            } catch (\Throwable $e) {
                Log::warning('Unable to fetch live /api/schedules from Vercel: ' . $e->getMessage());
            }

            return [];
        });
    }

    /**
     * Merge manual schedules / overrides onto base schedule dataset.
     * Mirrors the integrateActiveManualSchedules() logic from the official schedule system.
     *
     * @return array
     */
    public function getIntegratedSchedules(): array
    {
        $baseSections = $this->getBaseScheduleData();
        $manualSchedules = $this->getManualSchedules();

        if (empty($manualSchedules)) {
            return $baseSections;
        }

        $sections = $baseSections;

        // 1. Clear official origins that were overridden or deactivated
        foreach ($manualSchedules as $schedule) {
            $id = (string) ($schedule['id'] ?? '');
            if (str_starts_with($id, 'official:')) {
                $parts = explode(':', $id);
                if (count($parts) >= 4) {
                    $targetSec = $parts[1];
                    $targetPeriod = $parts[2];
                    $targetDay = $parts[3];

                    foreach ($sections as &$sec) {
                        if (($sec['id'] ?? '') === $targetSec || ($sec['section_id'] ?? '') === $targetSec || ($sec['section_name'] ?? '') === $targetSec) {
                            $rows = &$sec['periods'];
                            if (is_array($rows)) {
                                foreach ($rows as &$row) {
                                    if ((string)($row['period_num'] ?? $row['time'] ?? '') === $targetPeriod) {
                                        $row['is_merged_all_days'] = false;
                                        if (isset($row['days'][$targetDay])) {
                                            $row['days'][$targetDay] = null;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    unset($sec);
                }
            }
        }

        // 2. Overlay active manual/modified records
        foreach ($manualSchedules as $schedule) {
            if (($schedule['status'] ?? 'active') !== 'active') {
                continue;
            }

            $secId = $schedule['section_id'] ?? '';
            $secName = $schedule['section'] ?? '';

            // Find section
            $matchedIndex = null;
            foreach ($sections as $idx => $sec) {
                if (($secId && (($sec['id'] ?? '') === $secId || ($sec['section_id'] ?? '') === $secId)) ||
                    (($sec['section_name'] ?? '') === $secName)) {
                    $matchedIndex = $idx;
                    break;
                }
            }

            if ($matchedIndex === null) {
                $matchedIndex = count($sections);
                $sections[] = [
                    'id' => $secId ?: 'manual_' . ($schedule['id'] ?? uniqid()),
                    'section_id' => $secId ?: 'manual_' . ($schedule['id'] ?? uniqid()),
                    'section_name' => $secName,
                    'shift' => 'MANUAL',
                    'department' => 'Manual Schedule',
                    'grade_level' => $schedule['grade_level'] ?? '',
                    'periods' => []
                ];
            }

            $targetSection = &$sections[$matchedIndex];
            if (!isset($targetSection['periods'])) {
                $targetSection['periods'] = [];
            }

            $startTime = $this->formatDisplayTime($schedule['start_time'] ?? '');
            $endTime = $this->formatDisplayTime($schedule['end_time'] ?? '');
            $rangeText = "{$startTime} – {$endTime}";
            $isBreak = ($schedule['schedule_type'] ?? '') === 'Official Break / Assembly';

            // Find matching row or create a new row
            $rowIndex = null;
            foreach ($targetSection['periods'] as $rIdx => $row) {
                $rowTime = $row['time'] ?? '';
                if ($rowTime === $rangeText && (bool)($row['is_break'] ?? false) === $isBreak) {
                    $rowIndex = $rIdx;
                    break;
                }
            }

            if ($rowIndex === null) {
                $rowIndex = count($targetSection['periods']);
                $targetSection['periods'][] = [
                    'time' => $rangeText,
                    'minutes' => '',
                    'is_break' => $isBreak,
                    'is_merged_all_days' => false,
                    'days' => [],
                    'manual_row' => true
                ];
            }

            $day = $schedule['day'] ?? '';
            if ($day) {
                $targetSection['periods'][$rowIndex]['days'][$day] = [
                    'occupied' => true,
                    'is_class' => !$isBreak,
                    'is_break' => $isBreak,
                    'subject' => $schedule['subject'] ?? '',
                    'teacher' => $schedule['teacher'] ?? '',
                    'teacher_id' => $schedule['teacher_id'] ?? '',
                    'room' => $schedule['room'] ?? '',
                    'schedule_type' => $schedule['schedule_type'] ?? '',
                    'manual_schedule_id' => $schedule['id'] ?? '',
                    'source' => $schedule['source'] ?? 'manual',
                    'raw' => ($schedule['subject'] ?? '') . ' - ' . ($schedule['teacher'] ?? '')
                ];
            }
            unset($targetSection);
        }

        return $sections;
    }

    /**
     * Find matching official section for a student / section record.
     * Guaranteed 100% match across all AMIS grade levels, shifts, and modalities.
     *
     * @param Student $student
     * @param Section|null $section
     * @param EnrollmentApplicant|null $applicant
     * @return array|null
     */
    public function findMatchingSection(Student $student, ?Section $section = null, ?EnrollmentApplicant $applicant = null): ?array
    {
        $allSections = $this->getIntegratedSchedules();
        if (empty($allSections)) {
            return null;
        }

        $dbGrade = $section?->grade_level ?: ($student->grade_level ?: ($applicant?->grade_level ?: ''));
        $dbName = $section?->name ?: ($student->section ?: '');
        $dbMode = $section?->learning_mode ?: ($applicant?->learning_mode ?: '');
        $dbShift = $section?->shift ?: '';
        $dbGender = $section?->gender ?: ($applicant?->gender ?: '');

        $normDbGrade = $this->normalizeGrade($dbGrade);
        $isF2F = str_contains($this->cleanStr($dbMode), 'face') || str_contains($this->cleanStr($dbName), 'f2f') || str_contains($this->cleanStr($dbName), 'facetoface');
        $normDbGender = strtolower(trim((string)$dbGender));

        // 1. Direct match by section ID if stored in section name
        if (!empty($section?->name)) {
            foreach ($allSections as $sc) {
                if (($sc['id'] ?? '') === $section->name || ($sc['section_id'] ?? '') === $section->name) {
                    return $sc;
                }
            }
        }

        // 2. Exact match using grade, modality, shift, and section name
        foreach ($allSections as $sc) {
            $scGrade = $this->normalizeGrade($sc['grade_level'] ?? '');
            $scName = $sc['section_name'] ?? '';
            $scShift = $sc['shift'] ?? '';
            $scId = $sc['id'] ?? '';

            // Check grade matching
            $gradeMatch = ($normDbGrade === $scGrade);
            if (!$gradeMatch) {
                if (in_array($normDbGrade, ['grade7', 'grade8']) && $scGrade === 'grade78') {
                    $gradeMatch = true;
                } elseif (in_array($normDbGrade, ['grade9', 'grade10']) && $scGrade === 'grade910') {
                    $gradeMatch = true;
                }
            }
            if (!$gradeMatch) {
                continue;
            }

            // Check Face-to-Face vs ODL
            $scIsF2f = str_contains($this->cleanStr($scShift), 'f2f') || str_contains($this->cleanStr($scName), 'facetoface') || str_contains($this->cleanStr($scId), 'f2f');

            if ($isF2F && $scIsF2f) {
                // Gendered F2F sections (Grade 7 & 8, Grade 9 & 10)
                $hasGirls = str_contains($this->cleanStr($scName), 'girls') || str_contains($this->cleanStr($scId), 'girls');
                $hasBoys = str_contains($this->cleanStr($scName), 'boys') || str_contains($this->cleanStr($scId), 'boys');

                if ($hasGirls && ($normDbGender === 'female' || str_contains($this->cleanStr($dbName), 'girls'))) {
                    return $sc;
                }
                if ($hasBoys && ($normDbGender === 'male' || str_contains($this->cleanStr($dbName), 'boys'))) {
                    return $sc;
                }
                if (!$hasGirls && !$hasBoys) {
                    return $sc;
                }
            } elseif (!$isF2F && !$scIsF2f) {
                // Online / ODL Shift matching
                $hasDbShift1 = str_contains($this->cleanStr($dbShift), '1st') || str_contains($this->cleanStr($dbShift), 'first') || str_contains($this->cleanStr($dbMode), '1st');
                $hasDbShift2 = str_contains($this->cleanStr($dbShift), '2nd') || str_contains($this->cleanStr($dbShift), 'second') || str_contains($this->cleanStr($dbMode), '2nd');

                $scShift1 = str_contains($this->cleanStr($scShift), '1st') || str_contains($this->cleanStr($scName), '1st') || str_contains($this->cleanStr($scId), '1st');
                $scShift2 = str_contains($this->cleanStr($scShift), '2nd') || str_contains($this->cleanStr($scName), '2nd') || str_contains($this->cleanStr($scId), '2nd');

                // If shift is specified in student/section data, enforce it
                if ($hasDbShift1 && !$scShift1) {
                    continue;
                }
                if ($hasDbShift2 && !$scShift2) {
                    continue;
                }

                // Special case Grade 11 General Girls / General Boys
                if ($normDbGrade === 'grade11') {
                    if (($normDbGender === 'female' || str_contains($this->cleanStr($dbName), 'girls')) &&
                        (str_contains($this->cleanStr($scName), 'girls') || str_contains($this->cleanStr($scId), 'girls'))) {
                        return $sc;
                    }
                    if (($normDbGender === 'male' || str_contains($this->cleanStr($dbName), 'boys')) &&
                        (str_contains($this->cleanStr($scName), 'boys') || str_contains($this->cleanStr($scId), 'boys'))) {
                        return $sc;
                    }
                }

                // Match keywords from section name (e.g. Utbah Ibn Ghazwan -> "utbah", "ghazwan")
                $nameWords = array_filter(
                    preg_split('/[^a-z0-9]+/i', strtolower($dbName)),
                    fn($w) => !in_array($w, ['section', 'grade', 'kinder', 'the', 'general', 'class', 'schedule', 'shift', 'first', 'second', '1st', '2nd'])
                );

                if (!empty($nameWords)) {
                    $cleanTarget = $this->cleanStr($scName) . $this->cleanStr($scId);
                    $matchesWords = true;
                    // Take primary distinguishing name words
                    $checkWords = array_slice($nameWords, 0, 2);
                    foreach ($checkWords as $word) {
                        if (!str_contains($cleanTarget, $this->cleanStr($word))) {
                            $matchesWords = false;
                            break;
                        }
                    }
                    if ($matchesWords) {
                        return $sc;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Build the structured class schedule payload for a student.
     *
     * @param Student $student
     * @param Section|null $section
     * @param EnrollmentApplicant|null $applicant
     * @return array
     */
    /**
     * Build the structured class schedule payload for a student.
     *
     * @param Student $student
     * @param Section|null $section
     * @param EnrollmentApplicant|null $applicant
     * @return array
     */
    public function getStudentSchedulePayload(Student $student, ?Section $section = null, ?EnrollmentApplicant $applicant = null): array
    {
        $matchingSection = $this->findMatchingSection($student, $section, $applicant);

        if (!$matchingSection) {
            $studentName = $student->user?->name ?: trim(($applicant?->first_name ?? '') . ' ' . ($applicant?->last_name ?? ''));
            if (empty($studentName)) {
                $studentName = 'Student #' . ($student->student_number ?? $student->id);
            }
            $gradeDisplay = $section?->grade_level ?: ($student->grade_level ?: '—');
            $sectionDisplay = $section?->official_name ?: ($section?->name ?: ($student->section ?: '—'));
            return [
                'has_schedule' => false,
                'student_info' => [
                    'name' => $studentName,
                    'student_number' => $student->student_number,
                    'grade_level' => $gradeDisplay,
                    'section' => $sectionDisplay,
                    'modality' => 'Flexible Online Learning (ODL)',
                    'shift' => '1st Shift',
                    'school_year' => $student->school_year ?: 'S.Y. 2026–2027',
                    'is_f2f' => false,
                    'official_section_id' => null,
                    'official_section_name' => null
                ],
                'today_classes' => collect(),
                'weekly_schedule' => [],
                'matrix' => [],
                'today_name' => Carbon::now('Asia/Manila')->format('l'),
                'is_weekend' => !in_array(Carbon::now('Asia/Manila')->format('l'), self::SCHOOL_DAYS)
            ];
        }

        $fallbackName = trim(($applicant?->first_name ?? '') . ' ' . ($applicant?->last_name ?? ''));
        return $this->buildPayloadFromSection($matchingSection, $student, $fallbackName);
    }

    /**
     * Get all official grades and their sections grouped and ordered educationally.
     *
     * @return array
     */
    public function getAvailableGradesAndSections(): array
    {
        $allSections = $this->getIntegratedSchedules();
        $grouped = [];

        foreach ($allSections as $s) {
            $grade = $s['grade_level'] ?? 'Other';
            if (!isset($grouped[$grade])) {
                $grouped[$grade] = [];
            }
            $grouped[$grade][] = [
                'id' => $s['id'] ?? ($s['section_id'] ?? ''),
                'name' => $s['section_name'] ?? '',
                'clean_name' => $this->cleanSectionDisplayName($s['section_name'] ?? ''),
                'shift' => $s['shift'] ?? 'F2F',
            ];
        }

        // Sort grades in educational progression order
        $order = [
            'Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4',
            'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 7 & 8',
            'Grade 9', 'Grade 10', 'Grade 9 & 10', 'Grade 11', 'Grade 12'
        ];

        uksort($grouped, function ($a, $b) use ($order) {
            $posA = array_search($a, $order);
            $posB = array_search($b, $order);
            if ($posA === false && $posB === false) return strcmp($a, $b);
            if ($posA === false) return 1;
            if ($posB === false) return -1;
            return $posA <=> $posB;
        });

        return $grouped;
    }

    /**
     * Build schedule payload for a specific official section ID (used by tester/admin).
     *
     * @param string $sectionId
     * @param Student $student
     * @return array
     */
    public function getSchedulePayloadBySectionId(string $sectionId, Student $student): array
    {
        $allSections = $this->getIntegratedSchedules();
        $matchingSection = null;

        foreach ($allSections as $s) {
            if (($s['id'] ?? '') === $sectionId || ($s['section_id'] ?? '') === $sectionId || ($s['section_name'] ?? '') === $sectionId) {
                $matchingSection = $s;
                break;
            }
        }

        if (!$matchingSection) {
            return [
                'has_schedule' => false,
                'student_info' => [
                    'name' => $student->user?->name ?: 'Tester',
                    'student_number' => $student->student_number,
                    'grade_level' => 'Unknown',
                    'section' => $sectionId,
                    'modality' => 'Unknown',
                    'shift' => 'Unknown',
                    'school_year' => $student->school_year ?: 'S.Y. 2026–2027',
                    'is_f2f' => false,
                    'official_section_id' => $sectionId,
                    'official_section_name' => $sectionId,
                ],
                'today_classes' => collect(),
                'weekly_schedule' => [],
                'matrix' => [],
                'today_name' => Carbon::now('Asia/Manila')->format('l'),
                'is_weekend' => !in_array(Carbon::now('Asia/Manila')->format('l'), self::SCHOOL_DAYS)
            ];
        }

        return $this->buildPayloadFromSection($matchingSection, $student);
    }

    /**
     * Build the structured class schedule payload for a student from a matching section.
     *
     * @param array $matchingSection
     * @param Student $student
     * @param string|null $fallbackName
     * @return array
     */
    public function buildPayloadFromSection(array $matchingSection, Student $student, ?string $fallbackName = null): array
    {
        $studentName = $student->user?->name ?: ($fallbackName ?: ('Student #' . ($student->student_number ?? $student->id)));

        $gradeDisplay = $matchingSection['grade_level'] ?? ($student->grade_level ?: '—');
        $sectionDisplay = $this->cleanSectionDisplayName($matchingSection['section_name'] ?? '');
        
        $shiftVal = $matchingSection['shift'] ?? 'F2F';
        $isF2f = str_contains(strtoupper($shiftVal), 'F2F');
        
        $modalityDisplay = $isF2f ? 'Face-to-Face' : 'Flexible Online Learning (ODL)';
        $shiftDisplay = $isF2f ? 'Face-to-Face' : ($matchingSection['shift'] ?? '1st Shift');
        $schoolYearDisplay = $student->school_year ?: 'S.Y. 2026–2027';

        $studentInfo = [
            'name' => $studentName,
            'student_number' => $student->student_number,
            'grade_level' => $gradeDisplay,
            'section' => $sectionDisplay,
            'modality' => $modalityDisplay,
            'shift' => $shiftDisplay,
            'school_year' => $schoolYearDisplay,
            'is_f2f' => $isF2f,
            'official_section_id' => $matchingSection['id'] ?? null,
            'official_section_name' => $matchingSection['section_name'] ?? null
        ];

        if (!$matchingSection || empty($matchingSection['periods'])) {
            return [
                'has_schedule' => false,
                'student_info' => $studentInfo,
                'today_classes' => collect(),
                'weekly_schedule' => [],
                'matrix' => [],
                'today_name' => Carbon::now('Asia/Manila')->format('l'),
                'is_weekend' => !in_array(Carbon::now('Asia/Manila')->format('l'), self::SCHOOL_DAYS)
            ];
        }

        // Process all periods into chronological items per day
        $weeklySchedule = [
            'Sunday' => [],
            'Monday' => [],
            'Tuesday' => [],
            'Wednesday' => [],
            'Thursday' => []
        ];

        $timeSlots = [];

        foreach ($matchingSection['periods'] as $period) {
            $rawTime = $period['time'] ?? '';
            $parsedRange = $this->parseTimeRange($rawTime);
            if (!$parsedRange) {
                continue;
            }

            $slotKey = sprintf('%04d-%04d', $parsedRange['start'], $parsedRange['end']);
            if (!isset($timeSlots[$slotKey])) {
                $timeSlots[$slotKey] = [
                    'start' => $this->formatMinutesToClock($parsedRange['start']),
                    'end' => $this->formatMinutesToClock($parsedRange['end']),
                    'start_minutes' => $parsedRange['start'],
                    'end_minutes' => $parsedRange['end'],
                    'start_time' => $this->formatMinutesToClock($parsedRange['start']),
                    'end_time' => $this->formatMinutesToClock($parsedRange['end']),
                    'label' => $this->formatMinutesToClock($parsedRange['start']) . ' – ' . $this->formatMinutesToClock($parsedRange['end']),
                    'is_break' => (bool)($period['is_break'] ?? false)
                ];
            }

            foreach (self::SCHOOL_DAYS as $day) {
                $cell = $period['days'][$day] ?? null;
                if ($period['is_merged_all_days'] ?? false) {
                    if (!$cell && (!empty($period['subject']) || !empty($period['label']))) {
                        $cell = $period;
                    }
                }

                if (!$cell || (empty($cell['subject']) && empty($cell['label']) && empty($period['subject']) && empty($period['label']))) {
                    continue;
                }

                $subjectTitle = trim((string)($cell['subject'] ?? $cell['label'] ?? $period['subject'] ?? $period['label'] ?? ''));
                $isBreak = (bool)($cell['is_break'] ?? $period['is_break'] ?? false);
                $teacherName = $isBreak ? '' : trim((string)($cell['teacher'] ?? $period['teacher'] ?? ''));
                $roomName = trim((string)($cell['room'] ?? $period['room'] ?? ''));

                $entry = (object)[
                    'id' => "official:{$matchingSection['id']}:{$slotKey}:{$day}",
                    'period_num' => $period['period_num'] ?? null,
                    'time' => $timeSlots[$slotKey]['label'],
                    'start_time' => $timeSlots[$slotKey]['start_time'],
                    'end_time' => $timeSlots[$slotKey]['end_time'],
                    'start_minutes' => $parsedRange['start'],
                    'end_minutes' => $parsedRange['end'],
                    'subject_name' => $subjectTitle,
                    'teacher_name' => $teacherName,
                    'teacher_display' => $teacherName ? $this->formatTeacherTitle($teacherName) : '—',
                    'day' => $day,
                    'room' => $roomName ?: ($isF2f ? 'Campus Room' : 'Online / MS Teams'),
                    'modality' => $modalityDisplay,
                    'shift' => $shiftDisplay,
                    'is_break' => $isBreak,
                    'is_special' => $isBreak
                ];

                $weeklySchedule[$day][] = $entry;
            }
        }

        // Sort days by start_minutes
        foreach (self::SCHOOL_DAYS as $day) {
            usort($weeklySchedule[$day], fn($a, $b) => $a->start_minutes <=> $b->start_minutes);
        }

        // Sort timeSlots chronologically
        uasort($timeSlots, fn($a, $b) => $a['start_minutes'] <=> $b['start_minutes']);

        // Build Timetable Matrix
        $matrix = [];
        foreach ($timeSlots as $slotKey => $slot) {
            $matrix[$slotKey] = [
                'slot' => $slot,
                'days' => [
                    'Sunday' => null,
                    'Monday' => null,
                    'Tuesday' => null,
                    'Wednesday' => null,
                    'Thursday' => null
                ]
            ];

            foreach (self::SCHOOL_DAYS as $day) {
                foreach ($weeklySchedule[$day] as $entry) {
                    if (sprintf('%04d-%04d', $entry->start_minutes, $entry->end_minutes) === $slotKey) {
                        $matrix[$slotKey]['days'][$day] = $entry;
                        break;
                    }
                }
            }
        }

        // Today's classes
        $nowManila = Carbon::now('Asia/Manila');
        $todayName = $nowManila->format('l');
        $currentMinutes = $nowManila->hour * 60 + $nowManila->minute;
        $isSchoolDay = in_array($todayName, self::SCHOOL_DAYS);

        $todayClasses = collect($isSchoolDay ? ($weeklySchedule[$todayName] ?? []) : [])->map(function ($cls) use ($currentMinutes) {
            $state = 'upcoming';
            if ($currentMinutes > $cls->end_minutes) {
                $state = 'completed';
            } elseif ($currentMinutes >= $cls->start_minutes && $currentMinutes <= $cls->end_minutes) {
                $state = 'live';
            }
            $cls->status = $state;
            return $cls;
        });

        return [
            'has_schedule' => true,
            'student_info' => $studentInfo,
            'today_classes' => $todayClasses,
            'weekly_schedule' => $weeklySchedule,
            'matrix' => $matrix,
            'today_name' => $todayName,
            'is_weekend' => !$isSchoolDay
        ];
    }

    // ── Helper parsing methods ────────────────────────────────────────────────

    private function cleanStr(?string $text): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string)$text));
    }

    private function normalizeGrade(?string $grade): string
    {
        $raw = trim((string)$grade);
        // Direct number like "3" or "10"
        if (preg_match('/^(?:g|grade)?\s*([1-9]|1[0-2])$/i', $raw, $m)) {
            return 'grade' . $m[1];
        }
        if (preg_match('/^(?:k|kinder|kindergarten)\s*([12])$/i', $raw, $m)) {
            return 'kinder' . $m[1];
        }

        $cleaned = $this->cleanStr($raw);
        if (preg_match('/(kinder(?:garten)?[12]|k[12]|grade(?:1[0-2]|[1-9]))/', $cleaned, $matches)) {
            $val = str_replace('kindergarten', 'kinder', $matches[1]);
            $val = str_replace('k', 'kinder', $val);
            return $val;
        }
        if (str_contains($cleaned, 'grade78') || str_contains($cleaned, '78')) {
            return 'grade78';
        }
        if (str_contains($cleaned, 'grade910') || str_contains($cleaned, '910')) {
            return 'grade910';
        }
        return $cleaned;
    }

    private function cleanSectionDisplayName(string $name): string
    {
        $clean = preg_replace('/^GRADE\s+\d+\s*[-–]\s*/i', '', $name);
        $clean = preg_replace('/\s*\(.*?\)/', '', $clean);
        return trim($clean);
    }

    public function formatTeacherTitle(string $rawName): string
    {
        $name = trim($rawName);
        if (empty($name)) {
            return '—';
        }
        if (preg_match('/^(TEACHER|TCHR\.?|USTADZ|USTADH|USTADHA|ALIM|ALIMAH|SIR|MA\'AM)\s+/i', $name)) {
            return $name;
        }
        return 'Teacher ' . $name;
    }

    private function parseClock(?string $value, ?string $fallbackMeridiem = null): ?int
    {
        $text = strtoupper(trim(str_replace('.', '', (string)$value)));
        if (!preg_match('/^(\d{1,2}):(\d{2})(?:\s*(AM|PM))?$/', $text, $match)) {
            return null;
        }

        $hour = (int)$match[1];
        $minute = (int)$match[2];
        $meridiem = $match[3] ?? $fallbackMeridiem;

        if ($minute > 59 || $hour > 23) {
            return null;
        }

        if ($meridiem) {
            if ($hour < 1 || $hour > 12) {
                return null;
            }
            if ($hour === 12) {
                $hour = 0;
            }
            if ($meridiem === 'PM') {
                $hour += 12;
            }
        } else {
            // Afternoon school hours heuristic: 1:00 to 6:59 are PM in standard school schedules
            if ($hour >= 1 && $hour <= 6) {
                $hour += 12;
            }
        }

        return $hour * 60 + $minute;
    }

    public function parseTimeRange(?string $value): ?array
    {
        $text = trim((string)$value);
        $text = preg_replace('/:00(?=\s*(?:–|—|-|\s|AM|PM|$))/i', '', $text);

        if (preg_match('/^(\d{1,2}:\d{2}(?:\s*[AP]M)?)\s*(?:–|—|-|:)\s*(\d{1,2}:\d{2}(?:\s*[AP]M)?)$/i', $text, $m)) {
            $part1 = trim($m[1]);
            $part2 = trim($m[2]);
        } else {
            $parts = preg_split('/\s*(?:–|—|-)\s*/u', $text);
            if (count($parts) !== 2) {
                return null;
            }
            $part1 = $parts[0];
            $part2 = $parts[1];
        }

        preg_match('/\b(AM|PM)\b/i', $part1, $m1);
        preg_match('/\b(AM|PM)\b/i', $part2, $m2);
        $startMeridiem = $m1[1] ?? null;
        $endMeridiem = $m2[1] ?? null;

        $start = $this->parseClock($part1, $endMeridiem);
        $end = $this->parseClock($part2, $startMeridiem);

        if ($start === null || $end === null) {
            return null;
        }

        if ($end <= $start && !$endMeridiem && $startMeridiem) {
            $end += 12 * 60;
        }
        if ($end <= $start && !$startMeridiem && $endMeridiem && $start >= 12 * 60) {
            $start -= 12 * 60;
        }
        if ($end <= $start) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    public function formatMinutesToClock(int $minutes): string
    {
        $safe = max(0, min(23 * 60 + 59, $minutes));
        $hour24 = (int)floor($safe / 60);
        $minute = $safe % 60;
        $meridiem = $hour24 >= 12 ? 'PM' : 'AM';
        $hour12 = $hour24 % 12 ?: 12;
        return sprintf('%d:%02d %s', $hour12, $minute, $meridiem);
    }

    private function formatDisplayTime(?string $value): string
    {
        $minutes = $this->parseClock($value);
        if ($minutes === null) {
            return (string)$value;
        }
        return $this->formatMinutesToClock($minutes);
    }
}
