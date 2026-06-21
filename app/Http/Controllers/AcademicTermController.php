<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcademicTermDeactivateRequest;
use App\Http\Requests\AcademicTermIndexRequest;
use App\Http\Requests\AcademicTermReactivateRequest;
use App\Http\Requests\AcademicTermStoreRequest;
use App\Http\Requests\AcademicTermUpdateRequest;
use App\Models\AcademicTerm;
use App\Models\User;
use App\Services\AcademicTermService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AcademicTermController extends Controller
{
    public function __construct(private readonly AcademicTermService $academicTerms) {}

    public function index(AcademicTermIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('academic-terms.index', [
            'academicTerms' => $this->academicTerms->listFor($actor, $request->validated()),
            'academicYears' => $this->academicTerms->availableYearsFor($actor),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', AcademicTerm::class);
        /** @var User $actor */
        $actor = $request->user();

        return view('academic-terms.create', [
            'academicYears' => $this->academicTerms->availableYearsFor($actor, true),
        ]);
    }

    public function store(AcademicTermStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $academicTerm = $this->academicTerms->create($request->validated(), $actor);

        return redirect()
            ->route('academic-terms.show', $academicTerm)
            ->with('status', 'Academic term created successfully.');
    }

    public function show(Request $request, AcademicTerm $academicTerm): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('academic-terms.show', [
            'academicTerm' => $this->academicTerms->detailsFor($academicTerm, $actor),
        ]);
    }

    public function edit(Request $request, AcademicTerm $academicTerm): View
    {
        $this->authorize('update', $academicTerm);
        /** @var User $actor */
        $actor = $request->user();

        return view('academic-terms.edit', [
            'academicTerm' => $academicTerm,
            'academicYears' => $this->academicTerms->availableYearsFor($actor, true),
        ]);
    }

    public function update(
        AcademicTermUpdateRequest $request,
        AcademicTerm $academicTerm,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->academicTerms->update($academicTerm, $request->validated(), $actor);

        return redirect()
            ->route('academic-terms.show', $academicTerm)
            ->with('status', 'Academic term updated successfully.');
    }

    public function deactivate(
        AcademicTermDeactivateRequest $request,
        AcademicTerm $academicTerm,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->academicTerms->deactivate($academicTerm, $actor);

        return redirect()
            ->route('academic-terms.show', $academicTerm)
            ->with('status', 'Academic term deactivated successfully.');
    }

    public function reactivate(
        AcademicTermReactivateRequest $request,
        AcademicTerm $academicTerm,
    ): RedirectResponse {
        /** @var User $actor */
        $actor = $request->user();
        $this->academicTerms->reactivate($academicTerm, $actor);

        return redirect()
            ->route('academic-terms.show', $academicTerm)
            ->with('status', 'Academic term reactivated successfully.');
    }
}
