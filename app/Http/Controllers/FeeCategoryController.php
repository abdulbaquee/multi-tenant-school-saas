<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeeCategoryIndexRequest;
use App\Http\Requests\FeeCategoryLifecycleRequest;
use App\Http\Requests\FeeCategoryStoreRequest;
use App\Http\Requests\FeeCategoryUpdateRequest;
use App\Models\FeeCategory;
use App\Models\User;
use App\Services\FeeCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeCategoryController extends Controller
{
    public function __construct(private readonly FeeCategoryService $feeCategories) {}

    public function index(FeeCategoryIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('fee-categories.index', [
            'feeCategories' => $this->feeCategories->listFor($actor, $request->validated()),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', FeeCategory::class);

        return view('fee-categories.create');
    }

    public function store(FeeCategoryStoreRequest $request): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $feeCategory = $this->feeCategories->create($request->validated(), $actor);

        return redirect()->route('fee-categories.show', $feeCategory)->with('status', __('Fee Category created successfully.'));
    }

    public function show(Request $request, FeeCategory $feeCategory): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('fee-categories.show', [
            'feeCategory' => $this->feeCategories->detailsFor($feeCategory, $actor),
        ]);
    }

    public function edit(FeeCategory $feeCategory): View
    {
        $this->authorize('update', $feeCategory);

        return view('fee-categories.edit', compact('feeCategory'));
    }

    public function update(FeeCategoryUpdateRequest $request, FeeCategory $feeCategory): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->feeCategories->update($feeCategory, $request->validated(), $actor);

        return redirect()->route('fee-categories.show', $feeCategory)->with('status', __('Fee Category updated successfully.'));
    }

    public function activate(FeeCategoryLifecycleRequest $request, FeeCategory $feeCategory): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->feeCategories->activate($feeCategory, $actor);

        return redirect()->route('fee-categories.show', $feeCategory)->with('status', __('Fee Category activated successfully.'));
    }

    public function deactivate(FeeCategoryLifecycleRequest $request, FeeCategory $feeCategory): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->feeCategories->deactivate($feeCategory, $actor);

        return redirect()->route('fee-categories.show', $feeCategory)->with('status', __('Fee Category deactivated successfully.'));
    }
}
