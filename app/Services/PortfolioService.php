<?php

namespace App\Services;

use App\Enums\TransactionTypeEnum;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Coin;
use App\Models\Portfolio;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class PortfolioService
{
    protected Portfolio $portfolio;
    protected Transaction $transaction;
    protected Coin $coin;
    protected array $data;

    /**
     * Set transaction data.
     *
     * @param array $data
     * @return static
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Load the portfolio by ID.
     *
     * @param Portfolio $portfolio
     * @return static
     */
    public function loadPortfolio(Portfolio $portfolio): static
    {
        $this->portfolio = $portfolio;
        return $this;
    }

    /**
     * Load the coin by ID.
     *
     * @param Coin $coin
     * @return static
     */
    public function loadCoin(Coin $coin): static
    {
        $this->coin = $coin;
        return $this;
    }

    /**
     * Load the transaction by ID.
     *
     * @param Transaction $transaction
     * @return static
     */
    public function loadTransaction(Transaction $transaction): static
    {
        $this->transaction = $transaction;
        return $this;
    }

    /**
     * Gets detailed portfolio information including coin calculations.
     *
     * @param Collection $coinData
     * @return array
     */
    public function getPortfolioDetails(Collection $coinData): array
    {
        $groupedTransactions = $this->getGroupTransactions($coinData);

        return [
            'id' => $this->portfolio->id,
            'name' => $this->portfolio->name,
            'total_value' => $this->calculateTotalPortfolioValue($groupedTransactions),
            'coins' => $this->getGroupTransactions($coinData)->values(), // Reset array keys,
        ];
    }

    /**
     * Calculates total portfolio value by summing up all fiat investments.
     *
     * @param Collection $groupedTransactions
     * @return float
     */
    public function calculateTotalPortfolioValue(Collection $groupedTransactions): float
    {
        return max(0, $groupedTransactions->sum('fiat_spent_on_quantity'));
    }

    /**
     * Get the total quantity holding for a specific coin in a portfolio.
     *
     * @param Coin $coin
     * @return float
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
     *
     * @param Collection $coinData
     * @return Collection
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
     * @param $transactions
     * @param Collection $coinData
     * @return array|null
     * @throws InsufficientBalanceException
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
        $coinMetrics = $this->calculateCoinMetrics($transactions, $apiCoin['price']);

        return [
            'id' => $coin->id,
            'symbol' => $apiCoin['symbol'],
            'name' => $apiCoin['name'],
            'price' => $apiCoin['price'],
            'fiat_spent_on_quantity' => $coinMetrics['fiat_spent_on_quantity'],
            'total_holding_quantity' => $coinMetrics['total_holding_quantity'],
            'average_buy_price' => $coinMetrics['average_buy_price'],
            'transactions' => $this->processTransactions($transactions)->toArray()
        ];
    }

    /**
     * Calculates metrics for a specific coin.
     *
     * @param $transactions
     * @param $currentCoinPrice
     * @return array
     */
    private function calculateCoinMetrics($transactions, $currentCoinPrice): array
    {
        // Calculate total quantity for the coin
        $totalQuantity = $transactions->sum(function ($transaction) {
            return $transaction->transaction_type === TransactionTypeEnum::BUY
                ? $transaction->quantity
                : -$transaction->quantity;
        });

        // Calculate weighted average buy price
        $totalCost = $transactions->reduce(function ($sum, $transaction) {
            return $sum + ($transaction->quantity * $transaction->buy_price * ($transaction->transaction_type === TransactionTypeEnum::BUY ? 1 : -1));
        }, 0);

        return [
            'fiat_spent_on_quantity' => max(0, $totalQuantity * $currentCoinPrice), // $currentInvestmentCoinValue
            'total_holding_quantity' => max(0, $totalQuantity),
            'average_buy_price' => $totalQuantity > 0 ? $totalCost / $totalQuantity : 0,
        ];
    }

    /**
     * Processes transactions for response.
     *
     * @param $transactions
     * @return Collection
     * @throws InsufficientBalanceException
     */
    private function processTransactions($transactions): Collection
    {
        return $transactions->map(function ($transaction) {
//            return $this->processTransaction();
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
