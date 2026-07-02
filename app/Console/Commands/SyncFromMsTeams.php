<?php

namespace App\Console\Commands;

use App\Models\Section;
use App\Models\SectionSubject;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncFromMsTeams extends Command
{
    protected $signature = 'ms-teams:sync-db-from-teams {--apply : Apply the changes to the database}';
    protected $description = 'Safely sync MS Team and channel IDs from Microsoft Teams to the DB sections and subjects (READ-ONLY to MS Teams)';

    public function handle(): int
    {
        $apply = $this->option('apply');
        $this->info($apply ? 'RUNNING IN APPLY MODE - Writing changes to database...' : 'RUNNING IN DRY-RUN MODE (Default) - No database writes.');
        $this->newLine();

        // 1. Fetch all groups from Microsoft Graph
        $this->info('Fetching all groups from Microsoft Teams...');
        try {
            $graph = new MicrosoftGraphService();
            $teams = $this->fetchTeamsFromGraph();
        } catch (\Exception $e) {
            $this->error('Failed to connect to Microsoft Graph: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Found ' . count($teams) . ' teams in Microsoft Teams.');
        $this->newLine();

        // 2. Load all sections from the database
        // Sort sections so that sections with concrete names are processed before those with placeholder/dummy 'A' or empty names
        $sections = Section::all()->sortBy(function ($sec) {
            return ($sec->name === 'A' || empty($sec->name)) ? 1 : 0;
        });
        $this->info('Loaded ' . $sections->count() . ' sections from the database.');
        $this->newLine();

        $matchedCount = 0;
        $unmatchedSections = [];
        $duplicateMatches = [];

        // Track updates to perform
        $sectionUpdates = [];
        $subjectUpdates = [];
        $assignedTeamIds = []; // Track assigned Team IDs to prevent duplicates

        // Match Teams to Sections
        foreach ($sections as $section) {
            $matchingTeams = [];

            foreach ($teams as $team) {
                if (in_array($team['id'], $assignedTeamIds)) {
                    continue;
                }
                if ($this->isMatch($section, $team)) {
                    $matchingTeams[] = $team;
                }
            }

            if (empty($matchingTeams)) {
                $unmatchedSections[] = $section;
                continue;
            }

            // Resolve duplicate matches (e.g. spelling variations)
            $bestTeam = $this->resolveBestTeam($section, $matchingTeams);
            if (count($matchingTeams) > 1) {
                $duplicateMatches[] = [
                    'section' => $section,
                    'teams' => $matchingTeams,
                    'selected' => $bestTeam,
                ];
            }

            $matchedCount++;
            $assignedTeamIds[] = $bestTeam['id']; // Track assigned Team ID

            // Record section update
            $sectionUpdates[] = [
                'section' => $section,
                'team_id' => $bestTeam['id'],
                'team_url' => "https://teams.microsoft.com/l/team/{$bestTeam['id']}",
                'team_name' => $bestTeam['displayName'],
            ];

            // Match subjects to channels
            $shiftLabel = $section->shift ?? 'F2F';
            $this->info("Section: [ID {$section->id}] {$section->grade_level} - {$section->name} ({$shiftLabel}, {$section->gender})");
            $this->line("  -> Matched Team: \"{$bestTeam['displayName']}\" (ID: {$bestTeam['id']})");

            try {
                $channels = $graph->listChannels($bestTeam['id']);
                $subjects = SectionSubject::where('section_id', $section->id)->get();

                foreach ($subjects as $subject) {
                    $matchedChannel = null;
                    foreach ($channels as $channel) {
                        if ($this->subjectsMatch($subject->subject_name, $channel['displayName'])) {
                            $matchedChannel = $channel;
                            break;
                        }
                    }

                    if ($matchedChannel) {
                        $this->line("     ✔ Subject: \"{$subject->subject_name}\" -> Channel: \"{$matchedChannel['displayName']}\" (ID: {$matchedChannel['id']})");
                        $subjectUpdates[] = [
                            'subject' => $subject,
                            'channel_id' => $matchedChannel['id'],
                        ];
                    } else {
                        $this->warn("     ✗ Subject: \"{$subject->subject_name}\" -> No matching channel found!");
                    }
                }
            } catch (\Exception $e) {
                $this->error("     Error fetching channels for Team ID {$bestTeam['id']}: " . $e->getMessage());
            }
            $this->newLine();
        }

        // Summary Report
        $this->info('--- MATCHING SUMMARY ---');
        $this->info("Matched Sections: {$matchedCount} / " . $sections->count());
        $this->warn("Unmatched Sections: " . count($unmatchedSections));
        if (count($unmatchedSections) > 0) {
            foreach ($unmatchedSections as $us) {
                $usShift = $us->shift ?? 'F2F';
                $this->line("  - [ID {$us->id}] {$us->grade_level} - {$us->name} ({$usShift}, {$us->gender})");
            }
        }

        if (count($duplicateMatches) > 0) {
            $this->newLine();
            $this->warn('--- DUPLICATE MATCHES RESOLVED ---');
            foreach ($duplicateMatches as $dm) {
                $section = $dm['section'];
                $sectionShift = $section->shift ?? 'F2F';
                $this->line("Section: [ID {$section->id}] {$section->grade_level} - {$section->name} ({$sectionShift}, {$section->gender})");
                foreach ($dm['teams'] as $team) {
                    $marker = ($team['id'] === $dm['selected']['id']) ? '  ★ Selected ->' : '  ⚠️ Skipped  ->';
                    $this->line("{$marker} \"{$team['displayName']}\" (ID: {$team['id']})");
                }
            }
        }

        // Apply changes
        if ($apply) {
            $this->newLine();
            $this->info('Applying updates to database...');

            $updatedSections = 0;
            foreach ($sectionUpdates as $update) {
                $cleanedName = $this->cleanTeamName($update['team_name']);
                
                // Safety check: check if another section already holds this ms_team_id to prevent constraint violations
                $existingConflict = Section::where('ms_team_id', $update['team_id'])
                    ->where('id', '!=', $update['section']->id)
                    ->first();

                if ($existingConflict) {
                    $this->warn("Skipping Section [ID {$update['section']->id}] -> matched Team ID {$update['team_id']} is already held by Section [ID {$existingConflict->id} ({$existingConflict->grade_level} - {$existingConflict->name})]");
                    continue;
                }

                try {
                    $update['section']->update([
                        'ms_team_id' => $update['team_id'],
                        'ms_team_url' => $update['team_url'],
                        'name' => ($cleanedName !== '') ? $cleanedName : $update['section']->name,
                    ]);
                    $updatedSections++;
                } catch (\Exception $e) {
                    $this->error("Failed to update Section [ID {$update['section']->id}]: " . $e->getMessage());
                }
            }

            $updatedSubjects = 0;
            foreach ($subjectUpdates as $update) {
                $update['subject']->update([
                    'ms_channel_id' => $update['channel_id'],
                ]);
                $updatedSubjects++;
            }

            $this->info("Successfully updated {$updatedSections} sections and {$updatedSubjects} subjects in the database!");
        } else {
            $this->newLine();
            $this->warn('Dry-run complete. No changes were saved. Run with --apply option to write changes.');
        }

        return Command::SUCCESS;
    }

    private function fetchTeamsFromGraph(): array
    {
        $response = Http::asForm()->post(
            "https://login.microsoftonline.com/" . config('services.microsoft.tenant_id') . "/oauth2/v2.0/token",
            [
                'grant_type' => 'client_credentials',
                'client_id' => config('services.microsoft.client_id'),
                'client_secret' => config('services.microsoft.client_secret'),
                'scope' => 'https://graph.microsoft.com/.default',
            ]
        );

        if (!$response->successful()) {
            throw new \Exception('Failed to obtain access token: ' . $response->body());
        }

        $token = $response->json('access_token');
        $groups = [];
        $url = '/groups?$select=id,displayName,resourceProvisioningOptions&$top=999';

        while ($url) {
            $res = Http::withToken($token)
                ->baseUrl('https://graph.microsoft.com/v1.0')
                ->timeout(60)
                ->get($url);

            if (!$res->successful()) {
                throw new \Exception('Failed to list groups from Graph: ' . $res->body());
            }

            $data = $res->json();
            $groups = array_merge($groups, $data['value'] ?? []);
            $nextLink = $data['@odata.nextLink'] ?? null;
            $url = $nextLink ? str_replace('https://graph.microsoft.com/v1.0', '', $nextLink) : null;
        }

        // Filter groups that are Teams
        $teams = [];
        foreach ($groups as $g) {
            $opts = $g['resourceProvisioningOptions'] ?? [];
            if (in_array('Team', $opts)) {
                $teams[] = $g;
            }
        }

        return $teams;
    }

    private function isMatch(Section $section, array $team): bool
    {
        $teamName = $team['displayName'];

        // 1. Grade level check
        $parsedGrade = $this->parseGradeLevel($teamName);
        if (!$parsedGrade || strcasecmp($parsedGrade, $section->grade_level) !== 0) {
            return false;
        }

        // 2. Shift check
        $parsedShift = $this->parseShift($teamName);
        if ($parsedShift !== $section->shift) {
            return false;
        }

        // 3. Gender check (if specified in team name)
        $parsedGender = $this->parseGender($teamName);
        if ($parsedGender !== null) {
            $dbGender   = $section->gender;
            $normParsed = in_array($parsedGender, ['male', 'female'], true) ? $parsedGender : 'merge';
            $normDb     = in_array($dbGender, ['male', 'female'], true) ? $dbGender : 'merge';
            if ($normParsed !== $normDb) {
                return false;
            }
        }

        // 4. Companion Name check
        $cleanedTeam = $this->cleanTeamName($teamName);
        $cleanedSection = $section->name;

        // If DB name is "A" (local dummy data) but MS Teams has name, let's match based on grade/shift/gender only
        if ($cleanedSection === 'A' || empty($cleanedSection)) {
            return true;
        }

        return $this->namesMatch($cleanedTeam, $cleanedSection);
    }

    private function resolveBestTeam(Section $section, array $teams): array
    {
        if (count($teams) === 1) {
            return $teams[0];
        }

        $sectionName = $section->name ?? '';

        // Prefer exact matches on name
        foreach ($teams as $team) {
            $cleanedTeam = $this->normalizeName($this->cleanTeamName($team['displayName']));
            $cleanedSection = $this->normalizeName($sectionName);
            if ($cleanedTeam === $cleanedSection) {
                return $team;
            }
        }

        // Prefer team names containing hyphens if section name has hyphens
        if (str_contains($sectionName, '-')) {
            foreach ($teams as $team) {
                if (str_contains($team['displayName'], '-')) {
                    return $team;
                }
            }
        }

        // Fallback to first
        return $teams[0];
    }

    private function parseGradeLevel(string $name): ?string
    {
        if (preg_match('/\b(K1|Kinder 1|Kindergarten 1)\b/i', $name)) {
            return 'Kinder 1';
        }
        if (preg_match('/\b(K2|Kinder 2|Kindergarten 2)\b/i', $name)) {
            return 'Kinder 2';
        }
        for ($i = 1; $i <= 12; $i++) {
            if (preg_match('/\b(G' . $i . '|Grade ' . $i . ')\b/i', $name)) {
                return 'Grade ' . $i;
            }
        }
        return null;
    }

    private function parseShift(string $name): ?string
    {
        if (preg_match('/1st\s*Shift/i', $name)) {
            return '1st Shift';
        }
        if (preg_match('/2nd\s*Shift/i', $name)) {
            return '2nd Shift';
        }
        return null;
    }

    private function parseGender(string $name): ?string
    {
        if (preg_match('/\b(boys|boy)\b/i', $name)) {
            return 'male';
        }
        if (preg_match('/\b(girls|girl)\b/i', $name)) {
            return 'female';
        }
        if (preg_match('/\b(na|mixed|mix|co-ed|coed|merge|merged)\b/i', $name)) {
            return 'merge';
        }
        return null;
    }

    private function cleanTeamName(string $name): string
    {
        // Strip grade prefix (e.g. G5 -, Grade 5 -, K2 -)
        $cleaned = preg_replace('/^(G\d+|K\d+|Grade\s*\d+|Kinder\s*\d+|Kindergarten\s*\d+)\b/i', '', $name);
        
        // Strip shift suffix (e.g. - 1st SHIFT, - 2nd SHIFT)
        $cleaned = preg_replace('/-\s*\d+(st|nd|rd|th)\s*shift\b/i', '', $cleaned);
        
        // Strip gender/brackets (e.g. (BOYS), (GIRLS), (MIX))
        $cleaned = preg_replace('/\((boys|girls|mix)\)/i', '', $cleaned);
        
        // Strip info brackets (e.g. [Boys & 1st Shift])
        $cleaned = preg_replace('/\[.*\]/i', '', $cleaned);
        
        // Strip leading/trailing hyphens and spaces
        $cleaned = trim($cleaned, " \t\n\r\0\x0B-");
        
        return $cleaned;
    }

    private function normalizeName(?string $name): string
    {
        $name = $name ?? '';
        $cleaned = strtolower($name);
        $cleaned = str_replace('-', ' ', $cleaned);
        $cleaned = preg_replace('/[^a-z0-9\s]/', '', $cleaned);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned);
        return trim($cleaned);
    }

    private function namesMatch(string $name1, string $name2): bool
    {
        $n1 = $this->normalizeName($name1);
        $n2 = $this->normalizeName($name2);
        
        if ($n1 === $n2) {
            return true;
        }
        
        if (str_contains($n1, $n2) || str_contains($n2, $n1)) {
            return true;
        }
        
        $dist = levenshtein($n1, $n2);
        $maxLength = max(strlen($n1), strlen($n2));
        if ($maxLength > 0 && ($dist <= 2 || ($dist / $maxLength) <= 0.15)) {
            return true;
        }
        
        return false;
    }

    private function normalizeSubjectName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = str_replace(["'", '-', ' '], '', $name);
        
        $mappings = [
            'ap' => 'aralingpanlipunan',
            'eng' => 'english',
            'math' => 'mathematics',
            'sci' => 'science',
            'esp' => 'gmrc',
            'seerah' => 'seerahandhadith',
        ];
        
        if (isset($mappings[$name])) {
            return $mappings[$name];
        }
        
        if (str_starts_with($name, 'tle')) {
            return 'tle';
        }
        
        return $name;
    }

    private function subjectsMatch(string $subj1, string $subj2): bool
    {
        $s1 = $this->normalizeSubjectName($subj1);
        $s2 = $this->normalizeSubjectName($subj2);
        return $s1 === $s2 || str_contains($s1, $s2) || str_contains($s2, $s1);
    }
}
