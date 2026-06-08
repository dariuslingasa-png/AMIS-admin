<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentPortalService;
use Illuminate\Support\Facades\Auth;

class StudentPortalController extends Controller
{
    protected StudentPortalService $portalService;

    public function __construct(StudentPortalService $portalService)
    {
        $this->portalService = $portalService;
    }

    public function dashboard()
    {
        $this->authorize('viewPortal', Student::class);

        $data = $this->portalService->getDashboardData(Auth::id());

        return view('student.dashboard', $data);
    }

    public function announcements()
    {
        $this->authorize('viewPortal', Student::class);

        $data = $this->portalService->getAnnouncementsData(Auth::id());

        return view('student.announcements', $data);
    }

    public function schedule()
    {
        $this->authorize('viewPortal', Student::class);

        $data = $this->portalService->getScheduleData(Auth::id());

        return view('student.schedule', $data);
    }

    public function subjects()
    {
        $this->authorize('viewPortal', Student::class);

        $data = $this->portalService->getSubjectsData(Auth::id());

        return view('student.subjects', $data);
    }

    public function grades()
    {
        $this->authorize('viewPortal', Student::class);

        $data = $this->portalService->getGradesData(Auth::id());

        return view('student.grades', $data);
    }

    public function profile()
    {
        $this->authorize('viewPortal', Student::class);

        $data = $this->portalService->getProfileData(Auth::id());

        return view('student.profile', $data);
    }

    public function settings()
    {
        $this->authorize('viewPortal', Student::class);

        $data = $this->portalService->getSettingsData(Auth::id());

        return view('student.settings', $data);
    }
}
