<?php

namespace App\DTOs;

use App\Models\User;

final readonly class MicrosoftLoginResult
{
    public function __construct(
        public bool $successful,
        public ?User $user = null,
        public ?string $errorMessage = null,
        public bool $requiresMicrosoftLogout = false,
        public ?string $tenantId = null,
        public ?string $redirectUri = null,
    ) {}

    public static function success(User $user): self
    {
        return new self(true, $user);
    }

    public static function failure(string $message): self
    {
        return new self(false, null, $message);
    }

    public static function denied(string $message, string $tenantId, string $redirectUri): self
    {
        return new self(false, null, $message, true, $tenantId, $redirectUri);
    }
}
