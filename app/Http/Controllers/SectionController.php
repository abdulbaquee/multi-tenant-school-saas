<?php

namespace App\Http\Controllers;

use App\Http\Requests\SectionIndexRequest;
use App\Http\Requests\SectionLifecycleRequest;
use App\Http\Requests\SectionStoreRequest;
use App\Http\Requests\SectionUpdateRequest;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Services\SectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionController extends Controller
{
    public function __construct(private readonly SectionService $sections) {}

    public function index(SectionIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('sections.index', [
            'sections' => $this->sections->listFor($actor, $request->validated()),
            'assignedView' => $actor->hasRoleCode(Role::TEACHER),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Section::class);
        /** @var User $actor */
        $actor = $request->user();

        return view('sections.create', $this->sections->formOptions($actor));
    }

    public function store(SectionStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $section = $this->sections->create($request->validated(), $actor);

        return redirect()->route('sections.show', $section)->with('status', 'Section created successfully.');
    }

    public function show(Request $request, Section $section): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('sections.show', [
            'section' => $this->sections->detailsFor($section, $actor),
            'assignedView' => $actor->hasRoleCode(Role::TEACHER),
        ]);
    }

    public function edit(Request $request, Section $section): View
    {
        $this->authorize('update', $section);
        /** @var User $actor */
        $actor = $request->user();

        return view('sections.edit', [
            'section' => $section,
            ...$this->sections->formOptions($actor),
        ]);
    }

    public function update(SectionUpdateRequest $request, Section $section): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->sections->update($section, $request->validated(), $actor);

        return redirect()->route('sections.show', $section)->with('status', 'Section updated successfully.');
    }

    public function activate(SectionLifecycleRequest $request, Section $section): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->sections->activate($section, $actor);

        return redirect()->route('sections.show', $section)->with('status', 'Section activated successfully.');
    }

    public function deactivate(SectionLifecycleRequest $request, Section $section): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->sections->deactivate($section, $actor);

        return redirect()->route('sections.show', $section)->with('status', 'Section deactivated successfully.');
    }

    public function archive(SectionLifecycleRequest $request, Section $section): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $section = $this->sections->archive($section, $actor);

        return redirect()->route('sections.show', $section)->with('status', 'Section archived successfully.');
    }

    public function restore(SectionLifecycleRequest $request, Section $section): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $section = $this->sections->restore($section, $actor);

        return redirect()->route('sections.show', $section)->with('status', 'Section restored as inactive successfully.');
    }
}
