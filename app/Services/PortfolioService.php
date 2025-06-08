<?php

namespace App\Services;

use App\Enums\TransactionTypeEnum;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Class PortfolioService
 * 
 * Manages portfolio operations, calculations, and data transformations.
 * Handles profit/loss calculations using the average cost method.
 */
class PortfolioService
{
    protected Portfolio $portfolio;
    protected Transaction $transaction;
    protected Coin $coin;
    protected array $data;
    protected TransactionService $transactionService;

    /**
     * Constructor to inject dependencies
     */
    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Set transaction data.
     *
     * @param array $data The transaction data to set
     * @return static Returns the current instance for method chaining
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Load the portfolio by ID.
     *
     * @param Portfolio $portfolio The portfolio to load
     * @return static Returns the current instance for method chaining
     */
    public function loadPortfolio(Portfolio $portfolio): static
    {
        $this->portfolio = $portfolio;
        return $this;
    }

    /**
     * Load the coin by ID.
     *
     * @param Coin $coin The coin to load
     * @return static Returns the current instance for method chaining
     */
    public function loadCoin(Coin $coin): static
    {
        $this->coin = $coin;
        return $this;
    }

    /**
     * Load the transaction by ID.
     *
     * @param Transaction $transaction The transaction to load
     * @return static Returns the current instance for method chaining
     */
    public function loadTransaction(Transaction $transaction): static
    {
        $this->transaction = $transaction;
        return $this;
    }

    /**
     * Gets detailed portfolio information including coin calculations.
     * Computes portfolio-wide metrics including total value, cost basis, and profit/loss.
     *
     * @param Collection $coinData Collection of current coin market data
     * @return array Portfolio details including all calculated metrics
     */
    public function getPortfolioDetails(Collection $coinData): array
    {
        $groupedTransactions = $this->getGroupTransactions($coinData);
        
        // Calculate portfolio-wide metrics
        $totalValue = $groupedTransactions->sum(function ($coin) {
            return $coin['price'] * $coin['total_holding_quantity'];
        });
        
        $totalCostBasis = $groupedTransactions->sum(function ($coin) {
            return $coin['total_cost_basis'];
        });
        
        $totalProfitLoss = $totalValue - $totalCostBasis;

        return [
            'id' => $this->portfolio->id,
            'name' => $this->portfolio->name,
            'total_value' => $totalValue,
            'total_cost_basis' => $totalCostBasis,
            'total_profit_loss' => $totalProfitLoss,
            'coins' => $groupedTransactions->values(), // Reset array keys
        ];
    }

    /**
     * Calculates total portfolio value by summing up all current coin values.
     * Current value = Sum of (coin price × holding quantity) for all coins.
     *
     * @param Collection $groupedTransactions Collection of grouped transaction data by coin
     * @return float The total current value of the portfolio
     */
    public function calculateTotalPortfolioValue(Collection $groupedTransactions): float
    {
        return $groupedTransactions->sum(function ($coin) {
            return $coin['price'] * $coin['total_holding_quantity'];
        });
    }

    /**
     * Groups transactions by coin and calculate relevant metrics.
     * For each coin, processes transactions to compute holdings, cost basis, and profit/loss.
     *
     * @param Collection $coinData Collection of current coin market data
     * @return Collection Grouped and processed transaction data by coin
     */
    private function getGroupTransactions(Collection $coinData): Collection
    {
        // Group transactions by coin and include related coin details
        return $this->portfolio->transactions()
            ->with('coin')
            ->get()
            ->groupBy('coin_id')
            ->map(function ($transactions) use ($coinData) {
                return $this->processCoinGroup($transactions, $coinData);
            })
            ->filter();
    }

    /**
     * Processes a group of transactions for a single coin.
     *
     * @param Collection $transactions All transactions for a specific coin
     * @param Collection $coinData Collection of current coin market data
     * @return array|null Processed coin data with metrics or null if coin not found
     */
    private function processCoinGroup($transactions, Collection $coinData): ?array
    {
        $firstTransaction = $transactions->first();

        // If no coin is found, skip grouping for invalid data
        $coin = $firstTransaction->coin;

        if (!$coin || !isset($coinData[$coin->symbol])) {
            return null;
        }

        $apiCoin = $coinData[$coin->symbol];
        
        // Use the transaction service to process coin transactions
        return $this->transactionService->processCoinTransactions($transactions, $apiCoin);
    }

    /**
     * Get the total quantity holding for a specific coin in a portfolio.
     * Calculates net quantity by subtracting sells from buys.
     *
     * @param Coin $coin The coin to calculate holdings for
     * @return float Total holding quantity (may be zero if fully sold)
     */
    public function getTotalHoldingForCoin(Coin $coin): float
    {
        $transactions = $this->portfolio->transactions()
            ->where('coin_id', $coin->id)
            ->get();
            
        return $this->transactionService->getTotalHoldingQuantity($transactions);
    }
}
