<?php

namespace App\Services;

use App\Models\MsTeam;
use App\Models\MsTeamChannel;
use Illuminate\Support\Facades\Log;

class MsTeamsProvisioningService
{
    const GRADE_LEVELS = [
        'Kinder 1', 'Kinder 2',
        'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
        'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10',
        'Grade 11', 'Grade 12',
    ];

    // 6 private channels per grade team
    // display_name, gender_filter, learning_mode_filter
    const CHANNELS = [
        ['Face to Face Boys',    'male',   'Face-to-Face'],
        ['Face to Face Girls',   'female', 'Face-to-Face'],
        ['Flexible 1st Boys',    'male',   'Flexible Online Learning - 1st Shift'],
        ['Flexible 1st Girls',   'female', 'Flexible Online Learning - 1st Shift'],
        ['Flexible 2nd Boys',    'male',   'Flexible Online Learning - 2nd Shift'],
        ['Flexible 2nd Girls',   'female', 'Flexible Online Learning - 2nd Shift'],
    ];

    public function __construct(private MicrosoftGraphService $graph) {}

    /**
     * Get grades that don't have a team yet for this school year.
     */
    public static function getMissingGrades(string $schoolYear): array
    {
        $existing = MsTeam::where('type', 'grade')
            ->where('school_year', $schoolYear)
            ->pluck('grade_level')
            ->toArray();

        return array_values(array_diff(self::GRADE_LEVELS, $existing));
    }

    /**
     * Provision ONE grade team with all 6 private channels + General.
     * Called one at a time via AJAX.
     */
    public function provisionGrade(string $grade, string $schoolYear): MsTeam
    {
        $adminUpn = config('services.microsoft.admin_upn');
        $teamName = "{$grade} — {$schoolYear}";

        // 1. Create the Team
        $msTeamData = $this->graph->createTeam($teamName, "AMIS {$grade} team for {$schoolYear}");
        $msTeamId = $this->waitForTeam($msTeamData['id']);

        // 2. Save to DB
        $team = MsTeam::create([
            'ms_team_id' => $msTeamId,
            'display_name' => $teamName,
            'type' => 'grade',
            'shift' => null,
            'grade_level' => $grade,
            'subject_id' => null,
            'school_year' => $schoolYear,
            'team_url' => "https://teams.microsoft.com/l/team/{$msTeamId}",
        ]);

        // 3. Create all 6 private channels
        foreach (self::CHANNELS as [$channelName, $genderFilter, $learningModeFilter]) {
            $this->createChannel($team, $msTeamId, $channelName, $genderFilter, $learningModeFilter, $adminUpn);
            sleep(1); // small delay between channel creations
        }

        Log::info("Provisioned grade team: {$teamName} [{$msTeamId}] with ".count(self::CHANNELS).' channels');

        return $team->load('channels');
    }

    private function createChannel(
        MsTeam $team,
        string $msTeamId,
        string $channelName,
        string $genderFilter,
        string $learningModeFilter,
        string $adminUpn
    ): void {
        $attempts = 0;
        while ($attempts < 3) {
            try {
                $channelData = $this->graph->createPrivateChannel($msTeamId, $channelName, $adminUpn);

                MsTeamChannel::create([
                    'ms_team_id_fk' => $team->id,
                    'ms_channel_id' => $channelData['id'],
                    'display_name' => $channelName,
                    'gender_filter' => $genderFilter,
                    'is_private' => true,
                    'learning_mode_filter' => $learningModeFilter,
                ]);

                Log::info("Created channel [{$channelName}] in {$team->display_name}");

                return;

            } catch (\Exception $e) {
                $attempts++;
                if (str_contains($e->getMessage(), '429') && $attempts < 3) {
                    Log::warning("429 on channel [{$channelName}], retrying in 10s...");
                    sleep(10);
                } else {
                    Log::error("Failed to create channel [{$channelName}] in {$team->display_name}: ".$e->getMessage());

                    return;
                }
            }
        }
    }

    private function waitForTeam(string $teamId, int $maxAttempts = 10): string
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(3);
            try {
                $team = $this->graph->getTeam($teamId);
                if (! empty($team['id'])) {
                    return $team['id'];
                }
            } catch (\Exception) {
                // Not ready yet
            }
        }

        return $teamId;
    }
}
