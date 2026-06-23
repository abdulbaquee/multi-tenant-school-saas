<?php

namespace App\Http\Controllers;

use App\Http\Requests\SandboxTransactionIndexRequest;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\SandboxTransactionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentTransactionController extends Controller
{
    public function __construct(private readonly SandboxTransactionService $sandboxTransactions) {}

    public function index(SandboxTransactionIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();

        return view('payment-transactions.index', [
            'paymentTransactions' => $this->sandboxTransactions->listFor($actor, $request->validated()),
        ]);
    }

    public function show(Request $request, PaymentTransaction $paymentTransaction): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->authorize('view', $paymentTransaction);
        $paymentTransaction = $this->sandboxTransactions->showFor($paymentTransaction, $actor);

        return view('payment-transactions.show', [
            'paymentTransaction' => $paymentTransaction,
            'sandboxPayload' => $this->sandboxTransactions->sanitizedPayload($paymentTransaction),
        ]);
    }
}
