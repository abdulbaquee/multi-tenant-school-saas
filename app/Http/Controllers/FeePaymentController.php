<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeePaymentHistoryIndexRequest;
use App\Models\FeePayment;
use App\Models\User;
use App\Services\FeeCollectionService;
use App\Services\FeePaymentHistoryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeePaymentController extends Controller
{
    public function __construct(
        private readonly FeeCollectionService $feeCollections,
        private readonly FeePaymentHistoryService $paymentHistory,
    ) {}

    public function index(FeePaymentHistoryIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('fee-payments.index', [
            'feePayments' => $this->paymentHistory->listFor($actor, $request->validated()),
        ]);
    }

    public function show(Request $request, FeePayment $feePayment): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->authorize('view', $feePayment);

        return view('fee-payments.show', [
            'feePayment' => $this->feeCollections->receiptFor($feePayment, $actor),
        ]);
    }

    public function print(Request $request, FeePayment $feePayment): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->authorize('view', $feePayment);

        return view('fee-payments.print', [
            'feePayment' => $this->feeCollections->receiptFor($feePayment, $actor),
        ]);
    }
}
