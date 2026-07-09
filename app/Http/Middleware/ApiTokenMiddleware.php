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
        
        // Retrieve configure token from env with a secure fallback
        $configuredToken = env('AMIS_API_TOKEN', 'amis-scan-key-2026-secure');

        if (!$token || $token !== $configuredToken) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: Invalid or missing API token.'
            ], 401);
        }

        return $next($request);
    }
}
