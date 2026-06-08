<?php

namespace App\Services;

use App\Services\MicrosoftGraph\HandlesGraphAuthentication;
use App\Services\MicrosoftGraph\ManagesGraphMembership;
use App\Services\MicrosoftGraph\ManagesGraphTeams;
use App\Services\MicrosoftGraph\ManagesGraphUsers;

class MicrosoftGraphService
{
    use HandlesGraphAuthentication;
    use ManagesGraphMembership;
    use ManagesGraphTeams;
    use ManagesGraphUsers;

    private string $tenantId;

    private string $clientId;

    private string $clientSecret;

    private ?string $accessToken = null;

    private ?string $delegatedToken = null;

    public function __construct()
    {
        $this->tenantId = (string) config('services.microsoft.tenant_id');
        $this->clientId = (string) config('services.microsoft.client_id');
        $this->clientSecret = (string) config('services.microsoft.client_secret');
    }
}
