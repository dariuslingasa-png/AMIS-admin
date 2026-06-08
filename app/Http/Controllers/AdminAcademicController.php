<?php

namespace App\Http\Controllers;

use App\Repositories\AcademicRepository;
use App\Services\Admin\Academic\AcademicPageService;
use Illuminate\Support\Facades\Gate;

class AdminAcademicController extends Controller
{
    public function __construct(
        private readonly AcademicPageService $pages,
        private readonly AcademicRepository $academic
    ) {
    }

    public function dashboard()
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.dashboard', $this->pages->dashboard());
    }

    public function subjects()
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.subjects', [
            'subjects' => $this->academic->subjects(),
        ]);
    }

    public function curriculum()
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.curriculum', $this->pages->curriculum());
    }

    public function classAdvisory()
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.class-advisory', $this->pages->advisory());
    }

    public function schoolYears()
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.school-years', [
            'schoolYears' => $this->pages->curriculum()['schoolYears'],
        ]);
    }

    public function calendar()
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.calendar', ['events' => []]);
    }

    public function operations()
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.operations', $this->pages->operations());
    }
}
