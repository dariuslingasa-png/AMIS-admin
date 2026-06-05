<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;

class StudentOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (! Auth::check()) {
            return redirect()->route('student.login');
        }

        // Check if user is associated with a student record
        $studentExists = Student::where('user_id', Auth::id())->exists();

        if (!$studentExists) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('student.login')
                ->withErrors(['email' => 'This account is not registered as a student.']);
        }

        if ((Auth::user()->account_status ?? 'verified') !== 'verified') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('student.login')
                ->withErrors(['email' => 'Your student account is currently disabled. Please contact administration.']);
        }

        return $next($request);
    }
}
