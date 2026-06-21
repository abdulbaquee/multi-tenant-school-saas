<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicYearActivateRequest;
use App\Http\Requests\AcademicYearDeactivateRequest;
use App\Http\Requests\AcademicYearIndexRequest;
use App\Http\Requests\AcademicYearReactivateRequest;
use App\Http\Requests\AcademicYearStoreRequest;
use App\Http\Requests\AcademicYearUpdateRequest;
use App\Models\AcademicYear;
use App\Models\User;
use App\Services\AcademicYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicYearController extends Controller
{
    public function __construct(private readonly AcademicYearService $academicYears) {}

    public function index(AcademicYearIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('academic-years.index', [
            'academicYears' => $this->academicYears->listFor($actor, $request->validated()),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AcademicYear::class);

        return view('academic-years.create');
    }

    public function store(AcademicYearStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $academicYear = $this->academicYears->create($request->validated(), $actor);

        return redirect()
            ->route('academic-years.show', $academicYear)
            ->with('status', 'Academic year created successfully.');
    }

    public function show(Request $request, AcademicYear $academicYear): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('academic-years.show', [
            'academicYear' => $this->academicYears->detailsFor($academicYear, $actor),
        ]);
    }

    public function edit(AcademicYear $academicYear): View
    {
        $this->authorize('update', $academicYear);

        return view('academic-years.edit', compact('academicYear'));
    }

    public function update(
        AcademicYearUpdateRequest $request,
        AcademicYear $academicYear,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->academicYears->update($academicYear, $request->validated(), $actor);

        return redirect()
            ->route('academic-years.show', $academicYear)
            ->with('status', 'Academic year updated successfully.');
    }

    public function activate(
        AcademicYearActivateRequest $request,
        AcademicYear $academicYear,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->academicYears->makeCurrent($academicYear, $actor);

        return redirect()
            ->route('academic-years.show', $academicYear)
            ->with('status', 'Academic year set as current successfully.');
    }

    public function deactivate(
        AcademicYearDeactivateRequest $request,
        AcademicYear $academicYear,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->academicYears->deactivate($academicYear, $actor);

        return redirect()
            ->route('academic-years.show', $academicYear)
            ->with('status', 'Academic year deactivated successfully.');
    }

    public function reactivate(
        AcademicYearReactivateRequest $request,
        AcademicYear $academicYear,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->academicYears->reactivate($academicYear, $actor);

        return redirect()
            ->route('academic-years.show', $academicYear)
            ->with('status', 'Academic year reactivated successfully.');
    }
}
