<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('admin.login');
        }

        $user = Auth::user();

        if (! $user->hasAdminPortalAccess()) {
            abort(403);
        }

        if (($user->account_status ?? 'verified') !== 'verified') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'Account access is disabled. Please contact the system administrator.']);
        }

        if (
            $user->active_admin_session_id
            && $user->active_admin_session_id !== $request->session()->getId()
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->withErrors(['email' => 'This account was signed in from another device. Please log in again.']);
        }

        if ($user->isViewOnlyAccess() && ! $request->isMethodSafe()) {
            abort(403, 'This account is view-only.');
        }

        if (! $user->canAccessAdminRoute($request->route()?->getName())) {
            abort(403);
        }

        return $next($request);
    }
}
