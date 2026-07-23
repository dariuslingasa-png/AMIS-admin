<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustCloudflareHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->headers->has('CF-Connecting-IP')) {
            $realIp = $request->header('CF-Connecting-IP');
            $request->server->set('REMOTE_ADDR', $realIp);
        }

        return $next($request);
    }
}
