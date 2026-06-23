<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
use App\Models\User;
use App\Services\FeeCollectionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeePaymentController extends Controller
{
    public function __construct(private readonly FeeCollectionService $feeCollections) {}

    public function show(Request $request, FeePayment $feePayment): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('fee-payments.show', [
            'feePayment' => $this->feeCollections->receiptFor($feePayment, $actor),
        ]);
    }

    public function print(Request $request, FeePayment $feePayment): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('fee-payments.print', [
            'feePayment' => $this->feeCollections->receiptFor($feePayment, $actor),
        ]);
    }
}
