<?php

namespace App\Services;

use App\Models\Coin;
use Illuminate\Support\Facades\Log;

class CoinSyncService
{
    /**
     * Handles a sync of API data of coins with database for backup.
     * Data will be inserted only if not persisted before.
     *
     * @return void
     */
    public function syncCoinsFromApi(): void
    {
        try {
            $coinsData = app(CMCClient::class)->getCoinData();

            if($coinsData->isEmpty()) {
                Log::warning('API returned empty coin data!');
                return;
            }

            $getCoinsSymbol = Coin::pluck('symbol')->toArray();

            foreach($coinsData as $symbol => $data) {
                if(!in_array($symbol, $getCoinsSymbol)) {
                    Coin::create([
                        'name' => $data->name,
                        'symbol' => $data->symbol,
                        'price' => $data->price,
                        'market_cap' => $data->market_cap,
                        'percent_change_1h' => $data->percent_change_1h,
                        'percent_change_24h' => $data->percent_change_24h,
                        'percent_change_7d' => $data->percent_change_7d,
                        'volume_24h' => $data->volume_24h,
                    ]);
                } else {
                    Coin::where('symbol', $symbol)->update([
                        'price' => $data->price,
                        'market_cap' => $data->market_cap,
                        'percent_change_1h' => $data->percent_change_1h,
                        'percent_change_24h' => $data->percent_change_24h,
                        'percent_change_7d' => $data->percent_change_7d,
                        'volume_24h' => $data->volume_24h,
                    ]);
                }
            }
            Log::info('API returned coins data!');
        } catch (\Exception $e) {
            Log::error("Error syncing coin data: " . $e->getMessage());
        }
    }
}
