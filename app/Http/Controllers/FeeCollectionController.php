<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeeCollectionIndexRequest;
use App\Http\Requests\FeeCollectionStoreRequest;
use App\Models\StudentFee;
use App\Models\User;
use App\Services\FeeCollectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeCollectionController extends Controller
{
    public function __construct(private readonly FeeCollectionService $feeCollections) {}

    public function index(FeeCollectionIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('fee-collections.index', [
            'studentFees' => $this->feeCollections->collectibleListFor($actor, $request->validated()),
        ]);
    }

    public function create(Request $request, StudentFee $studentFee): View
    {
        $this->authorize('collect', $studentFee);

        /** @var User $actor */
        $actor = $request->user();
        $studentFee = $this->feeCollections->collectionFormFor($studentFee, $actor);
        $collectionToken = $this->feeCollections->issueCollectionToken($studentFee, $actor);

        return view('fee-collections.create', compact('studentFee', 'collectionToken'));
    }

    public function store(FeeCollectionStoreRequest $request, StudentFee $studentFee): RedirectResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $feePayment = $this->feeCollections->collect($studentFee, $request->validated(), $actor);

        return redirect()
            ->route('fee-payments.show', $feePayment)
            ->with('status', __('Payment collected successfully.'));
    }
}
