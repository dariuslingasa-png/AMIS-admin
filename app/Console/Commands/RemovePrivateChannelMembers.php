<?php

namespace App\Console\Commands;

use App\Models\MsTeamChannel;
use App\Models\Section;
use App\Models\SectionSubject;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemovePrivateChannelMembers extends Command
{
    protected $signature = 'ms-teams:remove-private-channel-members 
                            {--dry-run : Print matches without actual deletion} 
                            {--team= : Process only a specific team ID} 
                            {--channel= : Process only a specific channel ID}
                            {--delete-channels : Also delete the private channels from MS Teams and clean database references}';

    protected $description = 'Remove student members (non-owners) from all private MS Teams channels and optionally delete the channels';

    public function handle(MicrosoftGraphService $graph): int
    {
        $dryRun = $this->option('dry-run');
        $teamIdOption = $this->option('team');
        $channelIdOption = $this->option('channel');
        $deleteChannels = $this->option('delete-channels');

        if ($dryRun) {
            $this->info('--- DRY RUN MODE: No deletions will be made ---');
            if ($deleteChannels) {
                $this->info('--- DRY RUN: Channel deletions will be simulated ---');
            }
        } else {
            $msg = $deleteChannels
                ? 'Are you sure you want to delete all student members AND permanently delete all private channels?'
                : 'Are you sure you want to delete all student members from private channels?';

            if (! $this->confirm($msg)) {
                $this->info('Operation cancelled.');

                return self::FAILURE;
            }
        }

        // Fetch sections that have a Team ID
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
        $channelsDeleted = 0;
        $channelsFailed = 0;

        foreach ($sections as $section) {
            $teamId = $section->ms_team_id;
            $teamName = $section->display_name;

            $this->info("Fetching channels for Team: [{$teamName}] ({$teamId})");

            try {
                $channels = $graph->listChannels($teamId);
            } catch (\Throwable $e) {
                $this->error('  ✗ Failed to fetch channels: '.$e->getMessage());
                Log::error("Failed to fetch channels for team {$teamId}: ".$e->getMessage());

                continue;
            }

            // Filter for private channels
            $privateChannels = collect($channels)->filter(function ($ch) use ($channelIdOption) {
                $isPrivate = ($ch['membershipType'] ?? '') === 'private';
                if ($channelIdOption) {
                    return $isPrivate && $ch['id'] === $channelIdOption;
                }

                return $isPrivate;
            });

            if ($privateChannels->isEmpty()) {
                $this->line('  No private channels found in this team.');

                continue;
            }

            foreach ($privateChannels as $ch) {
                $channelId = $ch['id'];
                $channelName = $ch['displayName'] ?? 'Unnamed Channel';

                $this->info("  Checking private channel: [{$channelName}] ({$channelId})");

                try {
                    $members = $graph->listChannelMembers($teamId, $channelId);
                } catch (\Throwable $e) {
                    $this->error('    ✗ Failed to list members: '.$e->getMessage());
                    Log::error("Failed to list members for channel {$channelId} in team {$teamId}: ".$e->getMessage());

                    continue;
                }

                foreach ($members as $member) {
                    $roles = $member['roles'] ?? [];
                    $displayName = $member['displayName'] ?? 'Unknown';
                    $email = $member['email'] ?? $member['userPrincipalName'] ?? 'No Email';
                    $membershipId = $member['id'] ?? null;

                    // Only remove members that are not owners
                    if (in_array('owner', $roles)) {
                        $this->line("    - Keep Owner: {$displayName} ({$email})");

                        continue;
                    }

                    $totalFound++;
                    if ($dryRun) {
                        $this->info("    [Dry Run] Would remove: {$displayName} ({$email})");
                    } else {
                        $this->info("    Removing member: {$displayName} ({$email})...");
                        try {
                            $graph->removeChannelMember($teamId, $channelId, $membershipId);
                            $totalRemoved++;
                            $this->info('      ✓ Successfully removed');
                        } catch (\Throwable $e) {
                            $totalFailed++;
                            $this->error('      ✗ Failed to remove: '.$e->getMessage());
                            Log::error("Failed to remove member {$membershipId} from channel {$channelId} in team {$teamId}: ".$e->getMessage());
                        }
                    }
                }

                // Delete channel if requested
                if ($deleteChannels) {
                    if ($dryRun) {
                        $this->info("    [Dry Run] Would delete private channel: [{$channelName}] ({$channelId})");
                    } else {
                        $this->info("    Deleting private channel: [{$channelName}] ({$channelId})...");
                        try {
                            $graph->deleteChannel($teamId, $channelId);
                            $channelsDeleted++;

                            // Clean DB references
                            SectionSubject::where('ms_channel_id', $channelId)->update(['ms_channel_id' => null]);
                            MsTeamChannel::where('ms_channel_id', $channelId)->delete();

                            $this->info('      ✓ Channel successfully deleted and database reference cleared');
                        } catch (\Throwable $e) {
                            $channelsFailed++;
                            $this->error('      ✗ Failed to delete channel: '.$e->getMessage());
                            Log::error("Failed to delete channel {$channelId} from team {$teamId}: ".$e->getMessage());
                        }
                    }
                }
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run completed. Found {$totalFound} members that would be removed.");
            if ($deleteChannels) {
                $this->info('Dry run: Would attempt to delete all matching private channels.');
            }
        } else {
            $this->info('Operation completed.');
            $this->info("Members: Removed {$totalRemoved} / {$totalFound} members. Failed: {$totalFailed}");
            if ($deleteChannels) {
                $this->info("Channels: Deleted {$channelsDeleted} channels. Failed: {$channelsFailed}");
            }
        }

        return self::SUCCESS;
    }
}
