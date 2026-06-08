<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Enums\UserRole;
use App\Models\Student;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('student.login');
        }

        $user = Auth::user();
        $email = $user->email ?? '';
        $student = Student::where('user_id', $user->id)->first();

        if (
            $user->role !== UserRole::Student->value ||
            ! str_ends_with(strtolower($email), '@amis.edu.ph') ||
            ($user->account_status ?? AccountStatus::Verified->value) !== AccountStatus::Verified->value ||
            ! $student ||
            ! $student->applicant ||
            $student->applicant->status !== 'approved'
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $errorMessage = 'Access denied. This account is not allowed to access the Student Portal.';
            $cookie = cookie('microsoft_auth_error', $errorMessage, 5);

            $tenantId = config('services.azure.tenant_id');
            $redirectUri = config('services.azure.redirect_uri_student');
            $logoutUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/logout?".http_build_query([
                'post_logout_redirect_uri' => $redirectUri,
            ]);

            return redirect()->away($logoutUrl)->withCookie($cookie);
        }

        return $next($request);
    }
}
