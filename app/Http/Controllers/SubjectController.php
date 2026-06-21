<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubjectIndexRequest;
use App\Http\Requests\SubjectLifecycleRequest;
use App\Http\Requests\SubjectStoreRequest;
use App\Http\Requests\SubjectUpdateRequest;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use App\Services\SubjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubjectController extends Controller
{
    public function __construct(private readonly SubjectService $subjects) {}

    public function index(SubjectIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('subjects.index', [
            'subjects' => $this->subjects->listFor($actor, $request->validated()),
            'assignedView' => $actor->hasRoleCode(Role::TEACHER),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Subject::class);
        /** @var User $actor */
        $actor = $request->user();

        return view('subjects.create', $this->subjects->formOptions($actor));
    }

    public function store(SubjectStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $subject = $this->subjects->create($request->validated(), $actor);

        return redirect()->route('subjects.show', $subject)->with('status', 'Subject created successfully.');
    }

    public function show(Request $request, Subject $subject): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('subjects.show', [
            'subject' => $this->subjects->detailsFor($subject, $actor),
            'assignedView' => $actor->hasRoleCode(Role::TEACHER),
        ]);
    }

    public function edit(Request $request, Subject $subject): View
    {
        $this->authorize('update', $subject);
        /** @var User $actor */
        $actor = $request->user();

        return view('subjects.edit', [
            'subject' => $subject,
            ...$this->subjects->formOptions($actor),
        ]);
    }

    public function update(SubjectUpdateRequest $request, Subject $subject): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->subjects->update($subject, $request->validated(), $actor);

        return redirect()->route('subjects.show', $subject)->with('status', 'Subject updated successfully.');
    }

    public function activate(SubjectLifecycleRequest $request, Subject $subject): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->subjects->activate($subject, $actor);

        return redirect()->route('subjects.show', $subject)->with('status', 'Subject activated successfully.');
    }

    public function deactivate(SubjectLifecycleRequest $request, Subject $subject): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->subjects->deactivate($subject, $actor);

        return redirect()->route('subjects.show', $subject)->with('status', 'Subject deactivated successfully.');
    }

    public function archive(SubjectLifecycleRequest $request, Subject $subject): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $subject = $this->subjects->archive($subject, $actor);

        return redirect()->route('subjects.show', $subject)->with('status', 'Subject archived successfully.');
    }

    public function restore(SubjectLifecycleRequest $request, Subject $subject): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $subject = $this->subjects->restore($subject, $actor);

        return redirect()->route('subjects.show', $subject)->with('status', 'Subject restored as inactive successfully.');
    }
}
