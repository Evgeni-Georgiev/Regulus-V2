<?php

namespace App\Services;

use App\Enums\TransactionTypeEnum;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Resources\TransactionCollection;
use App\Http\Resources\TransactionResource;
use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\PortfolioHistory;
use App\Models\Transaction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service class for processing transaction-related operations.
 * Handles complex transaction logic including filtering, calculations, and creation.
 */
class TransactionProcessService
{
    private PortfolioService $portfolioService;
    private CMCClient $cmcClient;

    public function __construct(PortfolioService $portfolioService, CMCClient $cmcClient)
    {
        $this->portfolioService = $portfolioService;
        $this->cmcClient = $cmcClient;
    }

    /**
     * Retrieves and processes transactions with filtering and calculations.
     * Includes portfolio validation, transaction filtering, and profit/loss calculations.
     *
     * @param Request $request The HTTP request containing filter parameters
     * @return TransactionCollection Collection of transactions with metadata
     * @throws AuthorizationException
     */
    public function retrieveTransactionsWithMetrics(Request $request): TransactionCollection
    {
        $user = $request->user();

        // Build base query for user's transactions
        $query = $this->buildUserTransactionQuery($user);

        // Apply portfolio filter with validation
        if ($request->has('portfolio_id')) {
            $portfolio = $this->validateUserPortfolio($request->input('portfolio_id'), $user);
            $query->where('portfolio_id', $portfolio->id);
        }

        // Apply coin filter
        if ($request->has('coin_id')) {
            $query->where('coin_id', $request->input('coin_id'));
        }

        $transactions = $query->get();

        // Calculate transaction totals
        $transactionTotals = $this->calculateTransactionTotals($transactions);

        // Calculate profit/loss metrics if specific portfolio and coin are requested
        $profitLossMetrics = $this->calculateProfitLossMetrics($request, $transactionTotals);

        return (new TransactionCollection($transactions))
            ->additional([
                'meta' => array_merge($transactionTotals, $profitLossMetrics)
            ]);
    }

    /**
     * Creates a new transaction with portfolio validation and balance checks.
     * Handles database transaction, portfolio history, and insufficient balance scenarios.
     *
     * @param array $validatedData Validated transaction data
     * @param Authenticatable $user The authenticated user
     * @return TransactionResource The created transaction resource
     * @throws InsufficientBalanceException When insufficient balance for sell transactions
     */
    public function createTransactionWithValidation(array $validatedData, Authenticatable $user): TransactionResource
    {
        return DB::transaction(function () use ($validatedData, $user) {
            // Validate portfolio ownership
            $portfolio = $this->validateUserPortfolio($validatedData['portfolio_id'], $user);

            // Setup portfolio service with required data
            $portfolioService = $this->portfolioService
                ->loadPortfolio($portfolio)
                ->loadCoin(Coin::find($validatedData['coin_id']))
                ->setData($validatedData);

            // Get current portfolio value before transaction
            $previousValue = $portfolioService->getPortfolioDetails($this->cmcClient->getCoinData())['total_value'];

            // Validate sufficient balance for sell transactions
            if ($validatedData['transaction_type'] === TransactionTypeEnum::SELL) {
                $this->validateSufficientBalance($validatedData);
            }

            // Create portfolio history record
            $portfolioHistory = $this->createPortfolioHistoryRecord($portfolio->id, $previousValue);

            // Create the transaction
            $transaction = Transaction::create($validatedData);

            // Update portfolio history with transaction details
            $this->updatePortfolioHistoryWithTransaction($portfolioHistory, $transaction, $portfolioService);

            return new TransactionResource($transaction);
        });
    }

