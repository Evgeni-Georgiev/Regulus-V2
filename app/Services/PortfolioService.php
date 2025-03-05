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
     * Get the total quantity holding for a specific coin in a portfolio.
     * Calculates net quantity by subtracting sells from buys.
     *
     * @param Coin $coin The coin to calculate holdings for
     * @return float Total holding quantity (may be zero if fully sold)
     */
    public function getTotalHoldingForCoin(Coin $coin): float
    {
        return $this->portfolio->transactions()
            ->where('coin_id', $coin->id)
            ->get()
            ->sum(fn($transaction) => $transaction->transaction_type === TransactionTypeEnum::BUY ? $transaction->quantity : -$transaction->quantity);
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
     * Applies the average cost method to calculate cost basis and profit/loss.
     * 
     * Average cost method:
     * 1. Cost basis = Sum of (quantity × price) for all buy transactions
     * 2. When selling, reduce cost basis by (quantity sold × average cost)
     * 3. Profit/loss = Current value - Remaining cost basis
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
        
        // Get all buy and sell transactions
        $buyTransactions = $transactions->filter(fn($t) => $t->transaction_type === TransactionTypeEnum::BUY);
        $sellTransactions = $transactions->filter(fn($t) => $t->transaction_type === TransactionTypeEnum::SELL);
        
        // Calculate total buy and sell quantities and values
        $totalBuyQuantity = $buyTransactions->sum('quantity');
        $totalBuyValue = $buyTransactions->sum(function($t) {
            return $t->quantity * $t->buy_price;
        });
        
        $totalSellQuantity = $sellTransactions->sum('quantity');
        $totalSellValue = $sellTransactions->sum(function($t) {
            return $t->quantity * $t->buy_price;
        });
        
        // Calculate remaining quantity
        $remainingQuantity = max(0, $totalBuyQuantity - $totalSellQuantity);
        
        // Calculate average buy price
        $averageBuyPrice = $totalBuyQuantity > 0 ? $totalBuyValue / $totalBuyQuantity : 0;
        
        // Calculate cost basis according to the average cost method
        // When selling, we remove (quantity sold × average cost) from the total cost basis
        $costBasisRemoved = $totalSellQuantity * $averageBuyPrice;
        $remainingCostBasis = max(0, $totalBuyValue - $costBasisRemoved);
        
        // Current market value of holdings
        $currentValue = $remainingQuantity * $apiCoin['price'];
        
        // Calculate profit/loss (current value minus cost basis)
        $profitLoss = $currentValue - $remainingCostBasis;
        
        // Additional data for frontend profit/loss percentage calculation
        $percentChange1h = $apiCoin['percent_change_1h'] ?? 0;
        $percentChange24h = $apiCoin['percent_change_24h'] ?? 0;
        $percentChange7d = $apiCoin['percent_change_7d'] ?? 0;

        return [
            'id' => $coin->id,
            'symbol' => $apiCoin['symbol'],
            'name' => $apiCoin['name'],
            'price' => $apiCoin['price'],
            'total_holding_quantity' => $remainingQuantity,
            'average_buy_price' => $averageBuyPrice,
            'total_buy_value' => $totalBuyValue,          // Total value of all buys
            'total_sell_value' => $totalSellValue,        // Total value of all sells
            'total_cost_basis' => $remainingCostBasis,    // Remaining cost basis after sells
            'current_value' => $currentValue,             // Current market value of holdings
            'profit_loss' => $profitLoss,                 // Profit/loss in currency
            'percent_change_1h' => $percentChange1h,
            'percent_change_24h' => $percentChange24h,
            'percent_change_7d' => $percentChange7d,
            'fiat_spent_on_quantity' => $currentValue,    // For backward compatibility
            'transactions' => $this->processTransactions($transactions)->toArray()
        ];
    }

    /**
     * Calculates metrics for a specific coin.
     * Computes holding quantity, average buy price, and related metrics.
     *
     * @param Collection $transactions All transactions for a specific coin
     * @param float $currentCoinPrice Current market price for the coin
     * @return array Array of calculated metrics
     */
    private function calculateCoinMetrics($transactions, $currentCoinPrice): array
    {
        // Get buy and sell transactions
        $buyTransactions = $transactions->filter(fn($t) => $t->transaction_type === TransactionTypeEnum::BUY);
        $sellTransactions = $transactions->filter(fn($t) => $t->transaction_type === TransactionTypeEnum::SELL);
        
        // Calculate totals for buys
        $totalBuyQuantity = $buyTransactions->sum('quantity');
        $totalBuyValue = $buyTransactions->sum(function($t) {
            return $t->quantity * $t->buy_price;
        });
        
        // Calculate totals for sells
        $totalSellQuantity = $sellTransactions->sum('quantity');
        
        // Calculate remaining quantity
        $remainingQuantity = max(0, $totalBuyQuantity - $totalSellQuantity);
        
        // Calculate average buy price
        $averageBuyPrice = $totalBuyQuantity > 0 ? $totalBuyValue / $totalBuyQuantity : 0;
        
        // Current value of holdings
        $currentValue = $remainingQuantity * $currentCoinPrice;

        return [
            'fiat_spent_on_quantity' => $currentValue,
            'total_holding_quantity' => $remainingQuantity,
            'average_buy_price' => $averageBuyPrice,
            'total_buy_value' => $totalBuyValue,
        ];
    }

    /**
     * Processes transactions for response.
     * Formats transaction data for frontend display.
     *
     * @param Collection $transactions Collection of transactions to process
     * @return Collection Processed transaction data
     */
    private function processTransactions($transactions): Collection
    {
        return $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_type' => $transaction->transaction_type,
                'quantity' => $transaction->quantity,
                'buy_price' => $transaction->buy_price,
                'total_price' => $transaction->quantity * $transaction->buy_price,
                'created_at' => $transaction->created_at->format('d-m-Y'),
            ];
        });
    }
}
