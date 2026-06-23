<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeeStructureIndexRequest;
use App\Http\Requests\FeeStructureLifecycleRequest;
use App\Http\Requests\FeeStructureStoreRequest;
use App\Http\Requests\FeeStructureUpdateRequest;
use App\Models\FeeStructure;
use App\Models\User;
use App\Services\FeeStructureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeStructureController extends Controller
{
    public function __construct(private readonly FeeStructureService $feeStructures) {}

    public function index(FeeStructureIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('fee-structures.index', [
            'feeStructures' => $this->feeStructures->listFor($actor, $request->validated()),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', FeeStructure::class);

        return view('fee-structures.create', $this->feeStructures->formOptions(auth()->user()));
    }

    public function store(FeeStructureStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $feeStructure = $this->feeStructures->create($request->validated(), $actor);

        return redirect()->route('fee-structures.show', $feeStructure)->with('status', __('Fee Structure created successfully.'));
    }

    public function show(Request $request, FeeStructure $feeStructure): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('fee-structures.show', [
            'feeStructure' => $this->feeStructures->detailsFor($feeStructure, $actor),
        ]);
    }

    public function edit(FeeStructure $feeStructure): View
    {
        $this->authorize('update', $feeStructure);

        return view('fee-structures.edit', [
            'feeStructure' => $feeStructure->load(['feeCategory', 'academicYear', 'schoolClass']),
        ]);
    }

    public function update(FeeStructureUpdateRequest $request, FeeStructure $feeStructure): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->feeStructures->update($feeStructure, $request->validated(), $actor);

        return redirect()->route('fee-structures.show', $feeStructure)->with('status', __('Fee Structure updated successfully.'));
    }

    public function activate(FeeStructureLifecycleRequest $request, FeeStructure $feeStructure): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->feeStructures->activate($feeStructure, $actor);

        return redirect()->route('fee-structures.show', $feeStructure)->with('status', __('Fee Structure activated successfully.'));
    }

    public function deactivate(FeeStructureLifecycleRequest $request, FeeStructure $feeStructure): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->feeStructures->deactivate($feeStructure, $actor);

        return redirect()->route('fee-structures.show', $feeStructure)->with('status', __('Fee Structure deactivated successfully.'));
    }
}
