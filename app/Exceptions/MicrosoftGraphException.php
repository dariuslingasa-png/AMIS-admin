<?php

namespace App\Exceptions;

use RuntimeException;

class MicrosoftGraphException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly ?string $graphCode = null,
    ) {
        parent::__construct($message, $httpStatus);
    }

    public function userMessage(): string
    {
        return match ($this->httpStatus) {
            401 => 'Microsoft Graph rejected the application credentials. Verify the tenant, client ID, and secret.',
            403 => 'Microsoft Graph access is missing required permissions or administrator consent.',
            404 => 'The Microsoft Team is no longer available.',
            429 => 'Microsoft Graph is temporarily throttling requests. Please retry after the indicated delay.',
            500, 502, 503, 504 => 'Microsoft Graph is temporarily unavailable. Please try again later.',
            default => $this->getMessage() ?: 'Microsoft Graph could not be reached.',
        };
    }
}
