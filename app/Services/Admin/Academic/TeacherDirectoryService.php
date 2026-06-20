<?php

namespace App\Services\Admin\Academic;

use App\Repositories\AcademicRepository;
use App\Repositories\TeacherRepository;
use App\Services\ImageOptimizerService;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TeacherDirectoryService
{
    public function __construct(
        private readonly AcademicRepository $academic,
        private readonly TeacherRepository $teachers,
        private readonly TeacherAccountService $accounts,
        private readonly TeacherSubjectAssignmentService $assignments
    ) {
    }

    public function indexPayload(?string $editId = null): array
    {
        $teachers = $this->teachers()->values()->all();
        $teacherGroups = collect(['Elementary Department', 'High School Department', 'Islamic School and Arabic Language Department'])
            ->map(fn (string $department) => [
                'name' => $department,
                'teachers' => collect($teachers)->where('dept', $department)->values(),
            ]);

        $unassigned = collect($teachers)->filter(fn (array $teacher) => blank($teacher['dept'] ?? null))->values();
        if ($unassigned->isNotEmpty()) {
            $teacherGroups->push(['name' => 'Unassigned Department', 'teachers' => $unassigned]);
        }

        $dbEmails = \App\Models\User::where('email', 'like', '%@amis.edu.ph')->pluck('email');
        $studentEmails = \App\Models\Student::whereNotNull('school_email')->pluck('school_email');
        $teacherEmails = collect($teachers)->pluck('email');
        $emailBank = $dbEmails->merge($studentEmails)->merge($teacherEmails)->unique()->sort()->values()->all();

        return [
            'teachers' => $teachers,
            'teacherGroups' => $teacherGroups,
            'selectedTeacher' => collect($teachers)->firstWhere('id', (string) $editId),
            'emailBank' => $emailBank,
        ];
    }

    public function create(array $data, Request $request): array
    {
        $teacher = $this->normalize($data);
        $id = $this->uniqueTeacherId($teacher['name']);
        $teacher['photo'] = $request->hasFile('photo') ? $this->storePhoto($request, $id) : null;
        $teacher['password_changed'] = 'No';
        $teacher['temporary_password'] = $this->accounts->generatePassword();

        $this->teachers->saveTeacher($id, $teacher);
        $this->accounts->syncCreatedTeacher($teacher, $teacher['temporary_password']);

        return ['id' => $id, 'teacher' => $teacher];
    }

    public function update(array $data, Request $request): array
    {
        $id = Str::slug($data['id']);
        $existing = $this->teachers->findOverride($id) ?? [];
        $teacher = $this->normalize($data);
        $oldEmail = $existing['email'] ?? null;
        $password = $existing['temporary_password'] ?? null;

        $teacher['photo'] = $request->hasFile('photo') ? $this->storePhoto($request, $id) : ($existing['photo'] ?? null);
        $teacher['password_changed'] = $existing['password_changed'] ?? 'No';
        $teacher['subjects'] = $this->cleanSubjects($existing['subjects'] ?? []);

        if ($oldEmail && strtolower($oldEmail) !== strtolower($teacher['email'])) {
            $teacher['password_changed'] = 'No';
            $password = $this->accounts->generatePassword();
        } elseif (blank($password) && $teacher['password_changed'] === 'No') {
            $password = $this->accounts->generatePassword();
        }

        $teacher['temporary_password'] = $password;
        $this->teachers->saveTeacher($id, $teacher);
        $this->accounts->syncUpdatedTeacher($teacher, $existing, $password, $oldEmail);

        return ['id' => $id, 'teacher' => $teacher];
    }

    public function updateSubjects(string $id, array $subjects): void
    {
        $teacher = $this->findRaw($id);
        abort_unless($teacher, 404, 'Teacher not found.');

        $teacher['subjects'] = $this->assignments->sync($teacher, $subjects, auth()->id());
        $this->teachers->saveTeacher(Str::slug($id), $teacher);
    }

    public function resetCredentials(string $id): array
    {
        $teacher = $this->findRaw($id);
        abort_unless($teacher, 404, 'Teacher not found.');
        $credentials = $this->accounts->resetCredentials($teacher);
        $teacher['password_changed'] = 'No';
        $teacher['temporary_password'] = $credentials['password'];
        $this->teachers->saveTeacher(Str::slug($id), $teacher);

        return $credentials;
    }

    public function togglePasswordChanged(string $id): void
    {
        $teacher = $this->findRaw($id);
        abort_unless($teacher, 404, 'Teacher not found.');
        $teacher['password_changed'] = ($teacher['password_changed'] ?? 'No') === 'Yes' ? 'No' : 'Yes';
        $this->teachers->saveTeacher(Str::slug($id), $teacher);
    }

    public function profile(string $id): array
    {
        $teacher = $this->findRaw($id);
        abort_unless($teacher, 404, 'Teacher not found.');
        $teacher = array_merge($this->defaults(), $teacher);
        $teacher = array_merge($teacher, $this->subjectLoad($teacher));
        $teacher['initials'] = $this->initials($teacher['name']);

        $globalAssignments = TeacherSubjectAssignment::with('subject')
            ->where('status', 'active')
            ->where('teacher_key', '!=', $teacher['id'])
            ->get()
            ->map(fn ($assignment) => [
                'subject_id' => $assignment->subject_id,
                'subject_name' => $assignment->subject?->name,
                'grade_level' => $assignment->subject?->grade_level,
                'teacher_name' => $assignment->teacher_name,
            ])
            ->all();

        // Retrieve teacher schedules
        $scheduleService = app(\App\Services\Admin\Academic\ClassScheduleService::class);
        $rawSchedules = \App\Models\SectionSubject::with('section')
            ->get()
            ->filter(fn($ss) => strtolower(trim($ss->teacher_name)) === strtolower(trim($teacher['name'])));
        
        $schedules = $rawSchedules->map(function($ss) use ($scheduleService) {
            $presented = $scheduleService->present($ss);
            $presented['section_name'] = $ss->section ? ($ss->section->grade_level . ' - ' . ($ss->section->name ?? 'General')) : 'Unknown';
            return $presented;
        })
        ->sortBy([['day_index', 'asc'], ['start_minutes', 'asc']])
        ->values()
        ->all();

        return [
            'teacher' => $teacher,
            'isHighSchool' => str_contains($teacher['dept'], 'High'),
            'isIslamicArabic' => str_contains($teacher['dept'], 'Islamic School'),
            'assignmentHistory' => $this->assignments->history($teacher['id']),
            'globalAssignments' => $globalAssignments,
            'schedules' => $schedules,
        ];
    }

    public function find(string $id): ?array
    {
        return $this->teachers()->firstWhere('id', Str::slug($id));
    }

    private function findRaw(string $id): ?array
    {
        $id = Str::slug($id);
        $overrides = $this->teachers->overrides();
        $base = $this->baseTeachers()->firstWhere('id', $id);

        if (! $base && ! isset($overrides[$id])) {
            return null;
        }

        return array_merge($this->defaults(), $base ?? ['id' => $id], $overrides[$id] ?? []);
    }

    private function teachers(): Collection
    {
        $overrides = $this->teachers->overrides();
        $base = $this->baseTeachers();
        $baseIds = $base->pluck('id')->all();
        $custom = collect($overrides)->reject(fn ($teacher, string $id) => in_array($id, $baseIds, true))
            ->map(fn (array $teacher, string $id) => array_merge($this->defaults(), ['id' => $id], $teacher));

        return $base->map(fn (array $teacher) => array_merge($this->defaults(), $teacher, $overrides[$teacher['id']] ?? []))
            ->merge($custom)
            ->map(fn (array $teacher) => array_merge($teacher, $this->subjectLoad($teacher)));
    }

    private function baseTeachers(): Collection
    {
        $advisory = $this->academic->advisoryRows()->map(fn (array $row) => [
            'id' => $this->teacherId($row['teacher']),
            'name' => $row['teacher'],
            'email' => $this->teacherEmail($row['teacher']),
            'dept' => $row['department'],
            'sections' => '',
            'status' => 'Active',
            'photo' => $row['photo'] ?? null,
        ]);

        $advisoryIds = $advisory->pluck('id')->all();
        $baseIsal = collect([
            ['id' => 'ust-raffy-lingasa', 'name' => 'Ust. Raffy Lingasa', 'email' => 'tr.rlingasa@amis.edu.ph', 'sections' => '', 'dept' => 'Islamic School and Arabic Language Department', 'status' => 'Active', 'photo' => null],
            ['id' => 'ust-ahmad-al-jamil', 'name' => 'Ust. Ahmad Al-Jamil', 'email' => 'tr.ajamil@amis.edu.ph', 'sections' => '', 'dept' => 'Islamic School and Arabic Language Department', 'status' => 'Active', 'photo' => null],
            ['id' => 'ust-omar-mukhtar', 'name' => 'Ust. Omar Mukhtar', 'email' => 'tr.omukhtar@amis.edu.ph', 'sections' => '', 'dept' => 'Islamic School and Arabic Language Department', 'status' => 'Inactive', 'photo' => null],
            ['id' => 'ustadh-jaisam', 'name' => 'Ustadh Jaisam', 'email' => 'tr.jaisam@amis.edu.ph', 'sections' => '', 'dept' => 'Islamic School and Arabic Language Department', 'status' => 'Active', 'photo' => null],
            ['id' => 'ustadha-saliha', 'name' => 'Ustadha Saliha', 'email' => 'tr.saliha@amis.edu.ph', 'sections' => '', 'dept' => 'Islamic School and Arabic Language Department', 'status' => 'Active', 'photo' => null],
            ['id' => 'ustadha-isal', 'name' => 'Ustadha Isal', 'email' => 'tr.isal@amis.edu.ph', 'sections' => '', 'dept' => 'Islamic School and Arabic Language Department', 'status' => 'Active', 'photo' => null],
        ])->reject(fn ($item) => in_array($item['id'], $advisoryIds, true));

        $baseSubjectTeachers = collect([
            ['id' => 'mon-zhairel-lingasa', 'name' => 'Mon Zhairel Lingasa', 'email' => 'tr.mlingasa@amis.edu.ph', 'sections' => '', 'dept' => 'Elementary Department', 'status' => 'Active', 'photo' => null],
        ])->reject(fn ($item) => in_array($item['id'], $advisoryIds, true));

        return $advisory->merge($baseIsal)->merge($baseSubjectTeachers);
    }

    private function normalize(array $data): array
    {
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $microsoftSync = isset($data['microsoft_sync']) ? (bool) $data['microsoft_sync'] : false;

        return array_merge($this->defaults(), $data, [
            'name' => $name,
            'email' => $email,
            'microsoft_sync' => $microsoftSync,
        ]);
    }

    private function subjectLoad(array $teacher): array
    {
        $target = 8;
        $allSubjects = \App\Models\Subject::where('status', 'active')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();
        $activeIds = $this->assignments->activeSubjectIds($teacher['id'] ?? '');
        $assigned = $allSubjects->whereIn('id', $activeIds)->values();
        $fallbackNames = collect($teacher['subjects'] ?? [])->filter()->unique()->values();

        if ($assigned->isEmpty() && $fallbackNames->isNotEmpty()) {
            $assigned = $allSubjects->filter(fn ($subject) => $fallbackNames->contains($subject->name))->values();
        }

        $count = $assigned->count();

        return [
            'subjects' => $assigned->pluck('name')->all(),
            'subject_ids' => $assigned->pluck('id')->all(),
            'subject_options' => $allSubjects->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'grade_level' => $subject->grade_level,
            ])->values()->all(),
            'subject_count' => $count,
            'load_target' => $target,
            'load_percent' => min(100, (int) round(($count / $target) * 100)),
            'load_status' => $count >= $target ? 'Full Load' : ($count >= 6 ? 'Balanced Load' : 'Needs Load'),
        ];
    }

    private function defaults(): array
    {
        return ['name' => '', 'email' => '', 'dept' => '', 'sections' => '', 'status' => 'Active', 'license' => 'faculty_a1', 'photo' => null, 'first_name' => '', 'middle_name' => '', 'last_name' => '', 'gender' => 'Male', 'birthdate' => '', 'contact_number' => '', 'address' => '', 'password_changed' => 'No', 'temporary_password' => null, 'microsoft_sync' => true];
    }

    private function subjectPool(string $department): array
    {
        return str_contains($department, 'Islamic School')
            ? ['Qur’an', 'Arabic Language', 'SHAF', 'Seerah', 'Hadith', 'Aqeedah', 'Fiqh', 'Islamic Values']
            : (str_contains($department, 'High School') ? ['English', 'Mathematics', 'Science', 'Araling Panlipunan', 'Filipino', 'MAPEH', 'TLE', 'Computer Education'] : ['Reading and Literacy', 'Mathematics', 'Science', 'English', 'Filipino', 'GMRC', 'MAPEH', 'Computer Education']);
    }

    private function storePhoto(Request $request, string $id): string
    {
        $file = $request->file('photo');
        $optimizer = new ImageOptimizerService();
        if ($optimizer->isOptimizable($file->getClientMimeType())) {
            return 'storage/' . $optimizer->optimize($file, 'public', 'images/teachers', $id)['optimized'];
        }
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';

        return 'storage/' . $file->storeAs('images/teachers/original', "{$id}.{$extension}", 'public');
    }

    private function teacherEmail(string $name): string
    {
        $normalized = strtolower(trim($name));
        if ($normalized === 'ust. ahmad al-jamil' || $normalized === 'ust. ahmad al jamil') {
            return 'tr.ajamil@amis.edu.ph';
        }

        $cleanName = Str::of($name)->replaceMatches('/^(teacher|ust\.|ustadz\.?|ustadh\.?|ustadha\.?|sir\.?|ma\'am\.?|maam\.?|ms\.?|mrs\.?|mr\.?)\s+/i', '')->ascii()->lower()->replaceMatches('/[^a-z\s]/', '')->squish();
        $parts = explode(' ', (string) $cleanName);

        return count($parts) >= 2 ? 'tr.' . substr($parts[0], 0, 1) . end($parts) . '@amis.edu.ph' : 'tr.' . $cleanName . '@amis.edu.ph';
    }

    private function teacherId(string $name): string
    {
        return Str::slug(Str::of($name)->ascii());
    }

    private function uniqueTeacherId(string $name): string
    {
        $base = $this->teacherId($name) ?: 'teacher';
        $id = $base;
        $reserved = $this->baseTeachers()->pluck('id')->merge(array_keys($this->teachers->overrides()))->all();
        for ($counter = 2; in_array($id, $reserved, true); $counter++) {
            $id = "{$base}-{$counter}";
        }

        return $id;
    }

    public function delete(string $id): void
    {
        $teacher = $this->findRaw($id);
        if (!$teacher) {
            return;
        }

        // Find matching local user first to get their microsoft_id, UPN, or local username
        $localUser = null;
        $slug = Str::slug($teacher['name'] ?? '');
        if (!empty($teacher['email']) || !empty($slug) || !empty($id)) {
            $localUser = \App\Models\User::where('role', 'teacher')
                ->where(function ($query) use ($teacher, $id, $slug) {
                    if (!empty($teacher['email'])) {
                        $query->orWhere('email', $teacher['email']);
                    }
                    if (!empty($slug)) {
                        $query->orWhere('username', $slug);
                        $query->orWhere('name', $teacher['name']);
                    }
                    $query->orWhere('username', $id);
                    $query->orWhere('username', Str::slug($id));
                })
                ->first();
        }

        // 1. Delete Microsoft 365 account if configured
        $microsoftIdentifier = $localUser?->microsoft_id ?? $localUser?->email ?? $teacher['email'] ?? null;
        if (!empty($microsoftIdentifier)) {
            try {
                $graph = new \App\Services\MicrosoftGraphService();
                if ($graph->userExists($microsoftIdentifier)) {
                    $graph->deleteAzureUser($microsoftIdentifier);
                    try {
                        \App\Models\AdminAuditLog::record('teacher_microsoft_deleted', true, "Deleted Microsoft account for teacher {$microsoftIdentifier}");
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to log audit for Microsoft user deletion: " . $e->getMessage());
                    }
                }
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::error("Failed to delete Microsoft account for teacher {$microsoftIdentifier}: " . $exception->getMessage());
            }
        }

        // 2. Delete matching user from DB
        \App\Models\User::where('role', 'teacher')
            ->where(function ($query) use ($teacher, $id, $slug) {
                if (!empty($teacher['email'])) {
                    $query->orWhere('email', $teacher['email']);
                }
                if (!empty($slug)) {
                    $query->orWhere('username', $slug);
                    $query->orWhere('name', $teacher['name']);
                }
                $query->orWhere('username', $id);
                $query->orWhere('username', Str::slug($id));
            })
            ->delete();

        // 3. Delete subject assignments
        \App\Models\TeacherSubjectAssignment::where('teacher_key', $id)
            ->orWhere('teacher_key', Str::slug($id))
            ->orWhere('teacher_name', $teacher['name'])
            ->delete();

        // 4. Remove from JSON overrides
        $overrides = $this->teachers->overrides();
        unset($overrides[$id]);
        unset($overrides[Str::slug($id)]);
        foreach ($overrides as $key => $val) {
            if ((isset($val['email']) && !empty($teacher['email']) && strtolower($val['email']) === strtolower($teacher['email'])) ||
                (isset($val['name']) && strtolower($val['name']) === strtolower($teacher['name']))) {
                unset($overrides[$key]);
            }
        }
        $this->teachers->saveOverrides($overrides);
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', str_replace(['Ust. ', 'Tchr. ', 'TEACHER '], '', $name)))->filter()->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('');
    }
}
