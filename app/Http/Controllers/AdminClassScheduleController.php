<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academic\ClassScheduleRequest;
use App\Models\ClassSchedule;
use App\Services\Admin\Academic\ClassScheduleService;
use App\Services\Admin\Academic\SectionSubjectSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AdminClassScheduleController extends Controller
{
    public function __construct(
        private readonly ClassScheduleService        $schedules,
        private readonly SectionSubjectSyncService   $subjectSync,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('manage-academic');

        $mode = $request->query('mode', 'f2f');

        $sections = \App\Models\Section::withCount('students')
            ->orderByRaw("FIELD(grade_level,'Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12')")
            ->get();

        $f2fSections    = $sections->filter(fn($s) => str_contains($s->learning_mode ?? '', 'Face') || str_contains($s->learning_mode ?? '', 'f2f'));
        $onlineSections = $sections->filter(fn($s) => str_contains($s->learning_mode ?? '', 'Online') || str_contains($s->learning_mode ?? '', 'Flexible'));

        $advisories      = $this->schedules->advisories();
        $advisoryByGrade = $advisories->keyBy('grade_level');
        $teachers        = $this->schedules->allTeachersForPicker();

        $sectionIds = $sections->pluck('id');
        $schedulesBySection = ClassSchedule::whereIn('section_id', $sectionIds)
            ->get()
            ->map(fn (ClassSchedule $s) => $this->schedules->present($s))
            ->sortBy([['day_index', 'asc'], ['start_minutes', 'asc']])
            ->groupBy('section_id');

        $days            = $this->schedules->days();
        $timeOptions     = $this->schedules->timeOptions();

        $unmatchedCount = ClassSchedule::whereIn('section_id', $sectionIds)
            ->where('teacher_status', 'unmatched')
            ->count();

        $firstSectionId = (int) ($sections->first()?->id ?? 0);
        $activeSectionId = (int) old('section_id', $firstSectionId);
        $failedForm = old('_schedule_form');
        $failedScheduleId = (int) old('schedule_id', 0);
        $activeSection = $sections->firstWhere('id', $activeSectionId) ?? $sections->first();
        $activeGradeLevel = $activeSection?->grade_level ?? '';

        // All sections for the Active Sections Catalog
        $allSections = \App\Models\Section::withCount(['students as enrolled_count' => fn($q) => $q->where('ms_status', 'enrolled')])
            ->withCount('subjects')
            ->orderBy('grade_level')
            ->orderBy('learning_mode')
            ->orderBy('shift')
            ->orderBy('gender')
            ->get();

        $gradeOrder = [
            'Kinder 1' => 1, 'Kinder 2' => 2,
            'Grade 1' => 3, 'Grade 2' => 4, 'Grade 3' => 5, 'Grade 4' => 6,
            'Grade 5' => 7, 'Grade 6' => 8, 'Grade 7' => 9, 'Grade 8' => 10,
            'Grade 9' => 11, 'Grade 10' => 12, 'Grade 11' => 13, 'Grade 12' => 14
        ];
        $groupedSections = $allSections->groupBy('grade_level')->sortBy(fn($v, $k) => $gradeOrder[$k] ?? 99);

        $f2fCount      = $allSections->where('learning_mode', 'Face-to-Face')->count();
        $flexCount     = $allSections->filter(fn($s) => str_contains($s->learning_mode ?? '', 'Flexible'))->count();
        $totalEnrolled = \App\Models\StudentSection::where('ms_status', 'enrolled')->count();

        $sectionsStats = [
            'total_sections' => $allSections->count(),
            'f2f_count'      => $f2fCount,
            'flex_count'     => $flexCount,
            'total_enrolled' => $totalEnrolled,
        ];

        $schoolYear = config('services.school.year');
        $gradeTeams = \App\Models\MsTeam::where('type', 'grade')
            ->where('school_year', $schoolYear)
            ->get()
            ->keyBy('grade_level');

        return view('admin.academic.schedules', compact(
            'sections',
            'f2fSections',
            'onlineSections',
            'teachers',
            'advisories',
            'advisoryByGrade',
            'schedulesBySection',
            'days',
            'timeOptions',
            'mode',
            'unmatchedCount',
            'activeSectionId',
            'activeGradeLevel',
            'failedForm',
            'failedScheduleId',
            'allSections',
            'groupedSections',
            'sectionsStats',
            'gradeTeams',
        ));
    }

    public function store(ClassScheduleRequest $request)
    {
        $validated = $request->validated();
        $sectionId = $validated['section_id'];

        if (!empty($validated['spans_all_days'])) {
            $this->schedules->store(array_merge($validated, ['day' => 'Sunday', 'spans_all_days' => true]));
        } else {
            $days = explode(',', $validated['day']);
            foreach ($days as $day) {
                $this->schedules->store(array_merge($validated, ['day' => trim($day), 'spans_all_days' => false]));
            }
        }

        if ($request->input('_add_another')) {
            return back()
                ->with('status', 'Class schedule saved. Add your next class below.')
                ->with('schedule_workspace', 'schedule')
                ->with('reopen_add_modal', $sectionId)
                ->with('clear_draft_section', $sectionId);
        }

        return back()
            ->with('status', 'Class schedule saved.')
            ->with('schedule_workspace', 'schedule');
    }

    public function update(ClassScheduleRequest $request, ClassSchedule $schedule)
    {
        $validated = $request->validated();
        $days = explode(',', $validated['day']);
        $singleDay = trim($days[0] ?? 'Sunday');

        $this->schedules->update($schedule, array_merge($validated, [
            'day' => $singleDay,
            'spans_all_days' => !empty($validated['spans_all_days']),
        ]));

        return back()
            ->with('status', 'Class schedule updated.')
            ->with('schedule_workspace', 'schedule');
    }

    public function destroy(ClassSchedule $schedule)
    {
        Gate::authorize('manage-academic');

        // Delete siblings in the same section with same subject, start, and end time
        ClassSchedule::where('section_id', $schedule->section_id)
            ->where('subject_name', $schedule->subject_name)
            ->where('start_time', $schedule->start_time)
            ->where('end_time', $schedule->end_time)
            ->delete();

        return back()
            ->with('status', 'Class schedule deleted.')
            ->with('schedule_workspace', 'schedule');
    }

    /**
     * AJAX: Resolve an unmatched/manual teacher by assigning a teacher_key.
     */
    public function resolveTeacher(Request $request, ClassSchedule $schedule)
    {
        Gate::authorize('manage-academic');

        $request->validate([
            'teacher_key' => 'required|string',
        ]);

        $updated = $this->schedules->resolveTeacher($schedule, $request->teacher_key);

        return response()->json([
            'success'      => true,
            'teacher_key'  => $updated->teacher_key,
            'teacher_status' => $updated->teacher_status,
        ]);
    }

    public function togglePublish(\App\Models\Section $section): \Illuminate\Http\RedirectResponse
    {
        Gate::authorize('manage-academic');

        $section->update(['schedule_published' => ! $section->schedule_published]);

        $status = $section->schedule_published ? 'published' : 'drafted';

        // Auto-sync section_subjects from class_schedules on publish
        // so the student portal immediately reflects the correct teachers.
        if ($section->schedule_published) {
            $result = $this->subjectSync->sync($section);
            Log::info("SectionSubjectSync [{$section->name}]: created={$result['created']}, kept={$result['kept']}, deleted={$result['deleted']}");
        }

        return back()->with('success', "Schedule {$status} successfully.")
                     ->with('reopen_add_modal', false);
    }

    public function exportJson($sectionId)
    {
        Gate::authorize('manage-academic');

        $schedules = ClassSchedule::where('section_id', $sectionId)->get();

        // Group by subject, times, teacher, spans, is_special to combine days into comma-separated lists
        $grouped = [];
        foreach ($schedules as $s) {
            $key = md5(implode('|', [
                $s->subject_name,
                $s->teacher_display,
                $s->start_time,
                $s->end_time,
                $s->spans_all_days ? '1' : '0',
                $s->is_special ? '1' : '0'
            ]));

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'subject_name' => $s->subject_name,
                    'teacher_display' => $s->teacher_display ?? '',
                    'days' => [],
                    'start_time' => substr($s->start_time, 0, 5),
                    'end_time' => substr($s->end_time, 0, 5),
                    'spans_all_days' => (bool)$s->spans_all_days,
                    'is_special' => (bool)$s->is_special,
                ];
            }
            $grouped[$key]['days'][] = $s->day;
        }

        $formatted = [];
        foreach ($grouped as $item) {
            $formatted[] = [
                'subject_name' => $item['subject_name'],
                'teacher_display' => $item['teacher_display'],
                'day' => implode(', ', $item['days']),
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
                'spans_all_days' => $item['spans_all_days'],
                'is_special' => $item['is_special'],
            ];
        }

        return response()->json($formatted, 200, [], JSON_PRETTY_PRINT);
    }

    public function importJson(Request $request, $sectionId)
    {
        Gate::authorize('manage-academic');

        $request->validate([
            'schedule_json' => 'required|string',
        ]);

        $jsonStr = $request->schedule_json;
        $items = json_decode($jsonStr, true);

        if (!is_array($items)) {
            return back()->withErrors(['schedule_json' => 'Invalid JSON format. Must be a JSON array of schedule objects.']);
        }

        foreach ($items as $index => $item) {
            if (empty($item['subject_name'])) {
                return back()->withErrors(['schedule_json' => "Item at index {$index} is missing 'subject_name'."]);
            }
            if (empty($item['start_time']) || empty($item['end_time'])) {
                return back()->withErrors(['schedule_json' => "Item at index {$index} ('{$item['subject_name']}') is missing 'start_time' or 'end_time'."]);
            }
            if (empty($item['day']) && empty($item['spans_all_days'])) {
                return back()->withErrors(['schedule_json' => "Item at index {$index} ('{$item['subject_name']}') is missing 'day'."]);
            }
        }

        $section = \App\Models\Section::findOrFail($sectionId);

        // Delete existing schedules for this section
        ClassSchedule::where('section_id', $sectionId)->delete();

        foreach ($items as $item) {
            if (!empty($item['spans_all_days'])) {
                $this->schedules->store([
                    'section_id' => $sectionId,
                    'subject_name' => $item['subject_name'],
                    'teacher_display' => $item['teacher_display'] ?? '',
                    'day' => 'Sunday',
                    'start_time' => $item['start_time'],
                    'end_time' => $item['end_time'],
                    'spans_all_days' => true,
                    'is_special' => !empty($item['is_special']),
                    'mode' => str_contains($section->learning_mode ?? '', 'Face') || str_contains($section->learning_mode ?? '', 'f2f') ? 'f2f' : 'online',
                    'school_year' => config('services.school.year', '2026-2027'),
                ]);
            } else {
                $days = explode(',', $item['day']);
                foreach ($days as $day) {
                    $dayTrimmed = trim($day);
                    if (empty($dayTrimmed)) continue;

                    $this->schedules->store([
                        'section_id' => $sectionId,
                        'subject_name' => $item['subject_name'],
                        'teacher_display' => $item['teacher_display'] ?? '',
                        'day' => $dayTrimmed,
                        'start_time' => $item['start_time'],
                        'end_time' => $item['end_time'],
                        'spans_all_days' => false,
                        'is_special' => !empty($item['is_special']),
                        'mode' => str_contains($section->learning_mode ?? '', 'Face') || str_contains($section->learning_mode ?? '', 'f2f') ? 'f2f' : 'online',
                        'school_year' => config('services.school.year', '2026-2027'),
                    ]);
                }
            }
        }

        // Auto-sync section_subjects from class_schedules on publish
        if ($section->schedule_published) {
            $result = $this->subjectSync->sync($section);
            Log::info("SectionSubjectSync JSON Import [{$section->name}]: created={$result['created']}, kept={$result['kept']}, deleted={$result['deleted']}");
        }

        return back()
            ->with('status', 'Class schedule imported from JSON successfully.')
            ->with('schedule_workspace', 'schedule')
            ->with('active_section_id', $sectionId);
    }
}
