<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeeOutstandingBalanceIndexRequest;
use App\Models\User;
use App\Services\FeeOutstandingBalanceService;
use Illuminate\View\View;

class FeeOutstandingBalanceController extends Controller
{
    public function __construct(private readonly FeeOutstandingBalanceService $outstandingBalances) {}

    public function index(FeeOutstandingBalanceIndexRequest $request): View
    {
        /** @var User $actor */
        $actor = $request->user();
        $payload = $this->outstandingBalances->listFor($actor, $request->validated());

        return view('fee-outstanding-balances.index', $payload);
    }
}
