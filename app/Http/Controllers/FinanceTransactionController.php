<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFinanceTransactionRequest;
use App\Http\Requests\UpdateFinanceTransactionRequest;
use App\Http\Resources\V1\FinanceTransactionResource;
use App\Models\FinanceTransaction;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FinanceTransactionController extends Controller
{
    public function __construct(protected FinanceService $financeService) {}

    public function index(): AnonymousResourceCollection
    {
        $transactions = FinanceTransaction::query()
            ->latest('transaction_date')
            ->paginate(15);

        return FinanceTransactionResource::collection($transactions);
    }

    public function publicIndex(): AnonymousResourceCollection
    {
        $transactions = FinanceTransaction::query()
            ->where('status', 'published')
            ->latest('transaction_date')
            ->paginate(15);

        return FinanceTransactionResource::collection($transactions);
    }

    public function publicSummary(Request $request): JsonResponse
    {
        $year = $request->query('year') ? (int) $request->query('year') : null;
        $month = $request->query('month') ? (int) $request->query('month') : null;

        $summary = $this->financeService->getPublicSummary($year, $month);

        return response()->json([
            'data' => [
                'total_income' => $summary['total_income'],
                'total_expense' => $summary['total_expense'],
                'net_balance' => $summary['net_balance'],
                'transactions' => FinanceTransactionResource::collection($summary['transactions']),
            ],
        ]);
    }

    public function store(StoreFinanceTransactionRequest $request): FinanceTransactionResource
    {
        $transaction = $this->financeService->create($request->validated(), $request->user());

        return new FinanceTransactionResource($transaction);
    }

    public function update(UpdateFinanceTransactionRequest $request, int|string $id): FinanceTransactionResource
    {
        $transaction = FinanceTransaction::query()->findOrFail($id);
        $data = $request->validated();

        if ($data['action'] === 'publish') {
            $transaction = $this->financeService->publish($transaction, (int) $data['version']);
        } elseif ($data['action'] === 'reverse') {
            $transaction = $this->financeService->reverse($transaction, (int) $data['version'], (string) $data['reason']);
        }

        return new FinanceTransactionResource($transaction);
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        if (! $request->user()?->hasPermission('finance.manage')) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $transaction = FinanceTransaction::query()->findOrFail($id);
        $this->financeService->delete($transaction);

        return response()->json(['message' => 'Transaksi berhasil dihapus.'], 200);
    }
}
