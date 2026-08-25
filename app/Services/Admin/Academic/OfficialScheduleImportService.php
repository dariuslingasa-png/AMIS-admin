<?php

namespace App\Services\Admin\Academic;

use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OfficialScheduleImportService
{
    public const SCHOOL_YEAR = '2026-2027';

    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

    public function __construct(private readonly TeacherMatcherService $teacherMatcher) {}

    /**
     * Import only sections that do not yet have an encoded timetable.
     * Existing and manually locked production schedules are never overwritten.
     *
     * @return array{source_sections:int, imported_sections:int, skipped_populated_sections:int, created_sections:int, schedules:int, subjects:int, invalid_periods:int}
     */
    public function importMissing(?string $path = null, string $schoolYear = self::SCHOOL_YEAR): array
    {
        $path ??= database_path('data/academic/class_schedules_2026_2027.json');
        if (! is_file($path)) {
            throw new RuntimeException("Official schedule source not found: {$path}");
        }

        $sourceSections = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($sourceSections)) {
            throw new RuntimeException('Official schedule source must contain an array of sections.');
        }

        $report = [
            'source_sections' => count($sourceSections),
            'imported_sections' => 0,
            'skipped_populated_sections' => 0,
            'created_sections' => 0,
            'schedules' => 0,
            'subjects' => 0,
            'invalid_periods' => 0,
        ];

        DB::transaction(function () use ($sourceSections, $schoolYear, &$report): void {
            $sections = Section::query()->get();
            $subjectIds = Subject::query()
                ->where('school_year', $schoolYear)
                ->get()
                ->keyBy(fn (Subject $subject) => $this->subjectKey($subject->grade_level, $subject->name))
                ->map(fn (Subject $subject) => $subject->id)
                ->all();

            foreach ($sourceSections as $sourceSection) {
                [$section, $wasCreated] = $this->resolveSection($sourceSection, $sections);
                if ($wasCreated) {
                    $sections->push($section);
                    $report['created_sections']++;
                }

                $alreadyPopulated = ClassSchedule::query()
                    ->where('section_id', $section->id)
                    ->where('school_year', $schoolYear)
                    ->exists();
                if ($alreadyPopulated) {
                    $report['skipped_populated_sections']++;

                    continue;
                }

                $compiled = self::compileSectionRows($sourceSection);
                $report['invalid_periods'] += $compiled['invalid_periods'];
                $inserts = [];

                foreach ($compiled['rows'] as $row) {
                    $subjectId = null;
                    if (! $row['is_special']) {
                        $key = $this->subjectKey((string) $sourceSection['grade_level'], $row['subject_name']);
                        if (! isset($subjectIds[$key])) {
                            $subject = Subject::query()->firstOrCreate(
                                [
                                    'name' => $row['subject_name'],
                                    'grade_level' => (string) $sourceSection['grade_level'],
                                    'school_year' => $schoolYear,
                                ],
                                [
                                    'code' => null,
                                    'description' => 'Imported from the official Vercel class schedule.',
                                    'status' => 'active',
                                ],
                            );
                            $subjectIds[$key] = $subject->id;
                            $report['subjects']++;
                        }
                        $subjectId = $subjectIds[$key];
                    }

                    $teacher = $this->teacherMatcher->match((string) ($row['teacher_display'] ?? ''));
                    $now = now();
                    $inserts[] = [
                        'section_id' => $section->id,
                        'subject_id' => $subjectId,
                        'room_id' => null,
                        'subject_name' => $row['subject_name'],
                        'spans_all_days' => $row['spans_all_days'],
                        'is_special' => $row['is_special'],
                        'is_locked' => false,
                        'color_class' => $this->colorClass($row['subject_name'], $row['is_special']),
                        'teacher_key' => $teacher['key'],
                        'teacher_display' => $teacher['display'],
                        'teacher_status' => $teacher['status'],
                        'day' => $row['day'],
                        'start_time' => $row['start_time'],
                        'end_time' => $row['end_time'],
                        'mode' => $this->isFaceToFace($sourceSection) ? 'f2f' : 'online',
                        'school_year' => $schoolYear,
                        'created_by' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($inserts, 250) as $chunk) {
                    ClassSchedule::query()->insert($chunk);
                }

                $report['schedules'] += count($inserts);
                $report['imported_sections']++;
            }
        });

        return $report;
    }

    /**
     * Transform one official Vercel section into Laravel schedule rows.
     * This intentionally follows the same time parser and merged-cell behavior
     * as the Vercel schedule core.
     *
     * @return array{rows:array<int,array<string,mixed>>,invalid_periods:int}
     */
    public static function compileSectionRows(array $section): array
    {
        $rows = [];
        $invalidPeriods = 0;

        foreach (($section['periods'] ?? $section['rows'] ?? []) as $period) {
            $range = self::parseTimeRange((string) ($period['time'] ?? ''));
            if ($range === null) {
                $invalidPeriods++;

                continue;
            }

            $dayCells = collect(self::DAYS)
                ->mapWithKeys(fn (string $day) => [$day => $period['days'][$day] ?? null])
                ->filter(fn ($cell) => is_array($cell) && filled($cell['subject'] ?? $cell['label'] ?? $cell['raw'] ?? null));

            if (! empty($period['is_merged_all_days'])) {
                $cell = $dayCells->first();
                if (! is_array($cell) && filled($period['subject'] ?? $period['label'] ?? null)) {
                    $cell = $period;
                }
                if (is_array($cell)) {
                    $rows[] = self::scheduleRow($period, $cell, 'Sunday', $range, true);
                }

                continue;
            }

            foreach ($dayCells as $day => $cell) {
                $rows[] = self::scheduleRow($period, $cell, $day, $range, false);
            }
        }

        return ['rows' => $rows, 'invalid_periods' => $invalidPeriods];
    }

    /** @return array{start:string,end:string}|null */
    public static function parseTimeRange(string $value): ?array
    {
        $parts = preg_split('/\s*(?:–|—|-)\s*/u', trim($value));
        if (! is_array($parts) || count($parts) !== 2) {
            return null;
        }

        preg_match('/\b(AM|PM)\b/i', strtoupper($parts[0]), $startMatch);
        preg_match('/\b(AM|PM)\b/i', strtoupper($parts[1]), $endMatch);
        $startMeridiem = $startMatch[1] ?? null;
        $endMeridiem = $endMatch[1] ?? null;
        $start = self::parseClock($parts[0], $endMeridiem);
        $end = self::parseClock($parts[1], $startMeridiem);

        if ($start === null || $end === null) {
            return null;
        }
        if ($end <= $start && $endMeridiem === null && $startMeridiem !== null) {
            $end += 720;
        }
        if ($end <= $start && $startMeridiem === null && $endMeridiem !== null && $start >= 720) {
            $start -= 720;
        }
        if ($end <= $start) {
            return null;
        }

        return ['start' => self::formatMinutes($start), 'end' => self::formatMinutes($end)];
    }

    private static function scheduleRow(array $period, array $cell, string $day, array $range, bool $merged): array
    {
        $isSpecial = (bool) ($cell['is_break'] ?? $period['is_break'] ?? false);
        $subject = trim((string) ($cell['subject'] ?? $cell['label'] ?? $period['subject'] ?? $period['label'] ?? ''));

        return [
            'subject_name' => $subject,
            'teacher_display' => $isSpecial ? null : ($cell['teacher'] ?? $period['teacher'] ?? null),
            'day' => $day,
            'start_time' => $range['start'],
            'end_time' => $range['end'],
            'spans_all_days' => $merged,
            'is_special' => $isSpecial,
        ];
    }

    private static function parseClock(string $value, ?string $fallbackMeridiem): ?int
    {
        $text = strtoupper(str_replace('.', '', trim($value)));
        if (preg_match('/^(\d{1,2}):(\d{2})(?:\s*(AM|PM))?$/', $text, $match) !== 1) {
            return null;
        }

        $hour = (int) $match[1];
        $minute = (int) $match[2];
        $meridiem = $match[3] ?? $fallbackMeridiem;
        if ($minute > 59 || $hour > 23) {
            return null;
        }
        if ($meridiem !== null && $meridiem !== '') {
            if ($hour < 1 || $hour > 12) {
                return null;
            }
            $hour %= 12;
            if ($meridiem === 'PM') {
                $hour += 12;
            }
        }

        return ($hour * 60) + $minute;
    }

    private static function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /** @return array{0:Section,1:bool} */
    private function resolveSection(array $source, Collection $sections): array
    {
        $grade = trim((string) ($source['grade_level'] ?? ''));
        $sourceName = (string) ($source['section_name'] ?? '');
        $faceToFace = $this->isFaceToFace($source);
        $shift = $this->sourceShift($source);
        $gender = $this->sourceGender($sourceName);

        $candidates = $sections
            ->filter(fn (Section $section) => strcasecmp(trim((string) $section->grade_level), $grade) === 0)
            ->filter(fn (Section $section) => $this->sectionIsFaceToFace($section) === $faceToFace)
            ->when($shift !== null, fn (Collection $rows) => $rows->filter(fn (Section $section) => strcasecmp(trim((string) $section->shift), $shift) === 0));

        if ($gender !== 'na' && $candidates->contains(fn (Section $section) => $section->gender === $gender)) {
            $candidates = $candidates->where('gender', $gender);
        }

        $sourceKey = $this->sectionNameKey($sourceName);
        $ranked = $candidates->map(fn (Section $section) => [
            'section' => $section,
            'score' => $this->sectionNameScore($sourceKey, $this->sectionNameKey((string) $section->name)),
        ])->sortByDesc('score')->values();

        // Common tokens such as "ibn" are not enough to identify a section.
        // Accept only an exact normalized name or a strong substring match;
        // otherwise create the missing official section instead of risking a
        // cross-section import.
        if ($ranked->isNotEmpty() && $ranked->first()['score'] >= 70) {
            return [$ranked->first()['section'], false];
        }

        $section = Section::query()->create([
            'name' => $this->sectionDisplayName($sourceName, $faceToFace, $gender),
            'grade_level' => $grade,
            'learning_mode' => $faceToFace ? 'Face-to-Face' : 'Flexible Online Learning',
            'shift' => $shift,
            'gender' => $gender,
            'track_strand' => null,
            'academic_status' => 'active',
        ]);

        return [$section, true];
    }

    private function isFaceToFace(array $source): bool
    {
        return strtoupper(trim((string) ($source['shift'] ?? ''))) === 'F2F';
    }

    private function sectionIsFaceToFace(Section $section): bool
    {
        $mode = mb_strtolower((string) $section->learning_mode);

        return str_contains($mode, 'face') || str_contains($mode, 'f2f');
    }

    private function sourceShift(array $source): ?string
    {
        $shift = strtoupper((string) ($source['shift'] ?? ''));
        if (str_contains($shift, '1ST')) {
            return '1st Shift';
        }
        if (str_contains($shift, '2ND')) {
            return '2nd Shift';
        }

        return null;
    }

    private function sourceGender(string $name): string
    {
        $upper = strtoupper($name);
        if (str_contains($upper, 'GIRL')) {
            return 'female';
        }
        if (str_contains($upper, 'BOY')) {
            return 'male';
        }
        if (preg_match('/\bMIX(?:ED)?\b/', $upper) === 1) {
            return 'merge';
        }

        return 'na';
    }

    private function sectionNameKey(string $value): string
    {
        $value = mb_strtolower(Str::ascii($value));
        $value = preg_replace('/\b(?:grade|g|kinder|k)\s*\d+(?:\s*&\s*\d+)?\b/', ' ', $value) ?? $value;
        $value = preg_replace('/\b(?:class schedule|face to face|f2f|odl|1st|2nd|first|second|shift|girls?|boys?|mixed?|mix)\b/', ' ', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function sectionNameScore(string $source, string $candidate): int
    {
        if ($source === '' && $candidate === '') {
            return 10;
        }
        if ($source !== '' && $source === $candidate) {
            return 100;
        }
        if ($source !== '' && $candidate !== '' && (str_contains($source, $candidate) || str_contains($candidate, $source))) {
            return 70;
        }

        $sourceTokens = array_filter(explode(' ', $source));
        $candidateTokens = array_filter(explode(' ', $candidate));

        return count(array_intersect($sourceTokens, $candidateTokens)) * 10;
    }

    private function sectionDisplayName(string $source, bool $faceToFace, string $gender): string
    {
        $name = preg_replace('/^\s*(?:GRADE|G|KINDER|K)\s*\d+(?:\s*&\s*\d+)?\s*[-–]?\s*/i', '', $source) ?? $source;
        $name = preg_replace('/\(?\s*(?:F2F|FACE TO FACE|ODL\s*-?\s*)?\s*(?:1ST|2ND)?\s*SHIFT\s*\)?/i', ' ', $name) ?? $name;
        $name = preg_replace('/\(?\s*(?:F2F|FACE TO FACE)\s*\)?/i', ' ', $name) ?? $name;
        $name = preg_replace('/\b(?:GIRLS?|BOYS?|MIXED?|MIX)\b/i', ' ', $name) ?? $name;
        $name = trim(preg_replace('/\s+/', ' ', trim($name, " -–()\t\n\r\0\x0B")) ?? $name);

        if ($name === '' || strcasecmp($name, 'CLASS SCHEDULE') === 0) {
            $suffix = match ($gender) {
                'female' => ' - GIRLS',
                'male' => ' - BOYS',
                default => '',
            };

            return ($faceToFace ? 'FACE TO FACE' : 'GENERAL').$suffix;
        }

        return $name;
    }

    private function subjectKey(string $grade, string $subject): string
    {
        return mb_strtolower(trim($grade)).'|'.mb_strtolower(trim($subject));
    }

    private function colorClass(string $subject, bool $isSpecial): string
    {
        if ($isSpecial) {
            return str_contains(mb_strtolower($subject), 'recess') ? 'recess' : 'event';
        }

        $value = mb_strtolower($subject);
        foreach (['quran' => ["qur'an", 'quran'], 'arabic' => ['arabic'], 'hadith' => ['hadith']] as $color => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($value, $needle)) {
                    return $color;
                }
            }
        }

        return 'academic';
    }
}
