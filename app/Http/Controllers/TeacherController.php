<?php

namespace App\Http\Controllers;

use App\Http\Requests\TeacherActivateRequest;
use App\Http\Requests\TeacherArchiveRequest;
use App\Http\Requests\TeacherDeactivateRequest;
use App\Http\Requests\TeacherIndexRequest;
use App\Http\Requests\TeacherRestoreRequest;
use App\Http\Requests\TeacherStoreRequest;
use App\Http\Requests\TeacherUpdateRequest;
use App\Models\Teacher;
use App\Models\User;
use App\Services\TeacherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherController extends Controller
{
    public function __construct(private readonly TeacherService $teachers) {}

    public function index(TeacherIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('teacher-profiles.index', [
            'teachers' => $this->teachers->listFor($actor, $request->validated()),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Teacher::class);
        /** @var User $actor */
        $actor = $request->user();

        return view('teacher-profiles.create', [
            'eligibleUsers' => $this->teachers->eligibleUsersFor($actor),
        ]);
    }

    public function store(TeacherStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $teacher = $this->teachers->create($request->validated(), $actor);

        return redirect()
            ->route('teacher-profiles.show', $teacher)
            ->with('status', 'Teacher Profile created successfully.');
    }

    public function show(Request $request, Teacher $teacherProfile): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('teacher-profiles.show', [
            'teacher' => $this->teachers->detailsFor($teacherProfile, $actor),
        ]);
    }

    public function edit(Teacher $teacherProfile): View
    {
        $this->authorize('update', $teacherProfile);

        return view('teacher-profiles.edit', ['teacher' => $teacherProfile->load('user')]);
    }

    public function update(
        TeacherUpdateRequest $request,
        Teacher $teacherProfile,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->teachers->update($teacherProfile, $request->validated(), $actor);

        return redirect()
            ->route('teacher-profiles.show', $teacherProfile)
            ->with('status', 'Teacher Profile updated successfully.');
    }

    public function activate(
        TeacherActivateRequest $request,
        Teacher $teacherProfile,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->teachers->activate($teacherProfile, $actor);

        return redirect()
            ->route('teacher-profiles.show', $teacherProfile)
            ->with('status', 'Teacher Profile activated successfully.');
    }

    public function deactivate(
        TeacherDeactivateRequest $request,
        Teacher $teacherProfile,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->teachers->deactivate($teacherProfile, $actor);

        return redirect()
            ->route('teacher-profiles.show', $teacherProfile)
            ->with('status', 'Teacher Profile deactivated successfully.');
    }

    public function archive(
        TeacherArchiveRequest $request,
        Teacher $teacherProfile,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $teacher = $this->teachers->archive($teacherProfile, $actor);

        return redirect()
            ->route('teacher-profiles.show', $teacher)
            ->with('status', 'Teacher Profile archived successfully.');
    }

    public function restore(
        TeacherRestoreRequest $request,
        Teacher $teacherProfile,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $teacher = $this->teachers->restore($teacherProfile, $actor);

        return redirect()
            ->route('teacher-profiles.show', $teacher)
            ->with('status', 'Teacher Profile restored as inactive successfully.');
    }
}
