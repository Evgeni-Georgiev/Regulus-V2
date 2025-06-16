<?php

namespace App\Console\Commands;

use App\Models\ExchangeApiConnection;
use App\Services\ExchangeApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncExchangeTransactions extends Command
{
    protected $signature = 'exchange:sync {user_id?}';
    protected $description = 'Synchronize transactions from connected cryptocurrency exchanges';

    protected $exchangeApiService;

    public function __construct(ExchangeApiService $exchangeApiService)
    {
        parent::__construct();
        $this->exchangeApiService = $exchangeApiService;
    }

    public function handle()
    {
        $userId = $this->argument('user_id');
        
        $query = ExchangeApiConnection::where('is_active', true);
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        $connections = $query->get();
        $count = 0;
        
        foreach ($connections as $connection) {
            try {
                $this->info("Syncing {$connection->exchange_name} for user {$connection->user_id}...");
                $result = $this->exchangeApiService->syncExchangeTransactions($connection);
                
                if ($result) {
                    $count++;
                    $this->info("Sync completed successfully.");
                } else {
                    $this->warn("No new transactions found.");
                }
            } catch (\Exception $e) {
                $this->error("Sync failed: {$e->getMessage()}");
                Log::error("Failed to sync exchange {$connection->exchange_name} for user {$connection->user_id}: " . $e->getMessage());
            }
        }
        
        $this->info("Finished syncing {$count} connections.");
        
        return 0;
    }
} 