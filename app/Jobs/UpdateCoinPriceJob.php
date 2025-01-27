<?php

namespace App\Jobs;

use App\Events\CoinPriceUpdated;
use App\Services\CMCClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateCoinPriceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(CMCClient $service)
    {
        try {
            $coinData = $service->getCoinData();
            $chunks = $coinData->chunk(15);

            foreach ($chunks as $chunk) {
                event(new CoinPriceUpdated($chunk->toArray()));
            }
        } catch (\Exception $e) {
            Log::error('Failed to broadcast coin updates.', ['error' => $e->getMessage()]);
        }
    }
}
