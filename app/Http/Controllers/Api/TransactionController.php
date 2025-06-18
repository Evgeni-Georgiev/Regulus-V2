<?php

namespace App\Http\Controllers\Api;

use App\Enums\TransactionTypeEnum;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionStoreRequest;
use App\Http\Requests\TransactionUpdateRequest;
use App\Http\Resources\TransactionCollection;
use App\Http\Resources\TransactionResource;
use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\PortfolioHistory;
use App\Models\Transaction;
use App\Services\CMCClient;
use App\Services\PortfolioService;
use App\Services\TransactionProcessService;
use Exception;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionController extends Controller
{
    private TransactionProcessService $transactionProcessService;

    public function __construct(TransactionProcessService $transactionProcessService)
    {
        $this->transactionProcessService = $transactionProcessService;
    }

    /**
     * Retrieve transactions with filtering and metrics calculation.
     * Supports filtering by portfolio_id and coin_id with proper authorization.
     *
     * @param Request $request The HTTP request containing filter parameters
     * @return TransactionCollection Collection of transactions with metadata
     */
    public function index(Request $request): TransactionCollection
    {
        try {
            $this->authorize('viewAny', Transaction::class);

            return $this->transactionProcessService->retrieveTransactionsWithMetrics($request);

        } catch (Exception $e) {
            Log::error('Error in TransactionController@index: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'portfolio_id' => $request->input('portfolio_id'),
                'coin_id' => $request->input('coin_id'),
                'trace' => $e->getTraceAsString()
            ]);

            return (new TransactionCollection([]))
                ->additional([
                    'error' => 'Failed to load transactions',
                    'meta' => [
                        'total_bought' => 0,
                        'total_sold' => 0,
                        'net_position' => 0,
                        'profit_loss' => 0,
                        'current_value' => 0,
                        'average_buy_price' => 0,
                        'total_holding_quantity' => 0
                    ]
                ]);
        }
    }

    /**
     * Create a new transaction with validation and portfolio history tracking.
     * Handles authorization, balance validation, and database transactions.
     *
     * @param TransactionStoreRequest $request The validated transaction request
     * @return TransactionResource The created transaction resource
     * @throws InsufficientBalanceException When insufficient balance for sell transactions
     */
    public function store(TransactionStoreRequest $request): TransactionResource
    {
        try {
            $this->authorize('create', Transaction::class);

            return $this->transactionProcessService->createTransactionWithValidation(
                $request->validated(),
                $request->user()
            );

        } catch (InsufficientBalanceException $e) {
            throw new InsufficientBalanceException($e->getMessage());
        } catch (Throwable $e) {
            throw new HttpResponseException(response()->json([
                'error' => 'Transaction failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    public function show(Transaction $transaction): TransactionResource
    {
        $this->authorize('view', $transaction);

        return new TransactionResource($transaction);
    }

    public function update(TransactionUpdateRequest $request, Transaction $transaction): TransactionResource
    {
        $this->authorize('update', $transaction);

        $transaction->update($request->validated());

        return new TransactionResource($transaction);
    }

    public function destroy(Transaction $transaction): Response
    {
        $this->authorize('delete', $transaction);

        $transaction->delete();

        return response()->noContent();
    }
}
