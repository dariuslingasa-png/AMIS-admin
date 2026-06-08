<?php

namespace App\Http\Controllers\Admin\MsTeams;

use App\Models\Section;
use App\Models\StudentSection;
use App\Services\MicrosoftGraphService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait ManagesMsTeamSections
{
    public function index(Request $request)
    {
        $sections = Section::withCount(['students as enrolled_count' => fn ($q) => $q->where('ms_status', 'enrolled')])
            ->withCount('subjects')
            ->orderBy('grade_level')
            ->orderBy('learning_mode')
            ->orderBy('shift')
            ->orderBy('gender')
            ->get();

        $stats = [
            'total_sections' => $sections->count(),
            'with_team' => $sections->whereNotNull('ms_team_id')->count(),
            'without_team' => $sections->whereNull('ms_team_id')->count(),
            'total_enrolled' => StudentSection::where('ms_status', 'enrolled')->count(),
            'total_failed' => StudentSection::where('ms_status', 'failed')->count(),
        ];

        return view('admin.ms-teams.index', compact('sections', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'grade_level' => 'required|string',
            'learning_mode' => 'required|string',
            'name' => 'nullable|string|max:255',
            'school_year' => 'required|string',
            'shifts' => 'nullable|array',
            'shifts.*' => 'string',
            'genders' => 'nullable|array',
            'genders.*' => 'in:male,female',
            'gender_single' => 'nullable|in:male,female',
        ]);

        $isFlexible = $request->learning_mode === 'Flexible Online Learning';
        $sectionName = $request->name ?: null;
        $graph = new MicrosoftGraphService;
        $created = 0;

        if ($isFlexible) {
            $shifts = $request->input('shifts', []);
            $genders = $request->input('genders', []);

            if (empty($shifts) || empty($genders)) {
                return back()->withErrors(['ms' => 'Select at least one shift and one gender.'])->withInput();
            }

            foreach ($shifts as $shift) {
                foreach ($genders as $gender) {
                    $this->createSectionWithTeam($request, $graph, $sectionName, $shift, $gender);
                    $created++;
                }
            }
        } else {
            $this->createSectionWithTeam(
                $request,
                $graph,
                $sectionName,
                null,
                $request->gender_single ?? 'male',
            );
            $created = 1;
        }

        return redirect()->route('admin.ms-teams.index')
            ->with('success', "{$created} section(s) created for {$request->grade_level}.");
    }

    public function storeSingle(Request $request)
    {
        $request->validate([
            'grade_level' => 'required|string',
            'learning_mode' => 'required|string',
            'shift' => 'nullable|string',
            'gender' => 'required|in:male,female',
            'name' => 'nullable|string|max:255',
        ]);

        $sectionName = $request->name ?: null;
        $shift = $request->learning_mode === 'Flexible Online Learning' ? $request->shift : null;
        $teamName = $this->teamName($request->grade_level, $sectionName, $shift, $request->gender);
        $msTeamId = null;
        $msTeamUrl = null;

        try {
            $graph = new MicrosoftGraphService;
            $result = $graph->createTeam($teamName);
            $msTeamId = $result['id'];
            $msTeamUrl = "https://teams.microsoft.com/l/team/{$msTeamId}";
            $graph->waitForTeam($msTeamId);
            $generalChannelId = $graph->getGeneralChannelId($msTeamId);

            if ($generalChannelId) {
                $graph->postWelcomeCard($msTeamId, $generalChannelId, [
                    'grade_level' => $request->grade_level,
                    'learning_mode' => $request->learning_mode,
                    'shift' => $shift,
                    'gender' => $request->gender,
                ]);
            }
        } catch (Exception $exception) {
            Log::error("storeSingle: Failed to create MS Team [{$teamName}]: ".$exception->getMessage());
        }

        Section::create([
            'name' => $sectionName,
            'grade_level' => $request->grade_level,
            'learning_mode' => $request->learning_mode,
            'shift' => $shift,
            'gender' => $request->gender,
            'ms_team_id' => $msTeamId,
            'ms_team_url' => $msTeamUrl,
        ]);

        return response()->json([
            'success' => true,
            'team_name' => $teamName,
            'has_team' => ! is_null($msTeamId),
        ]);
    }

    public function retryTeam(Section $section)
    {
        $teamName = $this->teamName($section->grade_level, $section->name, $section->shift, $section->gender);

        try {
            $graph = new MicrosoftGraphService;
            $result = $graph->createTeam($teamName);
            $msTeamId = $result['id'];
            $section->update([
                'ms_team_id' => $msTeamId,
                'ms_team_url' => "https://teams.microsoft.com/l/team/{$msTeamId}",
            ]);

            return back()->with('success', "MS Team created: {$teamName}");
        } catch (Exception $exception) {
            Log::error("retryTeam failed [{$teamName}]: ".$exception->getMessage());

            return back()->withErrors(['ms' => 'Failed: '.$exception->getMessage()]);
        }
    }

    public function show(Section $section)
    {
        $section->load('subjects');
        $enrollments = StudentSection::where('section_id', $section->id)
            ->with('student.applicant')
            ->latest()
            ->get();

        return view('admin.ms-teams.show', compact('section', 'enrollments'));
    }

    public function update(Request $request, Section $section)
    {
        $request->validate(['name' => 'nullable|string|max:255']);

        $sectionName = $request->name ?: null;
        $newTeamName = $this->teamName($section->grade_level, $sectionName, $section->shift, $section->gender);

        if ($section->ms_team_id) {
            try {
                (new MicrosoftGraphService)->renameTeam($section->ms_team_id, $newTeamName);
            } catch (Exception $exception) {
                Log::warning("Could not rename MS Team [{$section->ms_team_id}]: ".$exception->getMessage());
            }
        }

        $section->update(['name' => $sectionName]);

        return response()->json(['success' => true]);
    }

    public function destroy(Section $section)
    {
        $msTeamId = $section->ms_team_id;
        $section->subjects()->delete();
        $section->delete();

        if ($msTeamId) {
            try {
                (new MicrosoftGraphService)->deleteTeam($msTeamId);
            } catch (Exception $exception) {
                Log::warning("Could not delete MS Team [{$msTeamId}] from Azure: ".$exception->getMessage());
            }
        }

        return redirect()->route('admin.ms-teams.index')
            ->with('success', "Section \"{$section->grade_level}\" deleted.".($msTeamId ? ' MS Team also removed from Azure.' : ''));
    }

    private function createSectionWithTeam(
        Request $request,
        MicrosoftGraphService $graph,
        ?string $sectionName,
        ?string $shift,
        string $gender
    ): void {
        $genderLabel = $gender === 'male' ? 'Boys' : 'Girls';
        $teamName = $request->grade_level
            .($sectionName ? " - {$sectionName}" : '')
            .' '.($shift ?: 'F2F')." {$genderLabel} {$request->school_year}";
        $msTeamId = null;
        $msTeamUrl = null;

        try {
            $result = $graph->createTeam($teamName);
            $msTeamId = $graph->waitForTeam($result['id']);
            $msTeamUrl = "https://teams.microsoft.com/l/team/{$msTeamId}";
        } catch (Exception $exception) {
            Log::error("Failed to create MS Team [{$teamName}]: ".$exception->getMessage());
        }

        Section::create([
            'name' => $sectionName,
            'grade_level' => $request->grade_level,
            'learning_mode' => $request->learning_mode,
            'shift' => $shift,
            'gender' => $gender,
            'school_year' => $request->school_year,
            'ms_team_id' => $msTeamId,
            'ms_team_url' => $msTeamUrl,
        ]);
    }

    private function teamName(string $grade, ?string $sectionName, ?string $shift, string $gender): string
    {
        $prefix = match ($grade) {
            'Kinder 1' => 'K1',
            'Kinder 2' => 'K2',
            default => 'G'.str_replace('Grade ', '', $grade),
        };

        $genderLabel = $gender === 'male' ? 'Boys' : 'Girls';
        $shiftLabel = $shift ? ($shift === '1st Shift' ? '1st Shift' : '2nd Shift') : 'F2F';
        $namePart = $sectionName ? " - {$sectionName}" : '';

        return "{$prefix}{$namePart} [{$genderLabel} & {$shiftLabel}]";
    }
}
