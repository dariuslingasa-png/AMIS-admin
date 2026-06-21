<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class IdleTimeout
{
    /**
     * Handle an incoming request.
     *
     * If the authenticated user has been idle for longer than the configured
     * timeout, log them out and redirect to the admin login with a message.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $request->hasSession()) {
            $idleMinutes = (int) config('session.idle_timeout', 30);
            $lastActivity = $request->session()->get('last_activity');

            if ($lastActivity && (time() - $lastActivity) > ($idleMinutes * 60)) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')
                    ->with('info', 'You have been logged out due to inactivity.');
            }

            // Update last activity timestamp on every request
            $request->session()->put('last_activity', time());
        }

        return $next($request);
    }
}
