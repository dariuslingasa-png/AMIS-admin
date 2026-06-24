<?php

namespace App\Console\Commands;

use App\Models\Section;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveTeamMembers extends Command
{
    protected $signature = 'ms-teams:remove-team-members
                            {--dry-run : Print matches without actual deletion}
                            {--team= : Process only a specific team ID}
                            {--delete-teams : Also delete the Teams from Microsoft 365 and clear local section references}';

    protected $description = 'Remove non-owner members from MS Teams and optionally delete the Teams';

    public function handle(MicrosoftGraphService $graph): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $teamIdOption = $this->option('team');
        $deleteTeams = (bool) $this->option('delete-teams');

        if ($dryRun) {
            $this->info('--- DRY RUN MODE: No deletions will be made ---');
        } else {
            $message = $deleteTeams
                ? 'Remove non-owner members AND permanently delete matching Teams?'
                : 'Remove all non-owner members from matching Teams?';

            if (! $this->confirm($message)) {
                $this->info('Operation cancelled.');

                return self::FAILURE;
            }
        }

        $sectionsQuery = Section::whereNotNull('ms_team_id');
        if ($teamIdOption) {
            $sectionsQuery->where('ms_team_id', $teamIdOption);
        }

        $sections = $sectionsQuery->get();
        if ($sections->isEmpty()) {
            $this->warn('No sections found with MS Team ID matching criteria.');

            return self::SUCCESS;
        }

        $this->info('Checking '.$sections->count().' team(s)...');

        $totalFound = 0;
        $totalRemoved = 0;
        $totalFailed = 0;
        $teamsDeleted = 0;
        $teamsFailed = 0;

        foreach ($sections as $section) {
            $teamId = $section->ms_team_id;
            $teamName = $section->display_name;

            $this->info("Fetching members for Team: [{$teamName}] ({$teamId})");

            try {
                $members = $graph->listTeamMembers($teamId);
            } catch (\Throwable $e) {
                $this->error('  Failed to list members: '.$e->getMessage());
                Log::error("Failed to list team members for {$teamId}: ".$e->getMessage());
                continue;
            }

            foreach ($members as $member) {
                $roles = $member['roles'] ?? [];
                $displayName = $member['displayName'] ?? 'Unknown';
                $email = $member['email'] ?? $member['userPrincipalName'] ?? 'No Email';
                $membershipId = $member['id'] ?? null;

                if (in_array('owner', $roles, true)) {
                    $this->line("  - Keep Owner: {$displayName} ({$email})");
                    continue;
                }

                if (! $membershipId) {
                    $totalFailed++;
                    $this->error("  Missing membership ID for {$displayName} ({$email})");
                    continue;
                }

                $totalFound++;
                if ($dryRun) {
                    $this->info("  [Dry Run] Would remove: {$displayName} ({$email})");
                    continue;
                }

                $this->info("  Removing member: {$displayName} ({$email})...");
                try {
                    $graph->removeTeamMember($teamId, $membershipId);
                    $totalRemoved++;
                    $this->info('    Successfully removed');
                } catch (\Throwable $e) {
                    $totalFailed++;
                    $this->error('    Failed to remove: '.$e->getMessage());
                    Log::error("Failed to remove team member {$membershipId} from {$teamId}: ".$e->getMessage());
                }
            }

            if (! $deleteTeams) {
                continue;
            }

            if ($dryRun) {
                $this->info("  [Dry Run] Would delete Team: [{$teamName}] ({$teamId})");
                continue;
            }

            $this->info("  Deleting Team: [{$teamName}] ({$teamId})...");
            try {
                $graph->deleteTeam($teamId);
                $section->subjects()->update(['ms_channel_id' => null]);
                $section->update(['ms_team_id' => null]);
                $teamsDeleted++;
                $this->info('    Team deleted and local section reference cleared');
            } catch (\Throwable $e) {
                $teamsFailed++;
                $this->error('    Failed to delete Team: '.$e->getMessage());
                Log::error("Failed to delete team {$teamId}: ".$e->getMessage());
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run completed. Found {$totalFound} non-owner members that would be removed.");
        } else {
            $this->info('Operation completed.');
            $this->info("Members: Removed {$totalRemoved} / {$totalFound}. Failed: {$totalFailed}");
            if ($deleteTeams) {
                $this->info("Teams: Deleted {$teamsDeleted}. Failed: {$teamsFailed}");
            }
        }

        return self::SUCCESS;
    }
}
