<?php

namespace App\Services\Microsoft;

use App\Services\MicrosoftGraphService;
use Illuminate\Support\Facades\Log;

class MicrosoftUserService
{
    /**
     * Retrieve all @amis.edu.ph users with pagination support (@odata.nextLink).
     *
     * @param string|null $filterType 'faculty', 'students', or null for all
     * @return array List of user array objects: ['id', 'userPrincipalName', 'displayName', 'mail', 'jobTitle']
     */
    public function getAmisUsers(?string $filterType = null, int $pageSize = 100): array
    {
        $users = [];
        $endpoint = '/users?$top=' . min($pageSize, 999) . '&$select=id,userPrincipalName,displayName,mail,jobTitle,userType';

        do {
            try {
                $response = $this->graph()->get($endpoint);
            } catch (\Throwable $e) {
                Log::error('MicrosoftUserService::getAmisUsers request failed: ' . $e->getMessage());
                break;
            }

            if (!$response->successful()) {
                Log::error('MicrosoftUserService::getAmisUsers returned error status ' . $response->status(), [
                    'body' => $response->body(),
                ]);
                break;
            }

            $data = $response->json();
            $items = $data['value'] ?? [];

            foreach ($items as $item) {
                $upn = strtolower(trim($item['userPrincipalName'] ?? ($item['mail'] ?? '')));
                
                // Skip non-amis or external guest accounts
                if (!str_ends_with($upn, '@amis.edu.ph') || str_contains($upn, '#ext#')) {
                    continue;
                }

                $usernamePart = explode('@', $upn)[0];
                $isStudent = (bool) preg_match('/^\d+/', $usernamePart);

                // Optional role filtering based on UPN structure
                if ($filterType === 'faculty' && $isStudent) {
                    continue;
                } elseif ($filterType === 'students' && !$isStudent) {
                    continue;
                }

                $users[] = [
                    'id' => $item['id'],
                    'userPrincipalName' => $item['userPrincipalName'],
                    'displayName' => $item['displayName'] ?? '',
                    'mail' => $item['mail'] ?? $item['userPrincipalName'],
                    'jobTitle' => $item['jobTitle'] ?? '',
                    'is_student' => $isStudent,
                ];
            }

            // Next link pagination support
            $nextLink = $data['@odata.nextLink'] ?? null;
            if ($nextLink) {
                $parsed = parse_url($nextLink);
                $endpoint = $parsed['path'] ?? '';
                if (!str_starts_with($endpoint, '/v1.0/')) {
                    $endpoint = '/users?' . ($parsed['query'] ?? '');
                } else {
                    $endpoint = str_replace('/v1.0', '', $endpoint) . '?' . ($parsed['query'] ?? '');
                }
            } else {
                $endpoint = null;
            }

        } while ($endpoint);

        return $users;
    }

    private function graph()
    {
        $svc = app(MicrosoftGraphService::class);
        $reflection = new \ReflectionClass($svc);
        $methodRef = $reflection->getMethod('graph');
        $methodRef->setAccessible(true);
        return $methodRef->invoke($svc);
    }
}
