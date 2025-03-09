<?php

namespace App\Services;

use App\Models\Coin;
use App\Models\Transaction;
use App\Models\User;
use App\Models\ExchangeApiConnection;
use App\Services\Exchanges\BinanceClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExchangeApiService
{
    /**
     * Sync transactions from all connected exchanges for a user
     */
    public function syncUserExchanges(User $user): void
    {
        $connections = $user->exchangeApiConnections()->get();

        foreach ($connections as $connection) {
            try {
                $this->syncExchangeTransactions($connection);
            } catch (\Exception $e) {
                Log::error("Failed to sync exchange {$connection->exchange_name} for user {$user->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Sync transactions from a specific exchange connection
     */
    public function syncExchangeTransactions(ExchangeApiConnection $connection): bool
    {
        $exchange = $this->getExchangeClient($connection);

        if (!$exchange) {
            return false;
        }

        // Get the last sync time or default to 30 days ago
        $lastSync = $connection->last_synced_at ?? Carbon::now()->subDays(30);

        // Fetch transactions from the exchange
        $transactions = $exchange->getTransactions($lastSync);

        if (!$transactions) {
            return false;
        }

        // Process and save transactions
        $this->saveTransactions($connection->user_id, $connection->exchange_name, $transactions);

        // Update the last sync time
        $connection->last_synced_at = Carbon::now();
        $connection->save();

        return true;
    }

    /**
     * Get the appropriate exchange client based on exchange name
     */
    protected function getExchangeClient(ExchangeApiConnection $connection)
    {
        switch ($connection->exchange_name) {
            case 'binance':
                return new BinanceClient($connection->api_key, $connection->api_secret);

//            case 'bybit':
//                return new BybitClient($connection->api_key, $connection->api_secret);
//
//            case 'gate_io':
//                return new GateIoClient($connection->api_key, $connection->api_secret);
//
            default:
                return null;
        }
    }

    /**
     * Save transactions to the database
     */
    protected function saveTransactions($userId, $exchangeName, $transactions)
    {
        foreach ($transactions as $transaction) {
            // Check if transaction already exists
            $exists = Transaction::where('user_id', $userId)
                ->where('exchange_source', $exchangeName)
                ->where('exchange_transaction_id', $transaction['id'])
                ->exists();

            if (!$exists) {
                $newTransaction = new Transaction([
                    'user_id' => $userId,
                    'asset_id' => $this->resolveAssetId($transaction['symbol']),
                    'transaction_type' => $this->mapTransactionType($transaction['type']),
                    'amount' => $transaction['amount'],
                    'price_per_coin' => $transaction['price'],
                    'total_amount' => $transaction['amount'] * $transaction['price'],
                    'fee' => $transaction['fee'] ?? 0,
                    'fee_currency' => $transaction['feeCurrency'] ?? null,
                    'transaction_date' => Carbon::parse($transaction['timestamp']),
                    'notes' => "Imported from {$exchangeName}",
                    'exchange_source' => $exchangeName,
                    'exchange_transaction_id' => $transaction['id'],
                    'synced_at' => Carbon::now(),
                ]);

                $newTransaction->save();
            }
        }
    }

    /**
     * Map exchange-specific transaction types to your application's types
     */
    protected function mapTransactionType($exchangeType)
    {
        $typeMap = [
            'BUY' => 'buy',
            'SELL' => 'sell',
            'DEPOSIT' => 'deposit',
            'WITHDRAWAL' => 'withdrawal',
            // Add more mappings as needed
        ];

        return $typeMap[$exchangeType] ?? 'other';
    }

    /**
     * Resolve asset ID from symbol
     */
    protected function resolveAssetId($symbol)
    {
        // Implement logic to map exchange symbols to your asset IDs
        // This might involve querying your assets table
        // For simplicity, this is a placeholder

        $asset = Coin::where('symbol', $symbol)
            ->orWhere('name', $symbol)
            ->first();

        return $asset ? $asset->id : null;
    }
}
