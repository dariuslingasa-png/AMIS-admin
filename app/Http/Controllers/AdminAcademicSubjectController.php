<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academic\SubjectRequest;
use App\Models\Subject;
use App\Services\Admin\Academic\SubjectCatalogService;
use Illuminate\Support\Facades\Gate;

class AdminAcademicSubjectController extends Controller
{
    public function __construct(private readonly SubjectCatalogService $subjects) {}

    public function store(SubjectRequest $request)
    {
        Gate::authorize('manage-academic');
        $this->subjects->create($request->validated());

        return back()->with('status', 'Subject created.');
    }

    public function update(SubjectRequest $request, Subject $subject)
    {
        Gate::authorize('manage-academic');
        $this->subjects->update($subject, $request->validated());

        return back()->with('status', 'Subject updated.');
    }

    public function archive(Subject $subject)
    {
        Gate::authorize('manage-academic');
        $this->subjects->archive($subject);

        return back()->with('status', 'Subject archived.');
    }

    public function restore(Subject $subject)
    {
        Gate::authorize('manage-academic');
        $this->subjects->restore($subject);

        return back()->with('status', 'Subject restored.');
    }
}
