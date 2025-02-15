<?php

namespace App\Services;

use App\Models\Portfolio;
use Illuminate\Support\Collection;

class PortfolioService
{
    /**
     * Gets detailed portfolio information including coin calculations.
     *
     * @param Portfolio $portfolio
     * @param Collection $coinData
     * @return array
     */
    public function getPortfolioDetails(Portfolio $portfolio, Collection $coinData): array
    {
        $groupedTransactions = $this->getGroupTransactions($portfolio, $coinData);

        return [
            'id' => $portfolio->id,
            'name' => $portfolio->name,
            'total_value' => $this->calculateTotalPortfolioValue($groupedTransactions),
            'coins' => $this->getGroupTransactions($portfolio, $coinData)->values(), // Reset array keys,
        ];
    }

    /**
     * Calculates total portfolio value.
     *
     * @param Collection $groupedTransactions
     * @return float
     */
    public function calculateTotalPortfolioValue(Collection $groupedTransactions): float
    {
        return $groupedTransactions->sum('fiat_spent_on_quantity');
    }

    /**
     * Groups transactions by coin and calculate relevant metrics.
     *
     * @param $portfolio
     * @param Collection $coinData
     * @return Collection
     */
    private function getGroupTransactions($portfolio, Collection $coinData): Collection
    {
        // Group transactions by coin and include related coin details
        return $portfolio->transactions()
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
        $totalQuantity = $transactions->sum('quantity');

        // Calculate weighted average buy price
        $totalCost = $transactions->reduce(function ($sum, $transaction) {
            return $sum + ($transaction->quantity * $transaction->buy_price);
        }, 0);

        return [
            'fiat_spent_on_quantity' => $totalQuantity * $currentCoinPrice, // $currentInvestmentCoinValue
            'total_holding_quantity' => $totalQuantity,
            'average_buy_price' => $totalQuantity > 0 ? $totalCost / $totalQuantity : 0,
        ];
    }

    /**
     * Processes transactions for response.
     *
     * @param $transactions
     * @return Collection
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
