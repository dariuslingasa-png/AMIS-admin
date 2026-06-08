<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academic\ClassAdvisoryAssignmentRequest;
use App\Http\Requests\Academic\SubjectRequest;
use App\Models\Subject;
use App\Repositories\AcademicRepository;
use App\Services\Admin\Academic\ClassAdvisoryAssignmentService;
use App\Services\Admin\Academic\AcademicPageService;
use App\Services\Admin\Academic\SubjectCatalogService;
use App\Services\Admin\Academic\TeacherDirectoryService;
use Illuminate\Support\Facades\Gate;

class AdminAcademicController extends Controller
{
    public function __construct(
        private readonly AcademicPageService $pages,
        private readonly AcademicRepository $academic,
        private readonly SubjectCatalogService $subjects,
        private readonly ClassAdvisoryAssignmentService $advisories,
        private readonly TeacherDirectoryService $teachers
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
            'subjects' => $this->subjects->list(),
        ]);
    }

    public function storeSubject(SubjectRequest $request)
    {
        $this->subjects->create($request->validated());

        return back()->with('status', 'Subject created.');
    }

    public function updateSubject(SubjectRequest $request, Subject $subject)
    {
        $this->subjects->update($subject, $request->validated());

        return back()->with('status', 'Subject updated.');
    }

    public function archiveSubject(Subject $subject)
    {
        Gate::authorize('manage-academic');
        $this->subjects->archive($subject);

        return back()->with('status', 'Subject archived.');
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

    public function assignClassAdvisory(ClassAdvisoryAssignmentRequest $request)
    {
        $teacher = $this->teachers->find($request->validated('teacher_key'));
        abort_unless($teacher, 404, 'Teacher not found.');

        $this->advisories->assign(
            $teacher,
            (int) $request->validated('section_id'),
            $request->validated('school_year')
        );

        return back()->with('status', 'Class advisory assigned.');
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
