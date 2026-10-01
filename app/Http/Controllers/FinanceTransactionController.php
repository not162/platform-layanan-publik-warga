<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinanceTransactionRequest;
use App\Http\Requests\UpdateFinanceTransactionRequest;
use App\Http\Resources\V1\FinanceTransactionResource;
use App\Models\FinanceTransaction;
use App\Services\FinanceService;

class FinanceTransactionController extends Controller
{
    public function __construct(protected FinanceService $financeService) {}

    public function index()
    {
        $transactions = FinanceTransaction::latest('transaction_date')->paginate(15);

        return FinanceTransactionResource::collection($transactions);
    }

    public function publicIndex()
    {
        $transactions = FinanceTransaction::where('status', 'published')
            ->latest('transaction_date')
            ->paginate(15);

        return FinanceTransactionResource::collection($transactions);
    }

    public function store(StoreFinanceTransactionRequest $request)
    {
        $transaction = $this->financeService->create($request->validated());

        return new FinanceTransactionResource($transaction);
    }

    public function update(UpdateFinanceTransactionRequest $request, $id)
    {
        $transaction = FinanceTransaction::findOrFail($id);
        $data = $request->validated();

        if ($data['action'] === 'publish') {
            $transaction = $this->financeService->publish($transaction, $data['version']);
        } elseif ($data['action'] === 'reverse') {
            $transaction = $this->financeService->reverse($transaction, $data['version'], $data['reason']);
        }

        return new FinanceTransactionResource($transaction);
    }
}
