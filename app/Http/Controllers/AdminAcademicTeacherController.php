<?php

namespace App\Http\Controllers;

use App\Http\Requests\Academic\StoreTeacherRequest;
use App\Http\Requests\Academic\TeacherCredentialRequest;
use App\Http\Requests\Academic\UpdateTeacherRequest;
use App\Http\Requests\Academic\UpdateTeacherSubjectsRequest;
use App\Services\Admin\Academic\TeacherDirectoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminAcademicTeacherController extends Controller
{
    public function __construct(private readonly TeacherDirectoryService $teachers) {}

    public function index(Request $request)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.teachers', $this->teachers->indexPayload($request->query('edit')));
    }

    public function store(StoreTeacherRequest $request)
    {
        $result = $this->teachers->create($request->validated(), $request);

        return redirect()->route('admin.academic.teachers')
            ->with('status', 'Teacher registered successfully.')
            ->with('temp_credentials', [
                'email' => $result['teacher']['email'],
                'password' => $result['teacher']['temporary_password'],
            ]);
    }

    public function update(UpdateTeacherRequest $request)
    {
        $this->teachers->update($request->validated(), $request);

        return redirect()
            ->route('admin.academic.teachers')
            ->with('status', 'Teacher profile updated.');
    }

    public function show(string $id)
    {
        Gate::authorize('manage-academic');

        return view('admin.academic.teacher-view', $this->teachers->profile($id));
    }

    public function updateSubjects(UpdateTeacherSubjectsRequest $request, string $id)
    {
        $this->teachers->updateSubjects($id, $request->validated('subjects') ?? []);

        return redirect()
            ->route('admin.academic.teachers.view', $id)
            ->with('status', 'Subject load updated.');
    }

    public function resendCredentials(TeacherCredentialRequest $request)
    {
        $credentials = $this->teachers->resetCredentials($request->validated('id'));

        return back()
            ->with('status', 'Credentials updated and temporary password generated.')
            ->with('temp_credentials', $credentials);
    }

    public function togglePasswordChanged(string $id)
    {
        Gate::authorize('manage-academic');
        $this->teachers->togglePasswordChanged($id);

        return back()->with('status', 'Password changed status updated.');
    }

    public function destroy(string $id)
    {
        Gate::authorize('manage-academic');
        $this->teachers->delete($id);

        return redirect()->route('admin.academic.teachers')->with('status', 'Teacher deleted successfully.');
    }
}
