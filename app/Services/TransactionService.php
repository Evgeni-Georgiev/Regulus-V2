<?php

namespace App\Services;

use App\Enums\TransactionTypeEnum;
use App\Models\Coin;
use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Class TransactionService
 * 
 * Handles transaction processing, calculations, and transformations.
 * Provides methods for transaction metrics and data processing.
 */
class TransactionService
{
    /**
     * Processes transactions for response.
     * Formats transaction data for frontend display.
     *
     * @param Collection $transactions Collection of transactions to process
     * @return Collection Processed transaction data
     */
    public function processTransactions(Collection $transactions): Collection
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

    /**
     * Processes a group of transactions for a single coin.
     * Applies the average cost method to calculate cost basis and profit/loss.
     * 
     * @param Collection $transactions All transactions for a specific coin
     * @param array $apiCoin Current coin market data
     * @return array Processed coin data with metrics
     */
    public function processCoinTransactions(Collection $transactions, array $apiCoin): array
    {
        $coin = $transactions->first()->coin;
        
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
     * Get the total quantity holding for a specific coin in a collection of transactions.
     * Calculates net quantity by subtracting sells from buys.
     *
     * @param Collection $transactions Collection of transactions for a coin
     * @return float Total holding quantity (may be zero if fully sold)
     */
    public function getTotalHoldingQuantity(Collection $transactions): float
    {
        return $transactions->sum(fn($transaction) => 
            $transaction->transaction_type === TransactionTypeEnum::BUY 
                ? $transaction->quantity 
                : -$transaction->quantity
        );
    }

    /**
     * Calculates metrics for a specific coin.
     * Computes holding quantity, average buy price, and related metrics.
     *
     * @param Collection $transactions All transactions for a specific coin
     * @param float $currentCoinPrice Current market price for the coin
     * @return array Array of calculated metrics
     */
    public function calculateCoinMetrics(Collection $transactions, float $currentCoinPrice): array
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
} 