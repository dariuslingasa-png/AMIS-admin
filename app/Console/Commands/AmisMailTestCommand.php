<?php

namespace App\Console\Commands;

use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;

class AmisMailTestCommand extends Command
{
    protected $signature = 'amis:mail-test';

    protected $description = 'Verify Microsoft Graph connection, tenant auth, user access, and mail access without exposing secrets.';

    public function handle(MicrosoftGraphService $graph): int
    {
        $this->info('========================================================================');
        $this->info(' 🔐 AMIS MICROSOFT GRAPH CONNECTION TEST');
        $this->info('========================================================================');

        $tenantId = config('services.microsoft.tenant_id');
        $clientId = config('services.microsoft.client_id');
        $clientSecret = config('services.microsoft.client_secret');

        if (empty($tenantId) || empty($clientId) || empty($clientSecret)) {
            $this->error('❌ Microsoft Graph credentials missing in environment variables.');
            $this->line('   Make sure MS_TENANT_ID, MS_CLIENT_ID, and MS_CLIENT_SECRET are set in .env');

            return 1;
        }

        // Test 1: App Token Authentication
        $this->line('1. Testing Microsoft Graph OAuth Token Acquisition...');
        try {
            $reflection = new \ReflectionClass($graph);
            $tokenMethod = $reflection->getMethod('getAccessToken');
            $tokenMethod->setAccessible(true);
            $token = $tokenMethod->invoke($graph);

            if ($token) {
                $this->info('   ✅ Microsoft Graph Authentication: OK');
            } else {
                $this->error('   ❌ Token acquisition returned empty string.');

                return 1;
            }
        } catch (\Throwable $e) {
            $this->error('   ❌ Authentication Failed: '.$e->getMessage());

            return 1;
        }

        // Test 2: User Access & Organization Connection
        $this->line('2. Testing Tenant Connection & User Organization Directory Access...');
        try {
            $reflection = new \ReflectionClass($graph);
            $graphMethod = $reflection->getMethod('graph');
            $graphMethod->setAccessible(true);
            $pendingReq = $graphMethod->invoke($graph);

            $userRes = $pendingReq->get('/users?$top=5&$select=id,userPrincipalName,displayName');

            if ($userRes->successful()) {
                $users = $userRes->json('value') ?? [];
                $count = count($users);
                $this->info('   ✅ Tenant: Connected');
                $this->info("   ✅ Users Accessible: YES ({$count} sample users retrieved)");
            } else {
                $this->error('   ❌ User Directory Access Failed (Status '.$userRes->status().')');
                $this->line('   Error: '.($userRes->json('error.message') ?? $userRes->body()));

                return 1;
            }
        } catch (\Throwable $e) {
            $this->error('   ❌ Tenant Directory Query Exception: '.$e->getMessage());

            return 1;
        }

        // Test 3: Mail Read Access Test
        $this->line('3. Testing Mail Read Access (Mail.ReadWrite permission check)...');
        $sampleUpn = config('services.microsoft.admin_upn', 'admin@amis.edu.ph');
        try {
            $mailRes = $pendingReq->get("/users/{$sampleUpn}/mailFolders/inbox/messages?\$top=1&\$select=id,subject");
            if ($mailRes->successful()) {
                $this->info("   ✅ Mail Access: YES (Inbox accessible for {$sampleUpn})");
            } else {
                $this->warn("   ⚠️ Mail Access check returned status {$mailRes->status()} for {$sampleUpn}");
                $this->line('   Details: '.($mailRes->json('error.message') ?? $mailRes->body()));
                $this->line('   Note: Ensure "Mail.ReadWrite" Application Permission has Admin Consent in Azure Portal.');
            }
        } catch (\Throwable $e) {
            $this->warn('   ⚠️ Mail Access Exception: '.$e->getMessage());
        }

        $this->info('========================================================================');
        $this->info(' 🎉 MICROSOFT GRAPH CONNECTION VERIFICATION COMPLETE');
        $this->info('========================================================================');

        return 0;
    }
}
