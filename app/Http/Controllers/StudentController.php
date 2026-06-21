<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentIndexRequest;
use App\Http\Requests\StudentLifecycleRequest;
use App\Http\Requests\StudentShowRequest;
use App\Http\Requests\StudentStoreRequest;
use App\Http\Requests\StudentUpdateRequest;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Services\StudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function __construct(private readonly StudentService $students) {}

    public function index(StudentIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $filters = $request->validated();

        return view('students.index', [
            'students' => $this->students->listFor($actor, $filters),
            'schools' => $this->students->selectableSchoolsFor($actor),
            'selectedSchoolId' => filled($filters['school_id'] ?? null) ? (int) $filters['school_id'] : null,
            'privacyLimited' => $actor->isSuperAdmin() || $actor->hasRoleCode(Role::TEACHER),
            'teacherView' => $actor->hasRoleCode(Role::TEACHER),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Student::class);

        return view('students.create');
    }

    public function store(StudentStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $student = $this->students->create($request->validated(), $actor);

        return redirect()
            ->route('students.show', $student)
            ->with('status', 'Student created successfully.');
    }

    public function show(StudentShowRequest $request, Student $student): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $schoolId = filled($request->validated('school_id')) ? (int) $request->validated('school_id') : null;

        return view('students.show', [
            'student' => $this->students->detailsFor($student, $actor, $schoolId),
            'privacyLimited' => $actor->isSuperAdmin() || $actor->hasRoleCode(Role::TEACHER),
            'selectedSchoolId' => $schoolId,
        ]);
    }

    public function edit(Student $student): View
    {
        $this->authorize('update', $student);

        return view('students.edit', ['student' => $student]);
    }

    public function update(StudentUpdateRequest $request, Student $student): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->students->update($student, $request->validated(), $actor);

        return redirect()
            ->route('students.show', $student)
            ->with('status', 'Student updated successfully.');
    }

    public function activate(StudentLifecycleRequest $request, Student $student): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->students->activate($student, $actor);

        return redirect()->route('students.show', $student)->with('status', 'Student activated successfully.');
    }

    public function deactivate(StudentLifecycleRequest $request, Student $student): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->students->deactivate($student, $actor);

        return redirect()->route('students.show', $student)->with('status', 'Student deactivated successfully.');
    }

    public function archive(StudentLifecycleRequest $request, Student $student): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $student = $this->students->archive($student, $actor);

        return redirect()->route('students.show', $student)->with('status', 'Student archived successfully.');
    }

    public function restore(StudentLifecycleRequest $request, Student $student): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $student = $this->students->restore($student, $actor);

        return redirect()->route('students.show', $student)->with('status', 'Student restored as inactive successfully.');
    }
}
