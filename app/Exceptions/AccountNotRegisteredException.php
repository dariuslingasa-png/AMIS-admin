<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class AccountNotRegisteredException extends Exception
{
    protected $message = 'ERR_ACCOUNT_NOT_REGISTERED';
    protected $code = 401;

    public function render($request): JsonResponse
    {
        return response()->json([
            'error' => $this->message
        ], $this->code);
    }
}