    /**
     * Builds the base query for retrieving user's transactions.
     *
     * @param Authenticatable $user The authenticated user
     * @return Builder Transaction query builder
     */
    private function buildUserTransactionQuery(Authenticatable $user): Builder
    {
        return Transaction::whereHas('portfolio', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        });
    }

    /**
     * Validates that a portfolio belongs to the authenticated user.
     *
     * @param int $portfolioId The portfolio ID to validate
     * @param Authenticatable $user The authenticated user
     * @return Portfolio The validated portfolio
     * @throws AuthorizationException When portfolio doesn't belong to user
     */
    private function validateUserPortfolio(int $portfolioId, Authenticatable $user): Portfolio
    {
        $portfolio = Portfolio::where('id', $portfolioId)
            ->where('user_id', $user->id)
            ->first();

        if (!$portfolio) {
            abort(Response::HTTP_FORBIDDEN, 'Access denied. This portfolio does not belong to you.');
        }

        return $portfolio;
    }

    /**
     * Calculates transaction totals including bought, sold, and net position.
     *
     * @param Collection $transactions Collection of transactions
     * @return array Array containing transaction totals
     */
    private function calculateTransactionTotals(Collection $transactions): array
    {
        $totalBought = 0;
        $totalSold = 0;

        foreach ($transactions as $transaction) {
            $amount = $transaction->quantity * $transaction->buy_price;

            if ($transaction->transaction_type === TransactionTypeEnum::BUY) {
                $totalBought += $amount;
            } elseif ($transaction->transaction_type === TransactionTypeEnum::SELL) {
                $totalSold += $amount;
            }
        }

        return [
            'total_bought' => $totalBought,
            'total_sold' => $totalSold,
            'net_position' => $totalBought - $totalSold,
        ];
    }

    /**
     * Calculates profit/loss metrics for specific portfolio and coin combinations.
     *
     * @param Request $request The HTTP request
     * @param array $transactionTotals Previously calculated transaction totals
     * @return array Array containing profit/loss metrics
     */
    private function calculateProfitLossMetrics(Request $request, array $transactionTotals): array
    {
        $metrics = [
            'profit_loss' => 0,
            'current_value' => 0,
            'average_buy_price' => 0,
            'total_holding_quantity' => 0
        ];

        if (!$request->has('portfolio_id') || !$request->has('coin_id')) {
            return $metrics;
        }

        // Get transactions for the specific portfolio and coin
        $transactions = Transaction::where('portfolio_id', $request->input('portfolio_id'))
            ->where('coin_id', $request->input('coin_id'))
            ->get();

        if ($transactions->isEmpty()) {
            return $metrics;
        }

        // Calculate metrics from transactions
        $buyTransactions = $transactions->where('transaction_type', TransactionTypeEnum::BUY);
        $sellTransactions = $transactions->where('transaction_type', TransactionTypeEnum::SELL);

        $totalBuyQuantity = $buyTransactions->sum('quantity');
        $totalBuyValue = $buyTransactions->sum(function($t) {
            return $t->quantity * $t->buy_price;
        });
        $totalSellQuantity = $sellTransactions->sum('quantity');

        // Calculate remaining holdings
        $totalHoldingQuantity = $totalBuyQuantity - $totalSellQuantity;

        // Calculate average buy price
        $averageBuyPrice = $totalBuyQuantity > 0 ? $totalBuyValue / $totalBuyQuantity : 0;

        // Get current coin price
        $coin = Coin::find($request->input('coin_id'));
        $currentPrice = $coin->price ?? 0;

        // Calculate current value and profit/loss
        $currentValue = $totalHoldingQuantity * $currentPrice;
        $invested = $averageBuyPrice * $totalHoldingQuantity;

        return [
            'profit_loss' => $currentValue - $invested,
            'current_value' => $currentValue,
            'average_buy_price' => $averageBuyPrice,
            'total_holding_quantity' => $totalHoldingQuantity
        ];
    }

    /**
     * Validates sufficient balance for sell transactions.
     *
     * @param array $validatedData Transaction data
     * @throws InsufficientBalanceException When insufficient balance
     */
    private function validateSufficientBalance(array $validatedData): void
    {
        $totalHolding = $this->portfolioService->getTotalHoldingForCoin(
            Coin::find($validatedData['coin_id'])
        );

        if ($totalHolding < $validatedData['quantity']) {
            throw new InsufficientBalanceException("Not enough balance to sell.");
        }
    }

    /**
     * Creates a portfolio history record before transaction processing.
     *
     * @param int $portfolioId The portfolio ID
     * @param float $previousValue The portfolio value before transaction
     * @return PortfolioHistory The created portfolio history record
     */
    private function createPortfolioHistoryRecord(int $portfolioId, float $previousValue): PortfolioHistory
    {
        return PortfolioHistory::create([
            'portfolio_id' => $portfolioId,
            'previous_value' => $previousValue,
            'changed_at' => now(),
        ]);
    }

    /**
     * Updates portfolio history record with transaction details.
     *
     * @param PortfolioHistory $portfolioHistory The portfolio history record
     * @param Transaction $transaction The created transaction
     * @param PortfolioService $portfolioService The portfolio service instance
     */
    private function updatePortfolioHistoryWithTransaction(
        PortfolioHistory $portfolioHistory,
        Transaction $transaction,
        PortfolioService $portfolioService
    ): void {
        $portfolioHistory->update([
            'change_type' => $transaction->transaction_type,
            'change_value' => $transaction->quantity * $transaction->buy_price,
            'new_value' => $portfolioService->getPortfolioDetails($this->cmcClient->getCoinData())['total_value']
        ]);
    }
}
