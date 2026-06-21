<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolClassActivateRequest;
use App\Http\Requests\SchoolClassArchiveRequest;
use App\Http\Requests\SchoolClassDeactivateRequest;
use App\Http\Requests\SchoolClassIndexRequest;
use App\Http\Requests\SchoolClassRestoreRequest;
use App\Http\Requests\SchoolClassStoreRequest;
use App\Http\Requests\SchoolClassUpdateRequest;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\SchoolClassService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolClassController extends Controller
{
    public function __construct(private readonly SchoolClassService $classes) {}

    public function index(SchoolClassIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('classes.index', [
            'classes' => $this->classes->listFor($actor, $request->validated()),
            'assignedView' => $actor->hasRoleCode(Role::TEACHER),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', SchoolClass::class);

        return view('classes.create');
    }

    public function store(SchoolClassStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $schoolClass = $this->classes->create($request->validated(), $actor);

        return redirect()->route('classes.show', $schoolClass)->with('status', 'Class created successfully.');
    }

    public function show(Request $request, SchoolClass $schoolClass): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('classes.show', [
            'schoolClass' => $this->classes->detailsFor($schoolClass, $actor),
            'assignedView' => $actor->hasRoleCode(Role::TEACHER),
        ]);
    }

    public function edit(SchoolClass $schoolClass): View
    {
        $this->authorize('update', $schoolClass);

        return view('classes.edit', compact('schoolClass'));
    }

    public function update(SchoolClassUpdateRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->classes->update($schoolClass, $request->validated(), $actor);

        return redirect()->route('classes.show', $schoolClass)->with('status', 'Class updated successfully.');
    }

    public function activate(SchoolClassActivateRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->classes->activate($schoolClass, $actor);

        return redirect()->route('classes.show', $schoolClass)->with('status', 'Class activated successfully.');
    }

    public function deactivate(SchoolClassDeactivateRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->classes->deactivate($schoolClass, $actor);

        return redirect()->route('classes.show', $schoolClass)->with('status', 'Class deactivated successfully.');
    }

    public function archive(SchoolClassArchiveRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $schoolClass = $this->classes->archive($schoolClass, $actor);

        return redirect()->route('classes.show', $schoolClass)->with('status', 'Class archived successfully.');
    }

    public function restore(SchoolClassRestoreRequest $request, SchoolClass $schoolClass): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $schoolClass = $this->classes->restore($schoolClass, $actor);

        return redirect()->route('classes.show', $schoolClass)->with('status', 'Class restored as inactive successfully.');
    }
}
