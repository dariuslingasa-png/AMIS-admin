<?php

namespace App\Services\Admin\Academic;

use App\Models\AcademicRoom;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicWorkspaceService
{
    public const GRADES = [
        'Kinder 1', 'Kinder 2', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4',
        'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10',
        'Grade 11', 'Grade 12',
    ];

    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

    public function __construct(
        private readonly TeacherDirectoryService $teachers,
        private readonly TeacherMatcherService $teacherMatcher,
        private readonly AcademicScheduleConflictService $conflicts,
    ) {}

    public function dashboard(): array
    {
        $schoolYear = $this->schoolYear();
        $schedules = ClassSchedule::query()->forSchoolYear($schoolYear)->get();
        $sections = Section::query()->withCount('students')->get();

        return $this->shared([
            'stats' => [
                'teachers' => count($this->teacherMatcher->allTeachers()),
                'subjects' => Subject::query()->where('school_year', $schoolYear)->where('status', 'active')->count(),
                'sections' => $sections->count(),
                'scheduled_hours' => round($schedules->sum(fn (ClassSchedule $row) => $row->endMinutes() - $row->startMinutes()) / 60, 1),
            ],
            'departments' => [
                [
                    'title' => 'Junior High School',
                    'description' => 'Build and audit schedules for Kinder 1 through Grade 6.',
                    'grades' => array_slice(self::GRADES, 0, 8),
                    'sections' => $sections->whereIn('grade_level', array_slice(self::GRADES, 0, 8))->count(),
                    'route' => route('admin.academic.builder', ['level' => 'elementary', 'school_year' => $schoolYear]),
                    'tone' => 'indigo',
                ],
                [
                    'title' => 'Senior High School',
                    'description' => 'Build and audit schedules for Grade 7 through Grade 12.',
                    'grades' => array_slice(self::GRADES, 8),
                    'sections' => $sections->whereIn('grade_level', array_slice(self::GRADES, 8))->count(),
                    'route' => route('admin.academic.builder', ['level' => 'secondary', 'school_year' => $schoolYear]),
                    'tone' => 'violet',
                ],
            ],
            'unmatchedTeachers' => $schedules->where('teacher_status', 'unmatched')->count(),
            'unpublishedSections' => $sections->where('schedule_published', false)->count(),
        ]);
    }

    public function subjects(Request $request): array
    {
        $query = Subject::query()
            ->where('school_year', $this->schoolYear())
            ->withCount('activeTeacherAssignments');
        $this->search($query, $request->string('q')->toString(), ['name', 'code', 'description']);

        $query->when($request->filled('grade'), fn ($builder) => $builder->where('grade_level', $request->string('grade')))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')))
            ->orderByRaw($this->gradeOrderSql())
            ->orderBy('name');

        return $this->shared(['subjects' => $query->paginate(18)->withQueryString()]);
    }

    public function sections(Request $request): array
    {
        $query = Section::query()->withCount(['students', 'subjects', 'schedules']);
        $this->search($query, $request->string('q')->toString(), ['name', 'grade_level', 'learning_mode', 'track_strand']);

        $query->when($request->filled('grade'), fn ($builder) => $builder->where('grade_level', $request->string('grade')))
            ->when($request->filled('mode'), function ($builder) use ($request) {
                $request->string('mode')->toString() === 'f2f'
                    ? $builder->where('learning_mode', 'like', '%Face%')
                    : $builder->where(fn ($query) => $query->where('learning_mode', 'like', '%Online%')->orWhere('learning_mode', 'like', '%Flexible%'));
            })
            ->when($request->filled('status'), fn ($builder) => $builder->where('academic_status', $request->string('status')))
            ->orderByRaw($this->gradeOrderSql())
            ->orderBy('name');

        return $this->shared(['sections' => $query->paginate(16)->withQueryString()]);
    }

    public function rooms(Request $request): array
    {
        $query = AcademicRoom::query()->withCount('schedules');
        $this->search($query, $request->string('q')->toString(), ['name', 'room_type']);
        $query->when($request->filled('type'), fn ($builder) => $builder->where('room_type', $request->string('type')))
            ->when($request->filled('status'), fn ($builder) => $builder->where('status', $request->string('status')))
            ->orderBy('name');

        return $this->shared([
            'rooms' => $query->paginate(16)->withQueryString(),
            'roomTypes' => AcademicRoom::query()->whereNotNull('room_type')->distinct()->orderBy('room_type')->pluck('room_type'),
        ]);
    }

    public function teachers(Request $request): array
    {
        $payload = $this->teachers->indexPayload($request->query('edit'));
        $items = collect($payload['teachers'])
            ->when($request->filled('q'), fn (Collection $rows) => $rows->filter(function (array $teacher) use ($request) {
                $query = mb_strtolower($request->string('q')->toString());

                return str_contains(mb_strtolower(implode(' ', [
                    $teacher['name'] ?? '', $teacher['email'] ?? '', $teacher['dept'] ?? '',
                ])), $query);
            }))
            ->when($request->filled('status'), fn (Collection $rows) => $rows->where('status', $request->string('status')->toString()))
            ->sortBy('name')
            ->values();

        $page = Paginator::resolveCurrentPage();
        $perPage = 18;
        $payload['teachers'] = new Paginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return $this->shared($payload);
    }

    public function builder(Request $request): array
    {
        $level = $request->string('level', 'elementary')->toString();
        $gradePool = $level === 'secondary' ? array_slice(self::GRADES, 8) : array_slice(self::GRADES, 0, 8);
        $sections = Section::query()
            ->whereIn('grade_level', $gradePool)
            ->where('academic_status', 'active')
            ->withCount('students')
            ->orderByRaw($this->gradeOrderSql())
            ->orderBy('name')
            ->get();

        $activeSection = $sections->firstWhere('id', (int) $request->query('section')) ?? $sections->first();
        $mode = $activeSection && str_contains(mb_strtolower((string) $activeSection->learning_mode), 'face') ? 'f2f' : 'online';
        $schedules = $activeSection
            ? ClassSchedule::query()
                ->with(['section', 'subject', 'room'])
                ->where('section_id', $activeSection->id)
                ->forSchoolYear($this->schoolYear())
                ->orderBy('start_time')
                ->get()
            : collect();

        $timeRows = $schedules
            ->groupBy(fn (ClassSchedule $row) => substr($row->start_time, 0, 5).'|'.substr($row->end_time, 0, 5))
            ->sortBy(fn (Collection $rows, string $key) => $key)
            ->map(function (Collection $rows, string $key) {
                [$start, $end] = explode('|', $key);

                return [
                    'key' => $key,
                    'start' => $start,
                    'end' => $end,
                    'label' => date('g:i A', strtotime($start)).' – '.date('g:i A', strtotime($end)),
                    'duration' => $rows->first()->endMinutes() - $rows->first()->startMinutes(),
                    'all_days' => $rows->firstWhere('spans_all_days', true),
                    'days' => $rows->keyBy('day'),
                ];
            })->values();

        return $this->shared([
            'level' => $level,
            'sections' => $sections,
            'activeSection' => $activeSection,
            'mode' => $mode,
            'schedules' => $schedules,
            'timeRows' => $timeRows,
            'days' => self::DAYS,
            'teachers' => $this->teacherMatcher->allTeachers(),
            'subjects' => Subject::query()
                ->where('school_year', $this->schoolYear())
                ->where('status', 'active')
                ->orderByRaw($this->gradeOrderSql())
                ->orderBy('name')
                ->get(),
            'rooms' => AcademicRoom::query()->where('status', 'active')->orderBy('name')->get(),
            'conflicts' => $this->conflicts->conflictsFor($schedules),
        ]);
    }

    public function workload(Request $request): array
    {
        $teacherRows = collect($this->teachers->indexPayload()['teachers']);
        $schedules = ClassSchedule::query()
            ->with(['subject:id,name,weekly_hours', 'section:id,name,grade_level'])
            ->forSchoolYear($this->schoolYear())
            ->whereNotNull('teacher_key')
            ->get()
            ->groupBy('teacher_key');

        $workloads = $teacherRows->map(function (array $teacher) use ($schedules) {
            $assigned = $schedules->get($teacher['id'], collect());
            $assignments = $assigned->groupBy(fn (ClassSchedule $row) => $row->section_id.'|'.($row->subject_id ?: mb_strtolower($row->subject_name)));
            $hours = $assignments->sum(function (Collection $rows) {
                $subjectHours = $rows->first()->subject?->weekly_hours;

                return $subjectHours !== null
                    ? (float) $subjectHours
                    : $rows->sum(fn (ClassSchedule $row) => $row->endMinutes() - $row->startMinutes()) / 60;
            });
            $max = (float) ($teacher['max_load'] ?? 40);

            return $teacher + [
                'assigned_hours' => round($hours, 1),
                'remaining_hours' => round(max(0, $max - $hours), 1),
                'max_load' => $max,
                'utilization' => $max > 0 ? min(100, (int) round(($hours / $max) * 100)) : 0,
                'assignments_count' => $assignments->count(),
            ];
        })->when($request->filled('q'), fn (Collection $rows) => $rows->filter(fn (array $row) => str_contains(mb_strtolower($row['name']), mb_strtolower($request->string('q')->toString()))))
            ->sortByDesc('utilization')
            ->values();

        $selectedTeacher = $request->filled('teacher')
            ? $workloads->firstWhere('id', $request->string('teacher')->toString())
            : null;
        $selectedSchedules = $selectedTeacher
            ? ClassSchedule::query()
                ->with(['section:id,name,grade_level,learning_mode,shift,gender', 'subject:id,name,weekly_hours', 'room:id,name'])
                ->forSchoolYear($this->schoolYear())
                ->where('teacher_key', $selectedTeacher['id'])
                ->orderByRaw("CASE day WHEN 'Sunday' THEN 1 WHEN 'Monday' THEN 2 WHEN 'Tuesday' THEN 3 WHEN 'Wednesday' THEN 4 WHEN 'Thursday' THEN 5 ELSE 9 END")
                ->orderBy('start_time')
                ->get()
            : collect();

        return $this->shared(compact('workloads', 'selectedTeacher', 'selectedSchedules'));
    }

    public function reports(Request $request): array
    {
        $type = in_array($request->query('type'), ['section', 'teacher', 'room'], true) ? $request->query('type') : 'section';
        $sections = Section::query()->where('academic_status', 'active')->orderByRaw($this->gradeOrderSql())->orderBy('name')->get();
        $teachers = collect($this->teacherMatcher->allTeachers())->sortBy('name')->values();
        $rooms = AcademicRoom::query()->where('status', 'active')->orderBy('name')->get();
        $entities = match ($type) {
            'teacher' => $teachers,
            'room' => $rooms,
            default => $sections,
        };
        $selected = (string) ($request->query('entity') ?? data_get($entities->first(), 'id', ''));

        $query = ClassSchedule::query()->with(['section', 'subject', 'room'])->forSchoolYear($this->schoolYear());
        match ($type) {
            'teacher' => $query->where('teacher_key', $selected),
            'room' => $query->where('room_id', (int) $selected),
            default => $query->where('section_id', (int) $selected),
        };

        $schedules = $query->orderBy('start_time')->get();

        return $this->shared(compact('type', 'sections', 'teachers', 'rooms', 'entities', 'selected', 'schedules'));
    }

    public function schoolYear(): string
    {
        $requested = request()->query('school_year');
        if (is_string($requested) && preg_match('/^\d{4}-\d{4}$/', $requested) === 1) {
            session(['academic_school_year' => $requested]);

            return $requested;
        }

        $stored = session('academic_school_year');
        if (is_string($stored) && preg_match('/^\d{4}-\d{4}$/', $stored) === 1) {
            return $stored;
        }

        return (string) config('services.school.year', '2026-2027');
    }

    private function shared(array $payload): array
    {
        $schoolYear = $this->schoolYear();

        return $payload + [
            'schoolYear' => $schoolYear,
            'schoolYears' => $this->schoolYears($schoolYear),
            'gradeOptions' => self::GRADES,
        ];
    }

    private function search($query, string $term, array $columns): void
    {
        if ($term === '') {
            return;
        }

        $query->where(function ($builder) use ($term, $columns) {
            foreach ($columns as $column) {
                $builder->orWhere($column, 'like', "%{$term}%");
            }
        });
    }

    private function gradeOrderSql(): string
    {
        $cases = collect(self::GRADES)
            ->map(fn (string $grade, int $index) => "WHEN '".str_replace("'", "''", $grade)."' THEN ".($index + 1))
            ->implode(' ');

        return "CASE grade_level {$cases} ELSE 999 END";
    }

    private function schoolYears(string $selected): Collection
    {
        $years = collect([$selected, (string) config('services.school.year', '2026-2027')]);

        if (Schema::hasTable('school_years')) {
            $years = $years->merge(DB::table('school_years')
                ->where('status', 'active')
                ->pluck('code'));
        }

        if (Schema::hasTable('class_schedules')) {
            $years = $years->merge(ClassSchedule::query()->distinct()->pluck('school_year'));
        }

        if (Schema::hasTable('subjects')) {
            $years = $years->merge(Subject::query()->distinct()->pluck('school_year'));
        }

        return $years
            ->filter(fn ($year) => is_string($year) && preg_match('/^\d{4}-\d{4}$/', $year) === 1)
            ->unique()
            ->sortDesc()
            ->values();
    }
}
