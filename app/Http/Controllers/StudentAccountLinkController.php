<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentAccountLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentAccountLinkController extends Controller
{
    protected StudentAccountLinkService $linkService;

    public function __construct(StudentAccountLinkService $linkService)
    {
        $this->linkService = $linkService;
    }

    public function redirectGoogle(Request $request)
    {
        $this->authorize('viewPortal', Student::class);

        $authUrl = $this->linkService->getGoogleAuthUrl($request);

        if (! $authUrl) {
            return back()->with('error', 'Google linking is not configured yet.');
        }

        return redirect()->away($authUrl);
    }

    public function callbackGoogle(Request $request)
    {
        $this->authorize('viewPortal', Student::class);

        if ($request->filled('error')) {
            return redirect()->route('student.settings')->with('error', 'Google linking was cancelled.');
        }

        $sessionState = (string) $request->session()->pull('student_google_oauth_state');
        $queryState = (string) $request->query('state');
        $code = (string) $request->query('code');

        $errorMessage = $this->linkService->linkGoogleAccount(
            Auth::user(),
            $code,
            $sessionState,
            $queryState
        );

        if ($errorMessage !== '') {
            return redirect()->route('student.settings')->with('error', $errorMessage);
        }

        return redirect()->route('student.settings')->with('success', 'Google account linked successfully.');
    }

    public function unlinkGoogle()
    {
        $this->authorize('viewPortal', Student::class);

        $this->linkService->unlinkGoogleAccount(Auth::user());

        return back()->with('success', 'Google account unlinked.');
    }
}
