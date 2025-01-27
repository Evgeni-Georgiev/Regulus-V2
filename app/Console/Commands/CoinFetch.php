<?php

namespace App\Console\Commands;

use App\Events\CoinPriceUpdated;
use App\Services\CMCClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CoinFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:coin-fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule the UpdateCoinPricesJob job';

    protected CMCClient $service;

    public function __construct(CMCClient $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $newData = $this->service->getCoinData();
        event(new CoinPriceUpdated($newData->toArray()));
        Log::info('Broadcasted updated coin prices.', ['data' => $newData->toArray()]);
        $this->info('Scheduled UpdateCoinPricesJob job to run every minute.');
    }
}
