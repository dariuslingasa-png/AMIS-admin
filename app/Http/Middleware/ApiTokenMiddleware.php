<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        // Retrieve configured token from config (safe under config:cache)
        $configuredToken = config('services.amis.api_token', 'amis-scan-key-2026-secure');

        if (!$token || $token !== $configuredToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing API token.'
            ], 401);
        }

        return $next($request);
    }
}
