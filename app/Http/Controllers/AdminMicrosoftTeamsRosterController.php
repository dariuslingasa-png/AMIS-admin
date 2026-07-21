<?php

namespace App\Http\Controllers;

use App\Http\Requests\ManualMicrosoftAccountMatchRequest;
use App\Http\Requests\UpdateMicrosoftTeamMappingRequest;
use App\Jobs\SyncMicrosoftTeamRosterJob;
use App\Jobs\SyncMicrosoftTeamsRosterJob;
use App\Models\Academic\GradeLevel;
use App\Models\Academic\SchoolYear;
use App\Models\AdminAuditLog;
use App\Models\MicrosoftSyncRun;
use App\Models\MicrosoftTeam;
use App\Models\MicrosoftTeamMembership;
use App\Models\MicrosoftTeamSectionMapping;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Microsoft\MicrosoftAccountMatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminMicrosoftTeamsRosterController extends Controller
{
    public function index(Request $request): View
    {
        $teams = MicrosoftTeam::query()
            ->with(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section'])
            ->when($request->filled('search'), fn (Builder $query) => $query->where('display_name', 'like', '%'.$request->string('search')->trim().'%'))
            ->when($request->filled('category'), fn (Builder $query) => $query->where('team_category', $request->string('category')))
            ->when($request->filled('mapping_status'), fn (Builder $query) => $query->whereHas('mapping', fn (Builder $mapping) => $mapping->where('mapping_status', $request->string('mapping_status'))))
            ->when($request->filled('school_year_id'), fn (Builder $query) => $query->whereHas('mapping', fn (Builder $mapping) => $mapping->where('school_year_id', $request->integer('school_year_id'))))
            ->when($request->filled('grade_level_id'), fn (Builder $query) => $query->whereHas('mapping', fn (Builder $mapping) => $mapping->where('grade_level_id', $request->integer('grade_level_id'))));

        $sort = in_array($request->string('sort')->toString(), ['display_name', 'member_count', 'last_synced_at'], true)
            ? $request->string('sort')->toString()
            : 'display_name';
        $direction = $request->string('direction')->toString() === 'desc' ? 'desc' : 'asc';
        $teams = $teams->orderBy($sort, $direction)->paginate(25)->withQueryString();

        $category = $request->filled('category') ? $request->string('category')->toString() : null;

        $stats = [
            'teams' => MicrosoftTeam::query()->where('is_active', true)
                ->when($category, fn ($q) => $q->where('team_category', $category))
                ->count(),
            'memberships' => MicrosoftTeamMembership::query()->where('is_active', true)
                ->when($category, fn ($q) => $q->whereHas('team', fn ($t) => $t->where('team_category', $category)))
                ->count(),
            'owners' => MicrosoftTeamMembership::query()->where('is_active', true)->where('team_role', 'owner')
                ->when($category, fn ($q) => $q->whereHas('team', fn ($t) => $t->where('team_category', $category)))
                ->count(),
            'matched_students' => MicrosoftTeamMembership::query()->where('is_active', true)->where('match_status', 'matched_student')
                ->when($category, fn ($q) => $q->whereHas('team', fn ($t) => $t->where('team_category', $category)))
                ->count(),
            'matched_faculty' => MicrosoftTeamMembership::query()->where('is_active', true)->whereIn('match_status', ['matched_faculty', 'matched_staff'])
                ->when($category, fn ($q) => $q->whereHas('team', fn ($t) => $t->where('team_category', $category)))
                ->count(),
            'unmatched' => MicrosoftTeamMembership::query()->where('is_active', true)->whereIn('match_status', ['unmatched', 'multiple_matches', 'manual_review'])
                ->when($category, fn ($q) => $q->whereHas('team', fn ($t) => $t->where('team_category', $category)))
                ->count(),
            'confirmed_mappings' => MicrosoftTeamSectionMapping::query()->where('mapping_status', 'confirmed')
                ->when($category, fn ($q) => $q->whereHas('team', fn ($t) => $t->where('team_category', $category)))
                ->count(),
            'last_successful_sync' => MicrosoftSyncRun::query()->whereIn('status', ['completed', 'completed_with_warnings'])->latest('completed_at')->first(),
        ];

        return view('admin.microsoft-roster.index', [
            'teams' => $teams,
            'stats' => $stats,
            'currentRun' => MicrosoftSyncRun::query()->with('startedBy')->whereIn('status', ['queued', 'running'])->latest()->first()
                ?? MicrosoftSyncRun::query()->with('startedBy')->latest()->first(),
            'schoolYears' => SchoolYear::query()->orderByDesc('is_active')->orderByDesc('code')->get(),
            'gradeLevels' => GradeLevel::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function accounts(Request $request): View
    {
        $accounts = $this->accountDirectoryQuery($request)
            ->paginate(30)
            ->withQueryString();

        return view('admin.microsoft-roster.accounts', compact('accounts'));
    }

    public function show(Request $request, MicrosoftTeam $team): View
    {
        $memberships = $team->memberships()
            ->with(['student.user', 'student.studentSection', 'faculty'])
            ->when($request->filled('search'), function (Builder $query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('display_name', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('user_principal_name', 'like', $search)
                        ->orWhereHas('student', fn (Builder $student) => $student->where('student_number', 'like', $search));
                });
            })
            ->when($request->filled('filter'), function (Builder $query) use ($request) {
                match ($request->string('filter')->toString()) {
                    'students' => $query->where('match_status', 'matched_student'),
                    'faculty' => $query->whereIn('account_type', ['faculty', 'staff', 'admin']),
                    'unmatched' => $query->whereIn('match_status', ['unmatched', 'multiple_matches', 'manual_review']),
                    'owners' => $query->where('team_role', 'owner'),
                    'members' => $query->where('team_role', 'member'),
                    'inactive' => $query->where('is_active', false),
                    default => null,
                };
            })
            ->orderByDesc('is_active')
            ->orderBy('display_name')
            ->paginate(40)
            ->withQueryString();

        $team->load(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section']);

        return view('admin.microsoft-roster.show', compact('team', 'memberships'));
    }

    public function mappings(Request $request): View
    {
        $teams = MicrosoftTeam::query()
            ->with(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section'])
            ->when($request->filled('status'), fn (Builder $query) => $query->whereHas('mapping', fn (Builder $mapping) => $mapping->where('mapping_status', $request->string('status'))))
            ->orderBy('display_name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.microsoft-roster.mappings', compact('teams'));
    }

    public function editMapping(MicrosoftTeam $team): View
    {
        $team->load('mapping');

        return view('admin.microsoft-roster.mapping-edit', [
            'team' => $team,
            'schoolYears' => SchoolYear::query()->orderByDesc('is_active')->orderByDesc('code')->get(),
            'gradeLevels' => GradeLevel::query()->orderBy('sort_order')->get(),
            'sections' => Section::query()->orderBy('grade_level')->orderBy('name')->get(),
        ]);
    }

    public function updateMapping(UpdateMicrosoftTeamMappingRequest $request, MicrosoftTeam $team): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['confirm_mapping']);
        $validated['not_official_class'] = $request->boolean('not_official_class');
        $validated['mapping_status'] = 'confirmed';
        $validated['mapping_method'] = 'manual';
        $validated['confirmed_by'] = $request->user()->id;
        $validated['confirmed_at'] = now();

        $mapping = MicrosoftTeamSectionMapping::query()->updateOrCreate(
            ['microsoft_team_local_id' => $team->id],
            $validated,
        );

        $team->update([
            'school_year_id' => $mapping->school_year_id,
            'team_category' => $mapping->program_type ?: $team->team_category,
        ]);

        AdminAuditLog::record('microsoft_team_section_mapping_confirmed', true, "Confirmed Microsoft Team mapping for {$team->display_name}.", [
            'microsoft_team_local_id' => $team->id,
            'mapping_id' => $mapping->id,
        ]);

        return redirect()->route('admin.microsoft-roster.mappings')->with('success', 'The mapping was confirmed. Official AMIS student sections were not changed.');
    }

    public function ignoreMapping(Request $request, MicrosoftTeam $team): RedirectResponse
    {
        $mapping = MicrosoftTeamSectionMapping::query()->updateOrCreate(
            ['microsoft_team_local_id' => $team->id],
            [
                'mapping_status' => 'ignored',
                'mapping_method' => 'manual',
                'not_official_class' => true,
                'confirmed_by' => $request->user()->id,
                'confirmed_at' => now(),
            ],
        );

        AdminAuditLog::record('microsoft_team_marked_ignored', true, "Marked Microsoft Team as not an official class: {$team->display_name}.", [
            'mapping_id' => $mapping->id,
        ]);

        return back()->with('success', 'The Team was marked as not an official class section.');
    }

    public function destroyMapping(MicrosoftTeam $team): RedirectResponse
    {
        $team->mapping()->delete();
        $team->update(['school_year_id' => null]);

        return redirect()->route('admin.microsoft-roster.mappings')->with('success', 'The local mapping was removed. No Microsoft or official AMIS record was changed.');
    }

    public function unmatched(Request $request): View
    {
        $accounts = $this->accountDirectoryQuery($request, true)->paginate(30)->withQueryString();

        return view('admin.microsoft-roster.unmatched', compact('accounts'));
    }

    public function reviewMatch(Request $request, MicrosoftTeamMembership $membership): View
    {
        $search = trim((string) $request->query('search'));
        $students = collect();
        $faculty = collect();

        if ($search !== '') {
            $like = '%'.$search.'%';
            $students = Student::query()
                ->with('user')
                ->where(function (Builder $query) use ($like) {
                    $query->where('student_number', 'like', $like)
                        ->orWhere('school_email', 'like', $like)
                        ->orWhere('ms_email', 'like', $like)
                        ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $like));
                })
                ->limit(20)
                ->get();

            $faculty = User::query()
                ->whereIn('role', ['teacher', 'admin', 'finance', 'staff'])
                ->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like))
                ->limit(20)
                ->get();
        }

        $membership->load('team');
        $teams = MicrosoftTeamMembership::query()
            ->with('team:id,display_name')
            ->where('identity_key', $membership->identity_key)
            ->get()
            ->pluck('team.display_name')
            ->filter()
            ->unique();

        return view('admin.microsoft-roster.match-review', compact('membership', 'teams', 'students', 'faculty', 'search'));
    }

    public function storeManualMatch(
        ManualMicrosoftAccountMatchRequest $request,
        MicrosoftTeamMembership $membership,
    ): RedirectResponse {
        $validated = $request->validated();
        $targetType = $validated['target_type'];
        $targetId = (int) $validated['target_id'];

        $target = $targetType === 'student'
            ? Student::query()->findOrFail($targetId)
            : User::query()->whereIn('role', ['teacher', 'admin', 'finance', 'staff'])->findOrFail($targetId);

        $identityMemberships = MicrosoftTeamMembership::query()->where('identity_key', $membership->identity_key);
        $conflict = (clone $identityMemberships)
            ->where('match_method', 'manual')
            ->where(function (Builder $query) use ($targetType, $targetId) {
                if ($targetType === 'student') {
                    $query->whereNull('local_student_id')->orWhere('local_student_id', '!=', $targetId);
                } else {
                    $query->whereNull('local_faculty_id')->orWhere('local_faculty_id', '!=', $targetId);
                }
            })
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'target_id' => 'This Microsoft account already has a different verified manual link. Remove that link explicitly before continuing.',
            ]);
        }

        $attributes = $targetType === 'student'
            ? [
                'local_student_id' => $target->id,
                'local_faculty_id' => null,
                'account_type' => 'student',
                'match_method' => 'manual',
                'match_status' => 'matched_student',
            ]
            : [
                'local_student_id' => null,
                'local_faculty_id' => $target->id,
                'account_type' => $target->role === 'teacher' ? 'faculty' : ($target->role === 'admin' ? 'admin' : 'staff'),
                'match_method' => 'manual',
                'match_status' => $target->role === 'teacher' ? 'matched_faculty' : 'matched_staff',
            ];

        $identityMemberships->update($attributes);
        AdminAuditLog::record('microsoft_account_manually_matched', true, "Manually matched Microsoft account {$membership->email}.", [
            'identity_key' => $membership->identity_key,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);

        return redirect()->route('admin.microsoft-roster.unmatched')->with('success', 'The Microsoft account was manually linked across its Team memberships.');
    }

    public function removeManualMatch(MicrosoftTeamMembership $membership, MicrosoftAccountMatcher $matcher): RedirectResponse
    {
        $memberships = MicrosoftTeamMembership::query()
            ->where('identity_key', $membership->identity_key)
            ->where('match_method', 'manual')
            ->get();

        foreach ($memberships as $linkedMembership) {
            $linkedMembership->fill($matcher->match($linkedMembership->raw_payload ?? []))->save();
        }

        AdminAuditLog::record('microsoft_account_match_removed', true, "Removed manual Microsoft account match for {$membership->email}.", [
            'identity_key' => $membership->identity_key,
        ]);

        return back()->with('success', 'The manual link was removed and exact automatic matching was re-evaluated.');
    }

    public function ignoreAccount(MicrosoftTeamMembership $membership): RedirectResponse
    {
        MicrosoftTeamMembership::query()->where('identity_key', $membership->identity_key)->update([
            'local_student_id' => null,
            'local_faculty_id' => null,
            'account_type' => 'unknown',
            'match_method' => 'ignored_by_admin',
            'match_status' => 'ignored',
        ]);

        return back()->with('success', 'The Microsoft account was ignored for matching review.');
    }

    public function syncAll(Request $request): RedirectResponse
    {
        $dispatchLock = Cache::lock('microsoft-teams-sync-dispatch', 10);
        if (! $dispatchLock->get()) {
            return back()->with('error', 'A Microsoft Teams synchronization is already in progress.');
        }

        $run = null;
        try {
            if (MicrosoftSyncRun::query()->whereIn('status', ['queued', 'running'])->exists()) {
                return back()->with('error', 'A Microsoft Teams synchronization is already in progress.');
            }

            $run = MicrosoftSyncRun::query()->create([
                'sync_type' => 'full',
                'status' => 'queued',
                'started_by' => $request->user()->id,
            ]);
            SyncMicrosoftTeamsRosterJob::dispatch($run->id);
        } finally {
            $dispatchLock->release();
        }

        return back()->with('success', "Microsoft Teams synchronization was queued (run #{$run->id}).");
    }

    public function syncTeam(Request $request, MicrosoftTeam $team): RedirectResponse
    {
        $dispatchLock = Cache::lock('microsoft-teams-sync-dispatch', 10);
        if (! $dispatchLock->get()) {
            return back()->with('error', 'A Microsoft Teams synchronization is already in progress.');
        }

        try {
            if (MicrosoftSyncRun::query()->whereIn('status', ['queued', 'running'])->exists()) {
                return back()->with('error', 'A Microsoft Teams synchronization is already in progress.');
            }

            $run = MicrosoftSyncRun::query()->create([
                'sync_type' => 'team',
                'status' => 'queued',
                'started_by' => $request->user()->id,
            ]);
            SyncMicrosoftTeamRosterJob::dispatch($run->id, $team->id);
        } finally {
            $dispatchLock->release();
        }

        return back()->with('success', "Synchronization for {$team->display_name} was queued.");
    }

    public function status(): JsonResponse
    {
        $run = MicrosoftSyncRun::query()->with('startedBy:id,name')->latest()->first();

        return response()->json(['run' => $run]);
    }

    public function history(): View
    {
        $runs = MicrosoftSyncRun::query()->with('startedBy')->latest()->paginate(40);

        return view('admin.microsoft-roster.history', compact('runs'));
    }

    public function historyShow(MicrosoftSyncRun $run): View
    {
        $run->load('startedBy');

        return view('admin.microsoft-roster.history-show', compact('run'));
    }

    public function raw(MicrosoftTeam $team): View
    {
        return view('admin.microsoft-roster.raw', [
            'team' => $team,
            'payload' => $this->normalizedTeamPayload($team),
        ]);
    }

    public function rawDownload(MicrosoftTeam $team): JsonResponse
    {
        return response()->json($this->normalizedTeamPayload($team), 200, [
            'Content-Disposition' => 'attachment; filename="microsoft-team-'.$team->id.'.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function exportTeams(string $format): StreamedResponse|JsonResponse
    {
        abort_unless(in_array($format, ['csv', 'json'], true), 404);
        $teams = MicrosoftTeam::query()->with(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section'])->orderBy('display_name')->get();
        $rows = $teams->map(fn (MicrosoftTeam $team) => $this->teamExportRow($team));

        if ($format === 'json') {
            return response()->json(['teams' => $rows], 200, [
                'Content-Disposition' => 'attachment; filename="microsoft-teams-summary.json"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->csv('microsoft-teams-summary.csv', array_keys($rows->first() ?? []), $rows->all());
    }

    public function exportRoster(MicrosoftTeam $team, string $format): StreamedResponse|JsonResponse
    {
        abort_unless(in_array($format, ['csv', 'json'], true), 404);
        $team->load(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section']);
        $members = $team->memberships()->with(['student.user', 'faculty'])->orderBy('display_name')->get();
        $rows = $members->map(fn (MicrosoftTeamMembership $member) => [
            'amis_id' => $member->student?->student_number,
            'display_name' => $member->display_name,
            'microsoft_email' => $member->email,
            'user_principal_name' => $member->user_principal_name,
            'account_type' => $member->account_type,
            'team_role' => $member->team_role,
            'match_status' => $member->match_status,
            'active' => $member->is_active,
            'first_seen_at' => $member->first_seen_at?->toIso8601String(),
            'last_seen_at' => $member->last_seen_at?->toIso8601String(),
        ]);

        if ($format === 'json') {
            return response()->json([
                'team' => $this->teamExportRow($team),
                'members' => $rows,
            ], 200, [
                'Content-Disposition' => 'attachment; filename="microsoft-team-roster-'.$team->id.'.json"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->csv('microsoft-team-roster-'.$team->id.'.csv', array_keys($rows->first() ?? []), $rows->all());
    }

    public function exportUnmatched(string $format): StreamedResponse|JsonResponse
    {
        abort_unless(in_array($format, ['csv', 'json'], true), 404);
        $rows = $this->accountDirectoryQuery(new Request, true)->get()->map(fn ($account) => [
            'display_name' => $account->display_name,
            'microsoft_email' => $account->email,
            'user_principal_name' => $account->user_principal_name,
            'teams_joined' => $account->teams_joined,
            'status' => $account->match_status,
            'last_seen_at' => $account->last_seen_at,
        ]);

        if ($format === 'json') {
            return response()->json(['accounts' => $rows], 200, [
                'Content-Disposition' => 'attachment; filename="unmatched-microsoft-accounts.json"',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $this->csv('unmatched-microsoft-accounts.csv', array_keys($rows->first() ?? []), $rows->all());
    }

    private function accountDirectoryQuery(Request $request, bool $unmatchedOnly = false)
    {
        $query = MicrosoftTeamMembership::query()
            ->selectRaw('MIN(id) as id, identity_key, MAX(display_name) as display_name, MAX(email) as email, MAX(user_principal_name) as user_principal_name, MAX(account_type) as account_type, MAX(match_status) as match_status, COUNT(DISTINCT microsoft_team_local_id) as teams_joined, MAX(last_seen_at) as last_seen_at')
            ->where('is_active', true)
            ->when($unmatchedOnly, fn (Builder $builder) => $builder->whereIn('match_status', ['unmatched', 'multiple_matches', 'manual_review']))
            ->when($request->filled('search'), function (Builder $builder) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';
                $builder->where(fn (Builder $inner) => $inner->where('display_name', 'like', $search)->orWhere('email', 'like', $search)->orWhere('user_principal_name', 'like', $search));
            })
            ->groupBy('identity_key')
            ->orderBy('display_name');

        return $query;
    }

    private function normalizedTeamPayload(MicrosoftTeam $team): array
    {
        $team->load(['mapping.schoolYear', 'mapping.gradeLevel', 'mapping.section', 'memberships.student']);

        return [
            'team' => [
                'microsoft_team_id' => $team->microsoft_team_id,
                'display_name' => $team->display_name,
                'description' => $team->description,
                'visibility' => $team->visibility,
                'category' => $team->team_category,
                'raw' => $this->redact($team->raw_payload ?? []),
            ],
            'mapping' => [
                'school_year' => $team->mapping?->schoolYear?->code,
                'grade_level' => $team->mapping?->gradeLevel?->name,
                'section' => $team->mapping?->section?->name,
                'gender_group' => $team->mapping?->gender_group,
                'shift' => $team->mapping?->shift,
                'program_type' => $team->mapping?->program_type,
                'status' => $team->mapping?->mapping_status,
                'suggestion' => $team->mapping?->detection_payload,
            ],
            'members' => $team->memberships->map(fn (MicrosoftTeamMembership $member) => [
                'entra_user_id' => $member->entra_user_id,
                'display_name' => $member->display_name,
                'email' => $member->email,
                'team_role' => $member->team_role,
                'account_type' => $member->account_type,
                'amis_id' => $member->student?->student_number,
                'match_status' => $member->match_status,
                'is_active' => $member->is_active,
                'raw' => $this->redact($member->raw_payload ?? []),
            ])->values(),
            'last_synced_at' => $team->last_synced_at?->toIso8601String(),
        ];
    }

    private function redact(array $payload): array
    {
        $sensitive = ['access_token', 'refresh_token', 'client_secret', 'authorization', 'password'];
        foreach ($payload as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $payload[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $payload[$key] = $this->redact($value);
            }
        }

        return $payload;
    }

    private function teamExportRow(MicrosoftTeam $team): array
    {
        return [
            'microsoft_team_id' => $team->microsoft_team_id,
            'team_name' => $team->display_name,
            'category' => $team->team_category,
            'visibility' => $team->visibility,
            'members' => $team->member_count,
            'owners' => $team->owner_count,
            'school_year' => $team->mapping?->schoolYear?->code,
            'grade_level' => $team->mapping?->gradeLevel?->name,
            'section' => $team->mapping?->section?->name,
            'mapping_status' => $team->mapping?->mapping_status,
            'last_synced_at' => $team->last_synced_at?->toIso8601String(),
        ];
    }

    private function csv(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            if ($headers !== []) {
                fputcsv($output, $headers);
            }
            foreach ($rows as $row) {
                fputcsv($output, array_values($row));
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
