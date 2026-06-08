<?php

namespace App\DTOs;

use App\Models\User;

final readonly class AuthAttemptResult
{
    public function __construct(
        public bool $successful,
        public ?User $user = null,
        public ?string $errorField = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(User $user): self
    {
        return new self(true, $user);
    }

    public static function failure(string $field, string $message): self
    {
        return new self(false, null, $field, $message);
    }
}
