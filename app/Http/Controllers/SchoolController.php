<?php

namespace App\Http\Controllers;

use App\Http\Requests\SchoolDeactivateRequest;
use App\Http\Requests\SchoolIndexRequest;
use App\Http\Requests\SchoolStoreRequest;
use App\Http\Requests\SchoolUpdateRequest;
use App\Models\School;
use App\Services\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolController extends Controller
{
    public function __construct(private readonly SchoolService $schools) {}

    public function index(SchoolIndexRequest $request): View
    {
        $this->authorize('viewAny', School::class);

        $schools = $this->schools->listFor($request->user(), $request->validated());

        return view('schools.index', compact('schools'));
    }

    public function create(): View
    {
        $this->authorize('create', School::class);

        return view('schools.create');
    }

    public function store(SchoolStoreRequest $request): RedirectResponse
    {
        $this->authorize('create', School::class);

        $school = $this->schools->create($request->validated(), $request->user());

        return redirect()
            ->route('schools.show', $school)
            ->with('status', 'School created successfully.');
    }

    public function show(School $school): View
    {
        $this->authorize('view', $school);

        $school->load('settings')->loadCount('users');

        return view('schools.show', compact('school'));
    }

    public function edit(School $school): View
    {
        $this->authorize('update', $school);

        return view('schools.edit', compact('school'));
    }

    public function update(SchoolUpdateRequest $request, School $school): RedirectResponse
    {
        $this->authorize('update', $school);

        $this->schools->update($school, $request->validated(), $request->user());

        return redirect()
            ->route('schools.show', $school)
            ->with('status', 'School updated successfully.');
    }

    public function deactivate(SchoolDeactivateRequest $request, School $school): RedirectResponse
    {
        $this->authorize('deactivate', $school);

        $this->schools->deactivate($school, $request->string('reason')->toString(), $request->user());

        return redirect()
            ->route('schools.show', $school)
            ->with('status', 'School deactivated successfully.');
    }

    public function activate(Request $request, School $school): RedirectResponse
    {
        $this->authorize('activate', $school);

        $this->schools->activate($school, $request->user());

        return redirect()
            ->route('schools.show', $school)
            ->with('status', 'School activated successfully.');
    }
}
